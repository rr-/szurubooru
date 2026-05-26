from datetime import datetime

from szurubooru import api, db, model


def test_info_api(
    tmpdir,
    config_injector,
    context_factory,
    post_factory,
    user_factory,
    fake_datetime,
):
    directory = tmpdir.mkdir("data")
    directory.join("test.txt").write("abc")
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    anon_user = user_factory(rank=model.User.RANK_ANONYMOUS)
    config_injector(
        {
            "name": "test installation",
            "contact_email": "test@example.com",
            "enable_safety": True,
            "data_dir": str(directory),
            "user_name_regex": "1",
            "password_regex": "2",
            "tag_name_regex": "3",
            "tag_category_name_regex": "4",
            "default_rank": "5",
            "default_tag_blocklist": "testTag",
            "default_tag_blocklist_for_anonymous": True,
            "privileges": {
                "test_key1": "test_value1",
                "test_key2": "test_value2",
                "posts:view:featured": "regular",
            },
            "smtp": {
                "host": "example.com",
            },
        }
    )
    db.session.add_all([post_factory(), post_factory()])
    db.session.flush()

    expected_config_key = {
        "name": "test installation",
        "contactEmail": "test@example.com",
        "enableSafety": True,
        "userNameRegex": "1",
        "passwordRegex": "2",
        "tagNameRegex": "3",
        "tagCategoryNameRegex": "4",
        "defaultUserRank": "5",
        "defaultTagBlocklist": "testTag",
        "defaultTagBlocklistForAnonymous": True,
        "privileges": {
            "testKey1": "test_value1",
            "testKey2": "test_value2",
            "posts:view:featured": "regular",
        },
        "canSendMails": True,
    }

    with fake_datetime("2016-01-01 13:00"):
        assert api.info_api.get_info(context_factory(user=auth_user)) == {
            "postCount": 2,
            "diskUsage": 3,
            "featuredPost": None,
            "featuringTime": None,
            "featuringUser": None,
            "serverTime": datetime(2016, 1, 1, 13, 0),
            "config": expected_config_key,
        }
    directory.join("test2.txt").write("abc")
    with fake_datetime("2016-01-03 12:59"):
        assert api.info_api.get_info(context_factory(user=auth_user)) == {
            "postCount": 2,
            "diskUsage": 3,  # still 3 - it's cached
            "featuredPost": None,
            "featuringTime": None,
            "featuringUser": None,
            "serverTime": datetime(2016, 1, 3, 12, 59),
            "config": expected_config_key,
        }
    with fake_datetime("2016-01-03 13:01"):
        assert api.info_api.get_info(context_factory(user=auth_user)) == {
            "postCount": 2,
            "diskUsage": 6,  # cache expired
            "featuredPost": None,
            "featuringTime": None,
            "featuringUser": None,
            "serverTime": datetime(2016, 1, 3, 13, 1),
            "config": expected_config_key,
        }
    with fake_datetime("2016-01-03 13:01"):
        assert api.info_api.get_info(context_factory(user=anon_user)) == {
            "postCount": 2,
            "diskUsage": 6,  # cache expired
            "serverTime": datetime(2016, 1, 3, 13, 1),
            "config": expected_config_key,
        }
