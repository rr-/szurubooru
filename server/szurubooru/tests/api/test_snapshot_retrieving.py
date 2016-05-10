import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, tags

def snapshot_factory():
    snapshot = db.Snapshot()
    snapshot.creation_time = datetime.datetime(1999, 1, 1)
    snapshot.resource_type = 'dummy'
    snapshot.resource_id = 1
    snapshot.resource_repr = 'dummy'
    snapshot.operation = 'added'
    snapshot.data = '{}'
    return snapshot

@pytest.fixture
def test_ctx(context_factory, config_injector, user_factory):
    config_injector({
        'privileges': {
            'snapshots:list': db.User.RANK_REGULAR,
        },
        'thumbnails': {'avatar_width': 200},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.SnapshotListApi()
    return ret

def test_retrieving_multiple(test_ctx):
    snapshot1 = snapshot_factory()
    snapshot2 = snapshot_factory()
    db.session.add_all([snapshot1, snapshot2])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert len(result['results']) == 2

def test_trying_to_retrieve_multiple_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))
