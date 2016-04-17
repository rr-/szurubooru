from datetime import datetime
from szurubooru import db

def test_saving_post(session, post_factory, user_factory, tag_factory):
    user = user_factory()
    tag1 = tag_factory()
    tag2 = tag_factory()
    related_post1 = post_factory()
    related_post2 = post_factory()
    post = db.Post()
    post.safety = 'safety'
    post.type = 'type'
    post.checksum = 'deadbeef'
    post.creation_time = datetime(1997, 1, 1)
    post.last_edit_time = datetime(1998, 1, 1)
    session.add_all([user, tag1, tag2, related_post1, related_post2, post])

    post.user = user
    post.tags.append(tag1)
    post.tags.append(tag2)
    post.relations.append(related_post1)
    post.relations.append(related_post2)
    session.commit()

    post = session.query(db.Post).filter(db.Post.post_id == post.post_id).one()
    assert not session.dirty
    assert post.user.user_id is not None
    assert post.safety == 'safety'
    assert post.type == 'type'
    assert post.checksum == 'deadbeef'
    assert post.creation_time == datetime(1997, 1, 1)
    assert post.last_edit_time == datetime(1998, 1, 1)
    assert len(post.relations) == 2

def test_cascade_deletions(session, post_factory, user_factory, tag_factory):
    user = user_factory()
    tag1 = tag_factory()
    tag2 = tag_factory()
    related_post1 = post_factory()
    related_post2 = post_factory()
    post = post_factory()
    session.add_all([user, tag1, tag2, post, related_post1, related_post2])
    session.flush()

    post.user = user
    post.tags.append(tag1)
    post.tags.append(tag2)
    post.relations.append(related_post1)
    post.relations.append(related_post2)
    session.flush()

    assert not session.dirty
    assert post.user.user_id is not None
    assert len(post.relations) == 2
    assert session.query(db.User).count() == 1
    assert session.query(db.Tag).count() == 2
    assert session.query(db.Post).count() == 3
    assert session.query(db.PostTag).count() == 2
    assert session.query(db.PostRelation).count() == 2

    session.delete(post)
    session.commit()

    assert not session.dirty
    assert session.query(db.User).count() == 1
    assert session.query(db.Tag).count() == 2
    assert session.query(db.Post).count() == 2
    assert session.query(db.PostTag).count() == 0
    assert session.query(db.PostRelation).count() == 0

def test_tracking_tag_count(session, post_factory, tag_factory):
    post = post_factory()
    tag1 = tag_factory()
    tag2 = tag_factory()
    session.add_all([tag1, tag2, post])
    session.flush()
    post.tags.append(tag1)
    post.tags.append(tag2)
    session.commit()
    assert len(post.tags) == 2
    assert post.tag_count == 2
    session.delete(tag1)
    session.commit()
    session.refresh(post)
    assert len(post.tags) == 1
    assert post.tag_count == 1
    session.delete(tag2)
    session.commit()
    session.refresh(post)
    assert len(post.tags) == 0
    assert post.tag_count == 0
