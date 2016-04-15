import datetime
import re
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.util import misc

def get_by_names(session, names):
    names = misc.icase_unique(names)
    if len(names) == 0:
        return []
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | db.TagName.name.ilike(name)
    return session.query(db.Tag).join(db.TagName).filter(expr).all()

def get_or_create_by_names(session, names):
    related_tags = get_by_names(session, names)
    for name in names:
        found = False
        for related_tag in related_tags:
            for tag_name in related_tag.names:
                if tag_name.name.lower() == name.lower():
                    found = True
                    break
            if found:
                break

        if not found:
            new_tag = create_tag(
                session,
                names=[name],
                category=config.config['tag_categories'][0],
                suggestions=[],
                implications=[])
            session.add(new_tag)
            session.commit() # need to get id for use in association tables
            related_tags.append(new_tag)
    return related_tags

def create_tag(session, names, category, suggestions, implications):
    tag = db.Tag()
    tag.creation_time = datetime.datetime.now()
    update_category(tag, category)
    update_names(session, tag, names)
    update_suggestions(session, tag, suggestions)
    update_implications(session, tag, implications)
    return tag

def update_category(tag, category):
    if not category in config.config['tag_categories']:
        raise errors.ValidationError(
            'Category must be either of %r.', config.config['tag_categories'])
    tag.category = category

def update_names(session, tag, names):
    names = misc.icase_unique(names)
    if not len(names):
        raise errors.ValidationError('At least one name must be specified.')
    for name in names:
        name_regex = config.config['tag_name_regex']
        if not re.match(name_regex, name):
            raise errors.ValidationError(
                'Name must satisfy regex %r.' % name_regex)
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | db.TagName.name.ilike(name)
    if tag.tag_id:
        expr = expr & (db.TagName.tag_id != tag.tag_id)
    existing_tags = session.query(db.TagName).filter(expr).all()
    if len(existing_tags):
        raise errors.ValidationError(
            'One of names is already used by another tag.')
    tag.names = []
    for name in names:
        tag_name = db.TagName(name)
        session.add(tag_name)
        tag.names.append(tag_name)

def update_implications(session, tag, relations):
    related_tags = get_or_create_by_names(session, relations)
    tag.implications = [
        db.TagImplication(tag.tag_id, other_tag.tag_id) \
            for other_tag in related_tags]

def update_suggestions(session, tag, relations):
    related_tags = get_or_create_by_names(session, relations)
    tag.suggestions = [
        db.TagSuggestion(tag.tag_id, other_tag.tag_id) \
            for other_tag in related_tags]
