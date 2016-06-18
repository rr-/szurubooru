from datetime import datetime
from szurubooru import api, db

def test_info_api(
        tmpdir, config_injector, context_factory, post_factory, fake_datetime):
    directory = tmpdir.mkdir('data')
    directory.join('test.txt').write('abc')
    config_injector({
        'data_dir': str(directory),
        'user_name_regex': '1',
        'password_regex': '2',
        'tag_name_regex': '3',
        'tag_category_name_regex': '4',
        'default_rank': '5',
        'privileges': {
            'test_key1': 'test_value1',
            'test_key2': 'test_value2',
        },
    })
    db.session.add_all([post_factory(), post_factory()])

    expected_config_key = {
        'userNameRegex': '1',
        'passwordRegex': '2',
        'tagNameRegex': '3',
        'tagCategoryNameRegex': '4',
        'defaultUserRank': '5',
        'privileges': {
            'testKey1': 'test_value1',
            'testKey2': 'test_value2',
        },
    }

    info_api = api.InfoApi()
    with fake_datetime('2016-01-01 13:00'):
        assert info_api.get(context_factory()) == {
            'postCount': 2,
            'diskUsage': 3,
            'featuredPost': None,
            'featuringTime': None,
            'featuringUser': None,
            'serverTime': datetime(2016, 1, 1, 13, 0),
            'config': expected_config_key,
        }
    directory.join('test2.txt').write('abc')
    with fake_datetime('2016-01-01 13:59'):
        assert info_api.get(context_factory()) == {
            'postCount': 2,
            'diskUsage': 3, # still 3 - it's cached
            'featuredPost': None,
            'featuringTime': None,
            'featuringUser': None,
            'serverTime': datetime(2016, 1, 1, 13, 59),
            'config': expected_config_key,
        }
    with fake_datetime('2016-01-01 14:01'):
        assert info_api.get(context_factory()) == {
            'postCount': 2,
            'diskUsage': 6, # cache expired
            'featuredPost': None,
            'featuringTime': None,
            'featuringUser': None,
            'serverTime': datetime(2016, 1, 1, 14, 1),
            'config': expected_config_key,
        }
