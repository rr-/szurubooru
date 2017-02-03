import logging
import elasticsearch
import elasticsearch_dsl
import xml.etree
from image_match.elasticsearch_driver import SignatureES
from szurubooru import config, errors


# pylint: disable=invalid-name
logger = logging.getLogger(__name__)
es = elasticsearch.Elasticsearch([{
    'host': config.config['elasticsearch']['host'],
    'port': config.config['elasticsearch']['port'],
}])


def _get_session():
    return SignatureES(es, index=config.config['elasticsearch']['index'])


def _safe_blanket(default_param_factory):
    def wrapper_outer(target_function):
        def wrapper_inner(*args, **kwargs):
            try:
                return target_function(*args, **kwargs)
            except elasticsearch.exceptions.NotFoundError:
                # index not yet created, will be created dynamically by
                # add_image()
                return default_param_factory()
            except elasticsearch.exceptions.ElasticsearchException as ex:
                logger.warning('Problem with elastic search: %s' % ex)
                raise errors.ThirdPartyError(
                    'Error connecting to elastic search.')
            except xml.etree.ElementTree.ParseError as ex:
                # image-match issue #60
                raise errors.ProcessingError('Not an image.')
            except Exception as ex:
                raise errors.ThirdPartyError('Unknown error (%s).' % ex)
        return wrapper_inner
    return wrapper_outer


class Lookalike:
    def __init__(self, score, distance, path):
        self.score = score
        self.distance = distance
        self.path = path


@_safe_blanket(lambda: None)
def add_image(path, image_content):
    if not path or not image_content:
        return
    session = _get_session()
    session.add_image(path=path, img=image_content, bytestream=True)


@_safe_blanket(lambda: None)
def delete_image(path):
    if not path:
        return
    session = _get_session()
    es.delete_by_query(
        index=session.index,
        doc_type=session.doc_type,
        body={'query': {'term': {'path': path}}})


@_safe_blanket(lambda: [])
def search_by_image(image_content):
    ret = []
    session = _get_session()
    for result in session.search_image(
            path=image_content,  # sic
            bytestream=True):
        ret.append(Lookalike(
            score=result['score'],
            distance=result['dist'],
            path=result['path']))
    return ret


@_safe_blanket(lambda: None)
def purge():
    session = _get_session()
    es.delete_by_query(
        index=session.index,
        doc_type=session.doc_type,
        body={'query': {'match_all': {}}})


@_safe_blanket(lambda: set())
def get_all_paths():
    session = _get_session()
    search = (
        elasticsearch_dsl.Search(
            using=es, index=session.index, doc_type=session.doc_type)
        .source(['path']))
    return set(h.path for h in search.scan())
