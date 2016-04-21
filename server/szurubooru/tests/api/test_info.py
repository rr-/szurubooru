from szurubooru import api, db

def test_info_api(
        tmpdir, config_injector, context_factory, post_factory, fake_datetime):
    directory = tmpdir.mkdir('data')
    directory.join('test.txt').write('abc')
    config_injector({'data_dir': str(directory)})
    db.session.add_all([post_factory(), post_factory()])
    info_api = api.InfoApi()
    with fake_datetime('13:00'):
        assert info_api.get(context_factory()) == {
            'postCount': 2,
            'diskUsage': 3,
        }
    directory.join('test2.txt').write('abc')
    with fake_datetime('13:59'):
        assert info_api.get(context_factory()) == {
            'postCount': 2,
            'diskUsage': 3, # still 3 - it's cached
        }
    with fake_datetime('14:01'):
        assert info_api.get(context_factory()) == {
            'postCount': 2,
            'diskUsage': 6, # cache expired
        }
