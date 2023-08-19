from datetime import datetime
from unittest.mock import patch

import pytest  # noqa: F401

from szurubooru import db, model
from szurubooru.func import snapshots, users


def test_get_tag_category_snapshot(tag_category_factory):
    category = tag_category_factory(name="name", color="color")
    assert snapshots.get_tag_category_snapshot(category) == {
        "name": "name",
        "color": "color",
        "default": False,
    }
    category.default = True
    assert snapshots.get_tag_category_snapshot(category) == {
        "name": "name",
        "color": "color",
        "default": True,
    }


def test_get_tag_snapshot(tag_factory, tag_category_factory):
    category = tag_category_factory(name="dummy")
    tag = tag_factory(names=["main_name", "alias"], category=category)
    assert snapshots.get_tag_snapshot(tag) == {
        "names": ["main_name", "alias"],
        "category": "dummy",
        "suggestions": [],
        "implications": [],
    }
    tag = tag_factory(names=["main_name", "alias"], category=category)
    imp1 = tag_factory(names=["imp1_main_name", "imp1_alias"])
    imp2 = tag_factory(names=["imp2_main_name", "imp2_alias"])
    sug1 = tag_factory(names=["sug1_main_name", "sug1_alias"])
    sug2 = tag_factory(names=["sug2_main_name", "sug2_alias"])
    db.session.add_all([imp1, imp2, sug1, sug2])
    tag.implications = [imp1, imp2]
    tag.suggestions = [sug1, sug2]
    db.session.flush()
    assert snapshots.get_tag_snapshot(tag) == {
        "names": ["main_name", "alias"],
        "category": "dummy",
        "implications": ["imp1_main_name", "imp2_main_name"],
        "suggestions": ["sug1_main_name", "sug2_main_name"],
    }


def test_get_post_snapshot(post_factory, user_factory, tag_factory):
    user = user_factory(name="dummy-user")
    tag1 = tag_factory(names=["dummy-tag1"])
    tag2 = tag_factory(names=["dummy-tag2"])
    post = post_factory(id=1)
    related_post1 = post_factory(id=2)
    related_post2 = post_factory(id=3)
    db.session.add_all([user, tag1, tag2, post, related_post1, related_post2])
    db.session.flush()

    score = model.PostScore()
    score.post = post
    score.user = user
    score.time = datetime(1997, 1, 1)
    score.score = 1
    favorite = model.PostFavorite()
    favorite.post = post
    favorite.user = user
    favorite.time = datetime(1997, 1, 1)
    feature = model.PostFeature()
    feature.post = post
    feature.user = user
    feature.time = datetime(1997, 1, 1)
    note = model.PostNote()
    note.post = post
    note.polygon = [(1, 1), (200, 1), (200, 200), (1, 200)]
    note.text = "some text"
    db.session.add_all([score])
    db.session.flush()

    post.user = user
    post.checksum = "deadbeef"
    post.source = "example.com"
    post.tags.append(tag1)
    post.tags.append(tag2)
    post.relations.append(related_post1)
    post.relations.append(related_post2)
    post.scores.append(score)
    post.favorited_by.append(favorite)
    post.features.append(feature)
    post.notes.append(note)

    assert snapshots.get_post_snapshot(post) == {
        "checksum": "deadbeef",
        "featured": True,
        "flags": [],
        "notes": [
            {
                "polygon": [[1, 1], [200, 1], [200, 200], [1, 200]],
                "text": "some text",
            }
        ],
        "relations": [2, 3],
        "safety": "safe",
        "source": "example.com",
        "tags": ["dummy-tag1", "dummy-tag2"],
    }


def test_serialize_snapshot(user_factory):
    auth_user = user_factory()
    snapshot = model.Snapshot()
    snapshot.operation = snapshot.OPERATION_CREATED
    snapshot.resource_type = "type"
    snapshot.resource_name = "id"
    snapshot.user = user_factory(name="issuer")
    snapshot.data = {"complex": list("object")}
    snapshot.creation_time = datetime(1997, 1, 1)
    with patch("szurubooru.func.users.serialize_micro_user"):
        users.serialize_micro_user.return_value = "mocked"
        assert snapshots.serialize_snapshot(snapshot, auth_user) == {
            "operation": "created",
            "type": "type",
            "id": "id",
            "user": "mocked",
            "data": {"complex": list("object")},
            "time": datetime(1997, 1, 1),
        }


def test_create(tag_factory, user_factory):
    tag = tag_factory(names=["dummy"])
    db.session.add(tag)
    db.session.flush()
    with patch("szurubooru.func.snapshots.get_tag_snapshot"), patch(
        "szurubooru.func.snapshots._post_to_webhooks"
    ):
        snapshots.get_tag_snapshot.return_value = "mocked"
        snapshots.create(tag, user_factory())
    db.session.flush()
    results = db.session.query(model.Snapshot).all()
    assert len(results) == 1
    assert results[0].operation == model.Snapshot.OPERATION_CREATED
    assert results[0].data == "mocked"


def test_modify_doesnt_save_empty_diffs(tag_factory, user_factory):
    tag = tag_factory(names=["dummy"])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.commit()
    snapshots.modify(tag, user)
    db.session.flush()
    assert db.session.query(model.Snapshot).count() == 0


def test_delete(tag_factory, user_factory):
    tag = tag_factory(names=["dummy"])
    db.session.add(tag)
    db.session.flush()
    with patch("szurubooru.func.snapshots.get_tag_snapshot"), patch(
        "szurubooru.func.snapshots._post_to_webhooks"
    ):
        snapshots.get_tag_snapshot.return_value = "mocked"
        snapshots.delete(tag, user_factory())
    db.session.flush()
    results = db.session.query(model.Snapshot).all()
    assert len(results) == 1
    assert results[0].operation == model.Snapshot.OPERATION_DELETED
    assert results[0].data == "mocked"


def test_merge(tag_factory, user_factory):
    source_tag = tag_factory(names=["source"])
    target_tag = tag_factory(names=["target"])
    db.session.add_all([source_tag, target_tag])
    db.session.flush()
    with patch("szurubooru.func.snapshots._post_to_webhooks"):
        snapshots.merge(source_tag, target_tag, user_factory())
        db.session.flush()
        result = db.session.query(model.Snapshot).one()
        assert result.operation == model.Snapshot.OPERATION_MERGED
        assert result.data == ["tag", "target"]
