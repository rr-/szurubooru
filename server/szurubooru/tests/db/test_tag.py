from datetime import datetime
from szurubooru import db


def test_saving_tag(tag_factory):
    sug1 = tag_factory(names=['sug1'])
    sug2 = tag_factory(names=['sug2'])
    imp1 = tag_factory(names=['imp1'])
    imp2 = tag_factory(names=['imp2'])
    tag = db.Tag()
    tag.names = [db.TagName('alias1', 0), db.TagName('alias2', 1)]
    tag.suggestions = []
    tag.implications = []
    tag.category = db.TagCategory('category')
    tag.creation_time = datetime(1997, 1, 1)
    tag.last_edit_time = datetime(1998, 1, 1)
    db.session.add_all([tag, sug1, sug2, imp1, imp2])
    db.session.commit()

    assert tag.tag_id is not None
    assert sug1.tag_id is not None
    assert sug2.tag_id is not None
    assert imp1.tag_id is not None
    assert imp2.tag_id is not None
    tag.suggestions.append(sug1)
    tag.suggestions.append(sug2)
    tag.implications.append(imp1)
    tag.implications.append(imp2)
    db.session.commit()

    tag = db.session \
        .query(db.Tag) \
        .join(db.TagName) \
        .filter(db.TagName.name == 'alias1') \
        .one()
    assert [tag_name.name for tag_name in tag.names] == ['alias1', 'alias2']
    assert tag.category.name == 'category'
    assert tag.creation_time == datetime(1997, 1, 1)
    assert tag.last_edit_time == datetime(1998, 1, 1)
    assert [relation.names[0].name for relation in tag.suggestions] \
        == ['sug1', 'sug2']
    assert [relation.names[0].name for relation in tag.implications] \
        == ['imp1', 'imp2']


def test_cascade_deletions(tag_factory):
    sug1 = tag_factory(names=['sug1'])
    sug2 = tag_factory(names=['sug2'])
    imp1 = tag_factory(names=['imp1'])
    imp2 = tag_factory(names=['imp2'])
    tag = db.Tag()
    tag.names = [db.TagName('alias1', 0), db.TagName('alias2', 1)]
    tag.suggestions = []
    tag.implications = []
    tag.category = db.TagCategory('category')
    tag.creation_time = datetime(1997, 1, 1)
    tag.last_edit_time = datetime(1998, 1, 1)
    tag.post_count = 1
    db.session.add_all([tag, sug1, sug2, imp1, imp2])
    db.session.commit()

    assert tag.tag_id is not None
    assert sug1.tag_id is not None
    assert sug2.tag_id is not None
    assert imp1.tag_id is not None
    assert imp2.tag_id is not None
    tag.suggestions.append(sug1)
    tag.suggestions.append(sug2)
    tag.implications.append(imp1)
    tag.implications.append(imp2)
    db.session.commit()

    db.session.delete(tag)
    db.session.commit()
    assert db.session.query(db.Tag).count() == 4
    assert db.session.query(db.TagName).count() == 4
    assert db.session.query(db.TagImplication).count() == 0
    assert db.session.query(db.TagSuggestion).count() == 0


def test_tracking_post_count(post_factory, tag_factory):
    tag = tag_factory()
    post1 = post_factory()
    post2 = post_factory()
    db.session.add_all([tag, post1, post2])
    db.session.flush()
    post1.tags.append(tag)
    post2.tags.append(tag)
    db.session.commit()
    assert len(post1.tags) == 1
    assert len(post2.tags) == 1
    assert tag.post_count == 2
    db.session.delete(post1)
    db.session.commit()
    db.session.refresh(tag)
    assert tag.post_count == 1
    db.session.delete(post2)
    db.session.commit()
    db.session.refresh(tag)
    assert tag.post_count == 0
