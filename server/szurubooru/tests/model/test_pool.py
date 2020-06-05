from datetime import datetime

import pytest

from szurubooru import db, model


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {"delete_source_files": False, "secret": "secret", "data_dir": ""}
    )


def test_saving_pool(pool_factory, post_factory):
    post1 = post_factory()
    post2 = post_factory()
    pool = model.Pool()
    pool.names = [model.PoolName("alias1", 0), model.PoolName("alias2", 1)]
    pool.posts = []
    pool.category = model.PoolCategory("category")
    pool.creation_time = datetime(1997, 1, 1)
    pool.last_edit_time = datetime(1998, 1, 1)
    db.session.add_all([pool, post1, post2])
    db.session.commit()

    assert pool.pool_id is not None
    pool.posts.append(post1)
    pool.posts.append(post2)
    db.session.commit()

    pool = (
        db.session.query(model.Pool)
        .join(model.PoolName)
        .filter(model.PoolName.name == "alias1")
        .one()
    )
    assert [pool_name.name for pool_name in pool.names] == ["alias1", "alias2"]
    assert pool.category.name == "category"
    assert pool.creation_time == datetime(1997, 1, 1)
    assert pool.last_edit_time == datetime(1998, 1, 1)
    assert [post.post_id for post in pool.posts] == [1, 2]


def test_cascade_deletions(pool_factory, post_factory):
    post1 = post_factory()
    post2 = post_factory()
    pool = model.Pool()
    pool.names = [model.PoolName("alias1", 0), model.PoolName("alias2", 1)]
    pool.posts = []
    pool.category = model.PoolCategory("category")
    pool.creation_time = datetime(1997, 1, 1)
    pool.last_edit_time = datetime(1998, 1, 1)
    db.session.add_all([pool, post1, post2])
    db.session.commit()

    assert pool.pool_id is not None
    pool.posts.append(post1)
    pool.posts.append(post2)
    db.session.commit()

    db.session.delete(pool)
    db.session.commit()
    assert db.session.query(model.Pool).count() == 0
    assert db.session.query(model.PoolName).count() == 0
    assert db.session.query(model.PoolPost).count() == 0
    assert db.session.query(model.PoolCategory).count() == 1
    assert db.session.query(model.Post).count() == 2


def test_tracking_post_count(post_factory, pool_factory):
    pool1 = pool_factory()
    pool2 = pool_factory()
    post1 = post_factory()
    post2 = post_factory()
    db.session.add_all([pool1, pool2, post1, post2])
    db.session.flush()
    assert pool1.pool_id is not None
    assert pool2.pool_id is not None
    pool1.posts.append(post1)
    pool2.posts.append(post1)
    pool2.posts.append(post2)
    db.session.commit()
    assert len(post1.pools) == 2
    assert len(post2.pools) == 1
    assert pool1.post_count == 1
    assert pool2.post_count == 2
    db.session.delete(post1)
    db.session.commit()
    db.session.refresh(pool1)
    db.session.refresh(pool2)
    assert pool1.post_count == 0
    assert pool2.post_count == 1
    db.session.delete(post2)
    db.session.commit()
    db.session.refresh(pool2)
    assert pool2.post_count == 0
