from unittest.mock import patch

import pytest

from szurubooru import db, model
from szurubooru.func import snapshots


@pytest.fixture(autouse=True)
def session(query_logger, postgresql_db):
    """
    Override db session for this specific test section only
    """
    db.session = postgresql_db.session
    postgresql_db.create_table(*model.Base.metadata.sorted_tables)
    try:
        yield postgresql_db.session
    finally:
        postgresql_db.reset_db()


def test_modify_saves_non_empty_diffs(post_factory, user_factory):
    if "sqlite" in db.session.get_bind().driver:
        pytest.xfail(
            "SQLite doesn't support transaction isolation, "
            "which is required to retrieve original entity"
        )
    post = post_factory()
    post.notes = [model.PostNote(polygon=[(0, 0), (0, 1), (1, 1)], text="old")]
    user = user_factory()
    db.session.add_all([post, user])
    db.session.commit()
    post.source = "new source"
    post.notes = [model.PostNote(polygon=[(0, 0), (0, 1), (1, 1)], text="new")]
    db.session.flush()
    with patch("szurubooru.func.snapshots._post_to_webhooks"):
        snapshots.modify(post, user)
        db.session.flush()
        results = db.session.query(model.Snapshot).all()
        assert len(results) == 1
        assert results[0].data == {
            "type": "object change",
            "value": {
                "source": {
                    "type": "primitive change",
                    "old-value": None,
                    "new-value": "new source",
                },
                "notes": {
                    "type": "list change",
                    "removed": [
                        {"polygon": [[0, 0], [0, 1], [1, 1]], "text": "old"}
                    ],
                    "added": [
                        {"polygon": [[0, 0], [0, 1], [1, 1]], "text": "new"}
                    ],
                },
            },
        }
