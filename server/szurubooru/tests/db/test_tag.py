from datetime import datetime
from szurubooru import db

def test_saving_tag(session, tag_factory):
    suggested_tag1 = tag_factory(names=['suggested1'])
    suggested_tag2 = tag_factory(names=['suggested2'])
    implied_tag1 = tag_factory(names=['implied1'])
    implied_tag2 = tag_factory(names=['implied2'])
    tag = db.Tag()
    tag.names = [db.TagName('alias1'), db.TagName('alias2')]
    tag.suggestions = []
    tag.implications = []
    tag.category = 'category'
    tag.creation_time = datetime(1997, 1, 1)
    tag.last_edit_time = datetime(1998, 1, 1)
    session.add_all([
        tag, suggested_tag1, suggested_tag2, implied_tag1, implied_tag2])
    session.commit()

    assert tag.tag_id is not None
    assert suggested_tag1.tag_id is not None
    assert suggested_tag2.tag_id is not None
    assert implied_tag1.tag_id is not None
    assert implied_tag2.tag_id is not None
    tag.suggestions.append(suggested_tag1)
    tag.suggestions.append(suggested_tag2)
    tag.implications.append(implied_tag1)
    tag.implications.append(implied_tag2)
    session.commit()

    tag = session.query(db.Tag) \
        .join(db.TagName) \
        .filter(db.TagName.name=='alias1') \
        .one()
    assert [tag_name.name for tag_name in tag.names] == ['alias1', 'alias2']
    assert tag.category == 'category'
    assert tag.creation_time == datetime(1997, 1, 1)
    assert tag.last_edit_time == datetime(1998, 1, 1)
    assert [relation.names[0].name for relation in tag.suggestions] \
        == ['suggested1', 'suggested2']
    assert [relation.names[0].name for relation in tag.implications] \
        == ['implied1', 'implied2']

def test_cascade_deletions(session, tag_factory):
    suggested_tag1 = tag_factory(names=['suggested1'])
    suggested_tag2 = tag_factory(names=['suggested2'])
    implied_tag1 = tag_factory(names=['implied1'])
    implied_tag2 = tag_factory(names=['implied2'])
    tag = db.Tag()
    tag.names = [db.TagName('alias1'), db.TagName('alias2')]
    tag.suggestions = []
    tag.implications = []
    tag.category = 'category'
    tag.creation_time = datetime(1997, 1, 1)
    tag.last_edit_time = datetime(1998, 1, 1)
    tag.post_count = 1
    session.add_all([
        tag, suggested_tag1, suggested_tag2, implied_tag1, implied_tag2])
    session.commit()

    assert tag.tag_id is not None
    assert suggested_tag1.tag_id is not None
    assert suggested_tag2.tag_id is not None
    assert implied_tag1.tag_id is not None
    assert implied_tag2.tag_id is not None
    tag.suggestions.append(suggested_tag1)
    tag.suggestions.append(suggested_tag2)
    tag.implications.append(implied_tag1)
    tag.implications.append(implied_tag2)
    session.commit()

    session.delete(tag)
    session.commit()
    assert session.query(db.Tag).count() == 4
    assert session.query(db.TagName).count() == 4
    assert session.query(db.TagImplication).count() == 0
    assert session.query(db.TagSuggestion).count() == 0

def test_tracking_post_count(session, post_factory, tag_factory):
    tag = tag_factory()
    post1 = post_factory()
    post2 = post_factory()
    session.add_all([tag, post1, post2])
    session.flush()
    post1.tags.append(tag)
    post2.tags.append(tag)
    session.commit()
    assert len(post1.tags) == 1
    assert len(post2.tags) == 1
    assert tag.post_count == 2
    session.delete(post1)
    session.commit()
    session.refresh(tag)
    assert tag.post_count == 1
    session.delete(post2)
    session.commit()
    session.refresh(tag)
    assert tag.post_count == 0
