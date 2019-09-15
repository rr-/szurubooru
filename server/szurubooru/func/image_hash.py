import logging
from io import BytesIO
from datetime import datetime
from typing import Any, Optional, Tuple, Set, List, Callable
import elasticsearch
import elasticsearch_dsl
import numpy as np
from PIL import Image
from szurubooru import config, errors

# pylint: disable=invalid-name
logger = logging.getLogger(__name__)

# Math based on paper from H. Chi Wong, Marshall Bern and David Goldberg
# Math code taken from https://github.com/ascribe/image-match
# (which is licensed under Apache 2 license)

LOWER_PERCENTILE = 5
UPPER_PERCENTILE = 95
IDENTICAL_TOLERANCE = 2 / 255.
DISTANCE_CUTOFF = 0.45
N_LEVELS = 2
N = 9
P = None
SAMPLE_WORDS = 16
MAX_WORDS = 63
ES_DOC_TYPE = 'image'
ES_MAX_RESULTS = 100

Window = Tuple[Tuple[float, float], Tuple[float, float]]
NpMatrix = Any


def get_session() -> elasticsearch.Elasticsearch:
    extra_args = {}
    if config.config['elasticsearch']['pass']:
        extra_args['http_auth'] = (
            config.config['elasticsearch']['user'],
            config.config['elasticsearch']['pass'])
        extra_args['scheme'] = 'https'
        extra_args['port'] = 443
    return elasticsearch.Elasticsearch([{
        'host': config.config['elasticsearch']['host'],
        'port': config.config['elasticsearch']['port'],
    }], **extra_args)


def _preprocess_image(content: bytes) -> NpMatrix:
    img = Image.open(BytesIO(content))
    return np.asarray(img.convert('L'), dtype=np.uint8)


def _crop_image(
        image: NpMatrix,
        lower_percentile: float,
        upper_percentile: float) -> Window:
    rw = np.cumsum(np.sum(np.abs(np.diff(image, axis=1)), axis=1))
    cw = np.cumsum(np.sum(np.abs(np.diff(image, axis=0)), axis=0))
    upper_column_limit = np.searchsorted(
        cw, np.percentile(cw, upper_percentile), side='left')
    lower_column_limit = np.searchsorted(
        cw, np.percentile(cw, lower_percentile), side='right')
    upper_row_limit = np.searchsorted(
        rw, np.percentile(rw, upper_percentile), side='left')
    lower_row_limit = np.searchsorted(
        rw, np.percentile(rw, lower_percentile), side='right')
    if lower_row_limit > upper_row_limit:
        lower_row_limit = int(lower_percentile / 100. * image.shape[0])
        upper_row_limit = int(upper_percentile / 100. * image.shape[0])
    if lower_column_limit > upper_column_limit:
        lower_column_limit = int(lower_percentile / 100. * image.shape[1])
        upper_column_limit = int(upper_percentile / 100. * image.shape[1])
    return (
        (lower_row_limit, upper_row_limit),
        (lower_column_limit, upper_column_limit))


def _normalize_and_threshold(
        diff_array: NpMatrix,
        identical_tolerance: float,
        n_levels: int) -> None:
    mask = np.abs(diff_array) < identical_tolerance
    diff_array[mask] = 0.
    if np.all(mask):
        return
    positive_cutoffs = np.percentile(
        diff_array[diff_array > 0.], np.linspace(0, 100, n_levels + 1))
    negative_cutoffs = np.percentile(
        diff_array[diff_array < 0.], np.linspace(100, 0, n_levels + 1))
    for level, interval in enumerate(
            positive_cutoffs[i:i + 2]
            for i in range(positive_cutoffs.shape[0] - 1)):
        diff_array[
            (diff_array >= interval[0]) & (diff_array <= interval[1])] = \
            level + 1
    for level, interval in enumerate(
            negative_cutoffs[i:i + 2]
            for i in range(negative_cutoffs.shape[0] - 1)):
        diff_array[
            (diff_array <= interval[0]) & (diff_array >= interval[1])] = \
            -(level + 1)


def _compute_grid_points(
        image: NpMatrix,
        n: float,
        window: Window = None) -> Tuple[NpMatrix, NpMatrix]:
    if window is None:
        window = ((0, image.shape[0]), (0, image.shape[1]))
    x_coords = np.linspace(window[0][0], window[0][1], n + 2, dtype=int)[1:-1]
    y_coords = np.linspace(window[1][0], window[1][1], n + 2, dtype=int)[1:-1]
    return x_coords, y_coords


def _compute_mean_level(
        image: NpMatrix,
        x_coords: NpMatrix,
        y_coords: NpMatrix,
        p: Optional[float]) -> NpMatrix:
    if p is None:
        p = max([2.0, int(0.5 + min(image.shape) / 20.)])
    avg_grey = np.zeros((x_coords.shape[0], y_coords.shape[0]))
    for i, x in enumerate(x_coords):
        lower_x_lim = int(max([x - p / 2, 0]))
        upper_x_lim = int(min([lower_x_lim + p, image.shape[0]]))
        for j, y in enumerate(y_coords):
            lower_y_lim = int(max([y - p / 2, 0]))
            upper_y_lim = int(min([lower_y_lim + p, image.shape[1]]))
            avg_grey[i, j] = np.mean(
                image[lower_x_lim:upper_x_lim, lower_y_lim:upper_y_lim])
    return avg_grey


def _compute_differentials(grey_level_matrix: NpMatrix) -> NpMatrix:
    flipped = np.fliplr(grey_level_matrix)
    right_neighbors = -np.concatenate(
        (
            np.diff(grey_level_matrix),
            (
                np.zeros(grey_level_matrix.shape[0])
                .reshape((grey_level_matrix.shape[0], 1))
            )
        ), axis=1)
    down_neighbors = -np.concatenate(
        (
            np.diff(grey_level_matrix, axis=0),
            (
                np.zeros(grey_level_matrix.shape[1])
                .reshape((1, grey_level_matrix.shape[1]))
            )
        ))
    left_neighbors = -np.concatenate(
        (right_neighbors[:, -1:], right_neighbors[:, :-1]), axis=1)
    up_neighbors = -np.concatenate((down_neighbors[-1:], down_neighbors[:-1]))
    diagonals = np.arange(
        -grey_level_matrix.shape[0] + 1, grey_level_matrix.shape[0])
    upper_left_neighbors = sum([
        np.diagflat(np.insert(np.diff(np.diag(grey_level_matrix, i)), 0, 0), i)
        for i in diagonals])
    upper_right_neighbors = sum([
        np.diagflat(np.insert(np.diff(np.diag(flipped, i)), 0, 0), i)
        for i in diagonals])
    lower_right_neighbors = -np.pad(
        upper_left_neighbors[1:, 1:], (0, 1), mode='constant')
    lower_left_neighbors = -np.pad(
        upper_right_neighbors[1:, 1:], (0, 1), mode='constant')
    return np.dstack(np.array([
        upper_left_neighbors,
        up_neighbors,
        np.fliplr(upper_right_neighbors),
        left_neighbors,
        right_neighbors,
        np.fliplr(lower_left_neighbors),
        down_neighbors,
        lower_right_neighbors]))


def _generate_signature(content: bytes) -> NpMatrix:
    im_array = _preprocess_image(content)
    image_limits = _crop_image(
        im_array,
        lower_percentile=LOWER_PERCENTILE,
        upper_percentile=UPPER_PERCENTILE)
    x_coords, y_coords = _compute_grid_points(
        im_array, n=N, window=image_limits)
    avg_grey = _compute_mean_level(im_array, x_coords, y_coords, p=P)
    diff_matrix = _compute_differentials(avg_grey)
    _normalize_and_threshold(
        diff_matrix,
        identical_tolerance=IDENTICAL_TOLERANCE,
        n_levels=N_LEVELS)
    return np.ravel(diff_matrix).astype('int8')


def _get_words(array: NpMatrix, k: int, n: int) -> NpMatrix:
    word_positions = np.linspace(
        0, array.shape[0], n, endpoint=False).astype('int')
    assert k <= array.shape[0]
    assert word_positions.shape[0] <= array.shape[0]
    words = np.zeros((n, k)).astype('int8')
    for i, pos in enumerate(word_positions):
        if pos + k <= array.shape[0]:
            words[i] = array[pos:pos + k]
        else:
            temp = array[pos:].copy()
            temp.resize(k)
            words[i] = temp
    _max_contrast(words)
    words = _words_to_int(words)
    return words


def _words_to_int(word_array: NpMatrix) -> NpMatrix:
    width = word_array.shape[1]
    coding_vector = 3**np.arange(width)
    return np.dot(word_array + 1, coding_vector)


def _max_contrast(array: NpMatrix) -> None:
    array[array > 0] = 1
    array[array < 0] = -1


def _normalized_distance(
        target_array: NpMatrix,
        vec: NpMatrix,
        nan_value: float = 1.0) -> List[float]:
    target_array = target_array.astype(int)
    vec = vec.astype(int)
    topvec = np.linalg.norm(vec - target_array, axis=1)
    norm1 = np.linalg.norm(vec, axis=0)
    norm2 = np.linalg.norm(target_array, axis=1)
    finvec = topvec / (norm1 + norm2)
    finvec[np.isnan(finvec)] = nan_value
    return finvec


def _safety_blanket(default_param_factory: Callable[[], Any]) -> Callable:
    def wrapper_outer(target_function: Callable) -> Callable:
        def wrapper_inner(*args: Any, **kwargs: Any) -> Any:
            try:
                return target_function(*args, **kwargs)
            except elasticsearch.exceptions.NotFoundError:
                # index not yet created, will be created dynamically by
                # add_image()
                return default_param_factory()
            except elasticsearch.exceptions.ElasticsearchException as ex:
                logger.warning('Problem with elastic search: %s', ex)
                raise errors.ThirdPartyError(
                    'Error connecting to elastic search.')
            except IOError:
                raise errors.ProcessingError('Not an image.')
            except Exception as ex:
                raise errors.ThirdPartyError('Unknown error (%s).' % ex)
        return wrapper_inner
    return wrapper_outer


class Lookalike:
    def __init__(self, score: int, distance: float, path: Any) -> None:
        self.score = score
        self.distance = distance
        self.path = path


@_safety_blanket(lambda: None)
def add_image(path: str, image_content: bytes) -> None:
    assert path
    assert image_content
    signature = _generate_signature(image_content)
    words = _get_words(signature, k=SAMPLE_WORDS, n=MAX_WORDS)

    record = {
        'signature': signature.tolist(),
        'path': path,
        'timestamp': datetime.now(),
    }
    for i in range(MAX_WORDS):
        record['simple_word_' + str(i)] = words[i].tolist()

    get_session().index(
        index=config.config['elasticsearch']['index'],
        doc_type=ES_DOC_TYPE,
        body=record,
        refresh=True)


@_safety_blanket(lambda: None)
def delete_image(path: str) -> None:
    assert path
    get_session().delete_by_query(
        index=config.config['elasticsearch']['index'],
        doc_type=ES_DOC_TYPE,
        body={'query': {'term': {'path': path}}})


@_safety_blanket(lambda: [])
def search_by_image(image_content: bytes) -> List[Lookalike]:
    signature = _generate_signature(image_content)
    words = _get_words(signature, k=SAMPLE_WORDS, n=MAX_WORDS)

    res = get_session().search(
        index=config.config['elasticsearch']['index'],
        doc_type=ES_DOC_TYPE,
        body={
            'query':
            {
                'bool':
                {
                    'should':
                    [
                        {'term': {'simple_word_%d' % i: word.tolist()}}
                        for i, word in enumerate(words)
                    ]
                }
            },
            '_source': {'excludes': ['simple_word_*']}},
        size=ES_MAX_RESULTS,
        timeout='10s')['hits']['hits']

    if len(res) == 0:
        return []

    sigs = np.array([x['_source']['signature'] for x in res])
    dists = _normalized_distance(sigs, np.array(signature))

    ids = set()  # type: Set[int]
    ret = []
    for item, dist in zip(res, dists):
        id = item['_id']
        score = item['_score']
        path = item['_source']['path']
        if id in ids:
            continue
        ids.add(id)
        if dist < DISTANCE_CUTOFF:
            ret.append(Lookalike(score=score, distance=dist, path=path))
    return ret


@_safety_blanket(lambda: None)
def purge() -> None:
    get_session().delete_by_query(
        index=config.config['elasticsearch']['index'],
        doc_type=ES_DOC_TYPE,
        body={'query': {'match_all': {}}},
        refresh=True)


@_safety_blanket(lambda: set())
def get_all_paths() -> Set[str]:
    search = (
        elasticsearch_dsl.Search(
            using=get_session(),
            index=config.config['elasticsearch']['index'],
            doc_type=ES_DOC_TYPE)
        .source(['path']))
    return set(h.path for h in search.scan())
