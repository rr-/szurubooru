import logging
import math
from datetime import datetime
from io import BytesIO
from typing import Any, Callable, List, Optional, Set, Tuple

import HeifImagePlugin
import numpy as np
import pillow_avif
from PIL import Image

from szurubooru import config, errors

logger = logging.getLogger(__name__)

# Math based on paper from H. Chi Wong, Marshall Bern and David Goldberg
# Math code taken from https://github.com/ascribe/image-match
# (which is licensed under Apache 2 license)

LOWER_PERCENTILE = 5
UPPER_PERCENTILE = 95
IDENTICAL_TOLERANCE = 2 / 255.0
DISTANCE_CUTOFF = 0.45
N_LEVELS = 2
N = 9
P = None
SAMPLE_WORDS = 16
MAX_WORDS = 63
SIG_CHUNK_BITS = 32

SIG_NUMS = 8 * N * N
SIG_BASE = 2 * N_LEVELS + 2
SIG_CHUNK_WIDTH = int(SIG_CHUNK_BITS / math.log2(SIG_BASE))
SIG_CHUNK_NUMS = SIG_NUMS / SIG_CHUNK_WIDTH
assert SIG_NUMS % SIG_CHUNK_WIDTH == 0

Window = Tuple[Tuple[float, float], Tuple[float, float]]
NpMatrix = np.ndarray


def _preprocess_image(content: bytes) -> NpMatrix:
    try:
        img = Image.open(BytesIO(content))
        return np.asarray(img.convert("L"), dtype=np.uint8)
    except (IOError, ValueError):
        raise errors.ProcessingError(
            "Unable to generate a signature hash " "for this image."
        )


def _crop_image(
    image: NpMatrix, lower_percentile: float, upper_percentile: float
) -> Window:
    rw = np.cumsum(np.sum(np.abs(np.diff(image, axis=1)), axis=1))
    cw = np.cumsum(np.sum(np.abs(np.diff(image, axis=0)), axis=0))
    upper_column_limit = np.searchsorted(
        cw, np.percentile(cw, upper_percentile), side="left"
    )
    lower_column_limit = np.searchsorted(
        cw, np.percentile(cw, lower_percentile), side="right"
    )
    upper_row_limit = np.searchsorted(
        rw, np.percentile(rw, upper_percentile), side="left"
    )
    lower_row_limit = np.searchsorted(
        rw, np.percentile(rw, lower_percentile), side="right"
    )
    if lower_row_limit > upper_row_limit:
        lower_row_limit = int(lower_percentile / 100.0 * image.shape[0])
        upper_row_limit = int(upper_percentile / 100.0 * image.shape[0])
    if lower_column_limit > upper_column_limit:
        lower_column_limit = int(lower_percentile / 100.0 * image.shape[1])
        upper_column_limit = int(upper_percentile / 100.0 * image.shape[1])
    return (
        (lower_row_limit, upper_row_limit),
        (lower_column_limit, upper_column_limit),
    )


def _normalize_and_threshold(
    diff_array: NpMatrix, identical_tolerance: float, n_levels: int
) -> None:
    mask = np.abs(diff_array) < identical_tolerance
    diff_array[mask] = 0.0
    if np.all(mask):
        return
    positive_cutoffs = np.percentile(
        diff_array[diff_array > 0.0], np.linspace(0, 100, n_levels + 1)
    )
    negative_cutoffs = np.percentile(
        diff_array[diff_array < 0.0], np.linspace(100, 0, n_levels + 1)
    )
    for level, interval in enumerate(
        positive_cutoffs[i : i + 2]
        for i in range(positive_cutoffs.shape[0] - 1)
    ):
        diff_array[
            (diff_array >= interval[0]) & (diff_array <= interval[1])
        ] = (level + 1)
    for level, interval in enumerate(
        negative_cutoffs[i : i + 2]
        for i in range(negative_cutoffs.shape[0] - 1)
    ):
        diff_array[
            (diff_array <= interval[0]) & (diff_array >= interval[1])
        ] = -(level + 1)


def _compute_grid_points(
    image: NpMatrix, n: float, window: Window = None
) -> Tuple[NpMatrix, NpMatrix]:
    if window is None:
        window = ((0, image.shape[0]), (0, image.shape[1]))
    x_coords = np.linspace(window[0][0], window[0][1], n + 2, dtype=int)[1:-1]
    y_coords = np.linspace(window[1][0], window[1][1], n + 2, dtype=int)[1:-1]
    return x_coords, y_coords


def _compute_mean_level(
    image: NpMatrix, x_coords: NpMatrix, y_coords: NpMatrix, p: Optional[float]
) -> NpMatrix:
    if p is None:
        p = max([2.0, int(0.5 + min(image.shape) / 20.0)])
    avg_grey = np.zeros((x_coords.shape[0], y_coords.shape[0]))
    for i, x in enumerate(x_coords):
        lower_x_lim = int(max([x - p / 2, 0]))
        upper_x_lim = int(min([lower_x_lim + p, image.shape[0]]))
        for j, y in enumerate(y_coords):
            lower_y_lim = int(max([y - p / 2, 0]))
            upper_y_lim = int(min([lower_y_lim + p, image.shape[1]]))
            avg_grey[i, j] = np.mean(
                image[lower_x_lim:upper_x_lim, lower_y_lim:upper_y_lim]
            )
    return avg_grey


def _compute_differentials(grey_level_matrix: NpMatrix) -> NpMatrix:
    flipped = np.fliplr(grey_level_matrix)
    right_neighbors = -np.concatenate(
        (
            np.diff(grey_level_matrix),
            (
                np.zeros(grey_level_matrix.shape[0]).reshape(
                    (grey_level_matrix.shape[0], 1)
                )
            ),
        ),
        axis=1,
    )
    down_neighbors = -np.concatenate(
        (
            np.diff(grey_level_matrix, axis=0),
            (
                np.zeros(grey_level_matrix.shape[1]).reshape(
                    (1, grey_level_matrix.shape[1])
                )
            ),
        )
    )
    left_neighbors = -np.concatenate(
        (right_neighbors[:, -1:], right_neighbors[:, :-1]), axis=1
    )
    up_neighbors = -np.concatenate((down_neighbors[-1:], down_neighbors[:-1]))
    diagonals = np.arange(
        -grey_level_matrix.shape[0] + 1, grey_level_matrix.shape[0]
    )
    upper_left_neighbors = sum(
        [
            np.diagflat(
                np.insert(np.diff(np.diag(grey_level_matrix, i)), 0, 0), i
            )
            for i in diagonals
        ]
    )
    upper_right_neighbors = sum(
        [
            np.diagflat(np.insert(np.diff(np.diag(flipped, i)), 0, 0), i)
            for i in diagonals
        ]
    )
    lower_right_neighbors = -np.pad(
        upper_left_neighbors[1:, 1:], (0, 1), mode="constant"
    )
    lower_left_neighbors = -np.pad(
        upper_right_neighbors[1:, 1:], (0, 1), mode="constant"
    )
    return np.dstack(
        np.array(
            [
                upper_left_neighbors,
                up_neighbors,
                np.fliplr(upper_right_neighbors),
                left_neighbors,
                right_neighbors,
                np.fliplr(lower_left_neighbors),
                down_neighbors,
                lower_right_neighbors,
            ]
        )
    )


def _words_to_int(word_array: NpMatrix) -> List[int]:
    width = word_array.shape[1]
    coding_vector = 3 ** np.arange(width)
    return np.dot(word_array + 1, coding_vector).astype(int).tolist()


def _get_words(array: NpMatrix, k: int, n: int) -> NpMatrix:
    word_positions = np.linspace(0, array.shape[0], n, endpoint=False).astype(
        "int"
    )
    assert k <= array.shape[0]
    assert word_positions.shape[0] <= array.shape[0]
    words = np.zeros((n, k)).astype("int8")
    for i, pos in enumerate(word_positions):
        if pos + k <= array.shape[0]:
            words[i] = array[pos : pos + k]
        else:
            temp = array[pos:].copy()
            temp.resize(k, refcheck=False)
            words[i] = temp
    words[words > 0] = 1
    words[words < 0] = -1
    return words


def generate_signature(content: bytes) -> NpMatrix:
    im_array = _preprocess_image(content)
    image_limits = _crop_image(
        im_array,
        lower_percentile=LOWER_PERCENTILE,
        upper_percentile=UPPER_PERCENTILE,
    )
    x_coords, y_coords = _compute_grid_points(
        im_array, n=N, window=image_limits
    )
    avg_grey = _compute_mean_level(im_array, x_coords, y_coords, p=P)
    diff_matrix = _compute_differentials(avg_grey)
    _normalize_and_threshold(
        diff_matrix, identical_tolerance=IDENTICAL_TOLERANCE, n_levels=N_LEVELS
    )
    return np.ravel(diff_matrix).astype("int8")


def generate_words(signature: NpMatrix) -> List[int]:
    return _words_to_int(_get_words(signature, k=SAMPLE_WORDS, n=MAX_WORDS))


def normalized_distance(
    target_array: Any, vec: NpMatrix, nan_value: float = 1.0
) -> List[float]:
    target_array = np.array(target_array).astype(int)
    vec = vec.astype(int)
    topvec = np.linalg.norm(vec - target_array, axis=1)
    norm1 = np.linalg.norm(vec, axis=0)
    norm2 = np.linalg.norm(target_array, axis=1)
    finvec = topvec / (norm1 + norm2)
    finvec[np.isnan(finvec)] = nan_value
    return finvec


def pack_signature(signature: NpMatrix) -> bytes:
    """
    Serializes the signature vector for efficient storage in a database.

    Shifts the range of the signature vector from [-N_LEVELS,+N_LEVELS]
    to [0, base]

    The vector can then be broken up into chunks, with each chunk
    consisting of SIG_CHUNK_WIDTH digits of radix `base`.

    This is then converted into a more packed array consisting of
    uint32 elements (for SIG_CHUNK_BITS = 32).
    """
    coding_vector = np.flipud(SIG_BASE ** np.arange(SIG_CHUNK_WIDTH))
    return (
        np.array(
            [
                np.dot(x, coding_vector)
                for x in np.reshape(
                    signature + N_LEVELS, (-1, SIG_CHUNK_WIDTH)
                )
            ]
        )
        .astype(f"uint{SIG_CHUNK_BITS}")
        .tobytes()
    )


def unpack_signature(packed: bytes) -> NpMatrix:
    """
    Deserializes the signature vector once recieved from the database.

    Functions as an inverse transformation of pack_signature()
    """
    return np.ravel(
        np.array(
            [
                [
                    int(digit) - N_LEVELS
                    for digit in np.base_repr(e, base=SIG_BASE).zfill(
                        SIG_CHUNK_WIDTH
                    )
                ]
                for e in np.frombuffer(packed, dtype=f"uint{SIG_CHUNK_BITS}")
            ]
        ).astype("int8")
    )
