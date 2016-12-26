import elasticsearch
import elasticsearch_dsl
from image_match.elasticsearch_driver import SignatureES
from szurubooru import config, errors


# pylint: disable=invalid-name
es = elasticsearch.Elasticsearch([{
    'host': config.config['elasticsearch']['host'],
    'port': config.config['elasticsearch']['port'],
}])
session = SignatureES(es, index='szurubooru')


def add_image(path, image_content):
    if not path or not image_content:
        return
    session.add_image(path=path, img=image_content, bytestream=True)


def delete_image(path):
    if not path:
        return
    try:
        es.delete_by_query(
            index=session.index,
            doc_type=session.doc_type,
            body={'query': {'term': {'path': path}}})
    except elasticsearch.exceptions.NotFoundError:
        pass


def search_by_image(image_content):
    try:
        for result in session.search_image(
                path=image_content,  # sic
                bytestream=True):
            yield {
                'score': result['score'],
                'dist': result['dist'],
                'path': result['path'],
            }
    except elasticsearch.exceptions.ElasticsearchException as ex:
        raise
    except Exception as ex:
        raise errors.SearchError('Error searching (invalid input?)')


def purge():
    es.delete_by_query(
        index=session.index,
        doc_type=session.doc_type,
        body={'query': {'match_all': {}}})


def get_all_paths():
    try:
        search = (
            elasticsearch_dsl.Search(
                using=es, index=session.index, doc_type=session.doc_type)
            .source(['path']))
        return set(h.path for h in search.scan())
    except elasticsearch.exceptions.NotFoundError:
        return set()
