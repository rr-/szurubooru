import pytest
import unittest.mock
from szurubooru import api, db, errors
from szurubooru.func import posts, tags

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({'privileges': {'posts:delete': db.User.RANK_REGULAR}})

def test_deleting(user_factory, post_factory, context_factory):
    db.session.add(post_factory(id=1))
    db.session.commit()
    with unittest.mock.patch('szurubooru.func.tags.export_to_json'):
        result = api.post_api.delete_post(
            context_factory(
                params={'version': 1},
                user=user_factory(rank=db.User.RANK_REGULAR)),
            {'post_id': 1})
        assert result == {}
        assert db.session.query(db.Post).count() == 0
        tags.export_to_json.assert_called_once_with()

def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.delete_post(
            context_factory(user=user_factory(rank=db.User.RANK_REGULAR)),
            {'post_id': 999})

def test_trying_to_delete_without_privileges(
        user_factory, post_factory, context_factory):
    db.session.add(post_factory(id=1))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.post_api.delete_post(
            context_factory(user=user_factory(rank=db.User.RANK_ANONYMOUS)),
            {'post_id': 1})
    assert db.session.query(db.Post).count() == 1
