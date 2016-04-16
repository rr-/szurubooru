import datetime
import re
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.util import misc

class TagNotFoundError(errors.NotFoundError):
    def __init__(self, tag):
        super().__init__('Tag %r not found')

class TagAlreadyExistsError(errors.ValidationError):
    def __init__(self):
        super().__init__('One of names is already used by another tag.')

class InvalidNameError(errors.ValidationError):
    def __init__(self, message):
        super().__init__(message)

class InvalidCategoryError(errors.ValidationError):
    def __init__(self, category, valid_categories):
        super().__init__(
            'Category %r is invalid. Valid categories: %r.' % (
                category, valid_categories))

class RelationError(errors.ValidationError):
    def __init__(self, message):
        super().__init__(message)

def _verify_name_validity(name):
    name_regex = config.config['tag_name_regex']
    if not re.match(name_regex, name):
        raise InvalidNameError('Name must satisfy regex %r.' % name_regex)

def _get_plain_names(tag):
    return [tag_name.name for tag_name in tag.names]

def _lower_list(names):
    return [name.lower() for name in names]

def _check_name_intersection(names1, names2):
    return len(set(_lower_list(names1)).intersection(_lower_list(names2))) > 0

def get_by_name(session, name):
    return session.query(db.Tag) \
        .join(db.TagName) \
        .filter(db.TagName.name.ilike(name)) \
        .first()

def get_by_names(session, names):
    names = misc.icase_unique(names)
    if len(names) == 0:
        return []
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | db.TagName.name.ilike(name)
    return session.query(db.Tag).join(db.TagName).filter(expr).all()

def get_or_create_by_names(session, names):
    names = misc.icase_unique(names)
    for name in names:
        _verify_name_validity(name)
    related_tags = get_by_names(session, names)
    new_tags = []
    for name in names:
        found = False
        for related_tag in related_tags:
            if _check_name_intersection(_get_plain_names(related_tag), [name]):
                found = True
                break
        if not found:
            new_tag = create_tag(
                session,
                names=[name],
                category=config.config['tag_categories'][0],
                suggestions=[],
                implications=[])
            session.add(new_tag)
            new_tags.append(new_tag)
    return related_tags, new_tags

def create_tag(session, names, category, suggestions, implications):
    tag = db.Tag()
    tag.creation_time = datetime.datetime.now()
    update_names(session, tag, names)
    update_category(session, tag, category)
    update_suggestions(session, tag, suggestions)
    update_implications(session, tag, implications)
    return tag

def update_category(session, tag, category):
    if not category in config.config['tag_categories']:
        raise InvalidCategoryError(category, config.config['tag_categories'])
    tag.category = category

def update_names(session, tag, names):
    names = misc.icase_unique(names)
    if not len(names):
        raise InvalidNameError('At least one name must be specified.')
    for name in names:
        _verify_name_validity(name)
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | db.TagName.name.ilike(name)
    if tag.tag_id:
        expr = expr & (db.TagName.tag_id != tag.tag_id)
    existing_tags = session.query(db.TagName).filter(expr).all()
    if len(existing_tags):
        raise TagAlreadyExistsError()
    tag_names_to_remove = []
    for tag_name in tag.names:
        if tag_name.name.lower() not in [name.lower() for name in names]:
            tag_names_to_remove.append(tag_name)
    for tag_name in tag_names_to_remove:
        tag.names.remove(tag_name)
    for name in names:
        if name.lower() not in [tag_name.name.lower() for tag_name in tag.names]:
            tag_name = db.TagName(name)
            session.add(tag_name)
            tag.names.append(tag_name)

def update_implications(session, tag, relations):
    if _check_name_intersection(_get_plain_names(tag), relations):
        raise RelationError('Tag cannot imply itself.')
    related_tags, new_tags = get_or_create_by_names(session, relations)
    session.flush()
    tag.implications = [
        db.TagImplication(tag.tag_id, other_tag.tag_id) \
            for other_tag in related_tags + new_tags]

def update_suggestions(session, tag, relations):
    if _check_name_intersection(_get_plain_names(tag), relations):
        raise RelationError('Tag cannot suggest itself.')
    related_tags, new_tags = get_or_create_by_names(session, relations)
    session.flush()
    tag.suggestions = [
        db.TagSuggestion(tag.tag_id, other_tag.tag_id) \
            for other_tag in related_tags + new_tags]
