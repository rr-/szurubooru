import pytest
from szurubooru.func import image_hash


def test_hashing(read_asset, config_injector):
    config_injector({
        'elasticsearch': {
            'host': 'localhost',
            'port': 9200,
            'index': 'szurubooru_test',
            'user': 'szurubooru',
            'pass': None,
        },
    })

    if not image_hash.get_session().ping():
        pytest.xfail(
            'Unable to connect to ElasticSearch, '
            'perhaps it is not available for this test?')

    image_hash.purge()
    image_hash.add_image('test', read_asset('jpeg.jpg'))

    paths = image_hash.get_all_paths()
    results_exact = image_hash.search_by_image(read_asset('jpeg.jpg'))
    results_similar = image_hash.search_by_image(
        read_asset('jpeg-similar.jpg'))

    assert len(paths) == 1
    assert len(results_exact) == 1
    assert len(results_similar) == 1
    assert results_exact[0].path == 'test'
    assert results_exact[0].score == 63
    assert results_exact[0].distance == 0
    assert results_similar[0].path == 'test'
    assert results_similar[0].score == 17
    assert abs(results_similar[0].distance - 0.20599895341812172) < 1e-8
