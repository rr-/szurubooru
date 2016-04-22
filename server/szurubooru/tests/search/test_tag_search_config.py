import datetime
import pytest
from szurubooru import db, errors, search

@pytest.fixture
def executor():
    search_config = search.TagSearchConfig()
    return search.SearchExecutor(search_config)

@pytest.fixture
def verify_unpaged(executor):
    def verify(input, expected_tag_names):
        actual_count, actual_tags = executor.execute(
            input, page=1, page_size=100)
        actual_tag_names = [u.names[0].name for u in actual_tags]
        assert actual_count == len(expected_tag_names)
        assert actual_tag_names == expected_tag_names
    return verify

@pytest.mark.parametrize('input,expected_tag_names', [
    ('creation-time:2014', ['t1', 't2']),
    ('creation-date:2014', ['t1', 't2']),
    ('-creation-time:2014', ['t3']),
    ('-creation-date:2014', ['t3']),
    ('creation-time:2014..2014-06', ['t1', 't2']),
    ('creation-time:2014-06..2015-01-01', ['t2', 't3']),
    ('creation-time:2014-06..', ['t2', 't3']),
    ('creation-time:..2014-06', ['t1', 't2']),
    ('-creation-time:2014..2014-06', ['t3']),
    ('-creation-time:2014-06..2015-01-01', ['t1']),
    ('creation-date:2014..2014-06', ['t1', 't2']),
    ('creation-date:2014-06..2015-01-01', ['t2', 't3']),
    ('creation-date:2014-06..', ['t2', 't3']),
    ('creation-date:..2014-06', ['t1', 't2']),
    ('-creation-date:2014..2014-06', ['t3']),
    ('-creation-date:2014-06..2015-01-01', ['t1']),
    ('creation-time:2014-01,2015', ['t1', 't3']),
    ('creation-date:2014-01,2015', ['t1', 't3']),
    ('-creation-time:2014-01,2015', ['t2']),
    ('-creation-date:2014-01,2015', ['t2']),
])
def test_filter_by_creation_time(
        verify_unpaged, tag_factory, input, expected_tag_names):
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    tag3 = tag_factory(names=['t3'])
    tag1.creation_time = datetime.datetime(2014, 1, 1)
    tag2.creation_time = datetime.datetime(2014, 6, 1)
    tag3.creation_time = datetime.datetime(2015, 1, 1)
    db.session.add_all([tag1, tag2, tag3])
    verify_unpaged(input, expected_tag_names)

@pytest.mark.parametrize('input,expected_tag_names', [
    ('name:tag1', ['tag1']),
    ('name:tag2', ['tag2']),
    ('name:none', []),
    ('name:', []),
    ('name:*1', ['tag1']),
    ('name:*2', ['tag2']),
    ('name:*', ['tag1', 'tag2', 'tag3', 'tag4']),
    ('name:t*', ['tag1', 'tag2', 'tag3', 'tag4']),
    ('name:*a*', ['tag1', 'tag2', 'tag3', 'tag4']),
    ('name:*!*', []),
    ('name:!*', []),
    ('name:*!', []),
    ('-name:tag1', ['tag2', 'tag3', 'tag4']),
    ('-name:tag2', ['tag1', 'tag3', 'tag4']),
    ('name:tag1,tag2', ['tag1', 'tag2']),
    ('-name:tag1,tag3', ['tag2', 'tag4']),
    ('name:tag4', ['tag4']),
    ('name:tag4,tag5', ['tag4']),
])
def test_filter_by_name(verify_unpaged, tag_factory, input, expected_tag_names):
    db.session.add(tag_factory(names=['tag1']))
    db.session.add(tag_factory(names=['tag2']))
    db.session.add(tag_factory(names=['tag3']))
    db.session.add(tag_factory(names=['tag4', 'tag5', 'tag6']))
    verify_unpaged(input, expected_tag_names)

@pytest.mark.parametrize('input,expected_tag_names', [
    ('', ['t1', 't2']),
    ('t1', ['t1']),
    ('t2', ['t2']),
    ('t1,t2', ['t1', 't2']),
])
def test_anonymous(verify_unpaged, tag_factory, input, expected_tag_names):
    db.session.add(tag_factory(names=['t1']))
    db.session.add(tag_factory(names=['t2']))
    verify_unpaged(input, expected_tag_names)

@pytest.mark.parametrize('input,expected_tag_names', [
    ('', ['t1', 't2']),
    ('sort:name', ['t1', 't2']),
    ('-sort:name', ['t2', 't1']),
    ('sort:name,asc', ['t1', 't2']),
    ('sort:name,desc', ['t2', 't1']),
    ('-sort:name,asc', ['t2', 't1']),
    ('-sort:name,desc', ['t1', 't2']),
])
def test_sort_by_name(verify_unpaged, tag_factory, input, expected_tag_names):
    db.session.add(tag_factory(names=['t2']))
    db.session.add(tag_factory(names=['t1']))
    verify_unpaged(input, expected_tag_names)

@pytest.mark.parametrize('input,expected_user_names', [
    ('', ['t1', 't2', 't3']),
    ('sort:creation-date', ['t3', 't2', 't1']),
    ('sort:creation-time', ['t3', 't2', 't1']),
])
def test_sort_by_creation_time(
        verify_unpaged, tag_factory, input, expected_user_names):
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    tag3 = tag_factory(names=['t3'])
    tag1.creation_time = datetime.datetime(1991, 1, 1)
    tag2.creation_time = datetime.datetime(1991, 1, 2)
    tag3.creation_time = datetime.datetime(1991, 1, 3)
    db.session.add_all([tag3, tag1, tag2])
    verify_unpaged(input, expected_user_names)

@pytest.mark.parametrize('input,expected_tag_names', [
    ('sort:suggestion-count', ['t1', 't2', 'sug1', 'sug2', 'sug3']),
])
def test_sort_by_suggestion_count(
        verify_unpaged, tag_factory, input, expected_tag_names):
    sug1 = tag_factory(names=['sug1'])
    sug2 = tag_factory(names=['sug2'])
    sug3 = tag_factory(names=['sug3'])
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    db.session.add_all([sug1, sug3, tag2, sug2, tag1])
    db.session.commit()
    tag1.suggestions.append(sug1)
    tag1.suggestions.append(sug2)
    tag2.suggestions.append(sug3)
    verify_unpaged(input, expected_tag_names)

@pytest.mark.parametrize('input,expected_tag_names', [
    ('sort:implication-count', ['t1', 't2', 'sug1', 'sug2', 'sug3']),
])
def test_sort_by_implication_count(
        verify_unpaged, tag_factory, input, expected_tag_names):
    sug1 = tag_factory(names=['sug1'])
    sug2 = tag_factory(names=['sug2'])
    sug3 = tag_factory(names=['sug3'])
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    db.session.add_all([sug1, sug3, tag2, sug2, tag1])
    db.session.commit()
    tag1.implications.append(sug1)
    tag1.implications.append(sug2)
    tag2.implications.append(sug3)
    verify_unpaged(input, expected_tag_names)

def test_filter_by_relation_count(verify_unpaged, tag_factory):
    sug1 = tag_factory(names=['sug1'])
    sug2 = tag_factory(names=['sug2'])
    imp1 = tag_factory(names=['imp1'])
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    db.session.add_all([sug1, tag1, sug2, imp1, tag2])
    db.session.commit()
    db.session.add_all([
        db.TagSuggestion(tag1.tag_id, sug1.tag_id),
        db.TagSuggestion(tag1.tag_id, sug2.tag_id),
        db.TagImplication(tag2.tag_id, imp1.tag_id)])
    db.session.commit()
    verify_unpaged('suggestion-count:0', ['imp1', 'sug1', 'sug2', 't2'])
    verify_unpaged('suggestion-count:1', [])
    verify_unpaged('suggestion-count:2', ['t1'])
    verify_unpaged('implication-count:0', ['imp1', 'sug1', 'sug2', 't1'])
    verify_unpaged('implication-count:1', ['t2'])
    verify_unpaged('implication-count:2', [])
