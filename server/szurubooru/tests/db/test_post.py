from datetime import datetime
from szurubooru import db

def test_saving_post(post_factory, user_factory, tag_factory):
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
    post.mime_type = 'application/whatever'
    db.session.add_all([user, tag1, tag2, related_post1, related_post2, post])

    post.user = user
    post.tags.append(tag1)
    post.tags.append(tag2)
    post.relations.append(related_post1)
    post.relations.append(related_post2)
    db.session.commit()

    db.session.refresh(post)
    assert not db.session.dirty
    assert post.user.user_id is not None
    assert post.safety == 'safety'
    assert post.type == 'type'
    assert post.checksum == 'deadbeef'
    assert post.creation_time == datetime(1997, 1, 1)
    assert post.last_edit_time == datetime(1998, 1, 1)
    assert len(post.relations) == 2

def test_cascade_deletions(post_factory, user_factory, tag_factory):
    user = user_factory()
    tag1 = tag_factory()
    tag2 = tag_factory()
    related_post1 = post_factory()
    related_post2 = post_factory()
    post = post_factory()
    db.session.add_all([user, tag1, tag2, post, related_post1, related_post2])
    db.session.flush()

    score = db.PostScore()
    score.post = post
    score.user = user
    score.time = datetime(1997, 1, 1)
    score.score = 1
    favorite = db.PostFavorite()
    favorite.post = post
    favorite.user = user
    favorite.time = datetime(1997, 1, 1)
    feature = db.PostFeature()
    feature.post = post
    feature.user = user
    feature.time = datetime(1997, 1, 1)
    note = db.PostNote()
    note.post = post
    note.polygon = ''
    note.text = ''
    db.session.add_all([score, favorite, feature, note])
    db.session.flush()

    post.user = user
    post.tags.append(tag1)
    post.tags.append(tag2)
    post.relations.append(related_post1)
    post.relations.append(related_post2)
    post.scores.append(score)
    post.favorited_by.append(favorite)
    post.features.append(feature)
    post.notes.append(note)
    db.session.flush()

    assert not db.session.dirty
    assert post.user is not None and post.user.user_id is not None
    assert len(post.relations) == 2
    assert db.session.query(db.User).count() == 1
    assert db.session.query(db.Tag).count() == 2
    assert db.session.query(db.Post).count() == 3
    assert db.session.query(db.PostTag).count() == 2
    assert db.session.query(db.PostRelation).count() == 2
    assert db.session.query(db.PostScore).count() == 1
    assert db.session.query(db.PostNote).count() == 1
    assert db.session.query(db.PostFeature).count() == 1
    assert db.session.query(db.PostFavorite).count() == 1

    db.session.delete(post)
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(db.User).count() == 1
    assert db.session.query(db.Tag).count() == 2
    assert db.session.query(db.Post).count() == 2
    assert db.session.query(db.PostTag).count() == 0
    assert db.session.query(db.PostRelation).count() == 0
    assert db.session.query(db.PostScore).count() == 0
    assert db.session.query(db.PostNote).count() == 0
    assert db.session.query(db.PostFeature).count() == 0
    assert db.session.query(db.PostFavorite).count() == 0

def test_tracking_tag_count(post_factory, tag_factory):
    post = post_factory()
    tag1 = tag_factory()
    tag2 = tag_factory()
    db.session.add_all([tag1, tag2, post])
    db.session.flush()
    post.tags.append(tag1)
    post.tags.append(tag2)
    db.session.commit()
    assert len(post.tags) == 2
    assert post.tag_count == 2
    db.session.delete(tag1)
    db.session.commit()
    db.session.refresh(post)
    assert len(post.tags) == 1
    assert post.tag_count == 1
    db.session.delete(tag2)
    db.session.commit()
    db.session.refresh(post)
    assert len(post.tags) == 0
    assert post.tag_count == 0
