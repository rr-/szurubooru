import datetime
import json
import os
import re
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.func import misc, tag_categories

class TagNotFoundError(errors.NotFoundError): pass
class TagAlreadyExistsError(errors.ValidationError): pass
class TagIsInUseError(errors.ValidationError): pass
class InvalidTagNameError(errors.ValidationError): pass
class InvalidTagCategoryError(errors.ValidationError): pass
class InvalidTagRelationError(errors.ValidationError): pass

def _verify_name_validity(name):
    name_regex = config.config['tag_name_regex']
    if not re.match(name_regex, name):
        raise InvalidTagNameError('Name must satisfy regex %r.' % name_regex)

def _get_plain_names(tag):
    return [tag_name.name for tag_name in tag.names]

def _lower_list(names):
    return [name.lower() for name in names]

def _check_name_intersection(names1, names2):
    return len(set(_lower_list(names1)).intersection(_lower_list(names2))) > 0

def export_to_json():
    output = {
        'tags': [],
        'categories': [],
    }
    all_tags = db.session \
        .query(db.Tag) \
        .options(
            sqlalchemy.orm.joinedload('suggestions'),
            sqlalchemy.orm.joinedload('implications')) \
        .all()
    for tag in all_tags:
        item = {
            'names': [tag_name.name for tag_name in tag.names],
            'usages': tag.post_count,
            'category': tag.category.name,
        }
        if len(tag.suggestions):
            item['suggestions'] = \
                [rel.names[0].name for rel in tag.suggestions]
        if len(tag.implications):
            item['implications'] = \
                [rel.names[0].name for rel in tag.implications]
        output['tags'].append(item)
    for category in tag_categories.get_all_categories():
        output['categories'].append({
            'name': category.name,
            'color': category.color,
        })
    export_path = os.path.join(config.config['data_dir'], 'tags.json')
    with open(export_path, 'w') as handle:
        handle.write(json.dumps(output, separators=(',', ':')))

def get_tag_by_name(name):
    return db.session \
        .query(db.Tag) \
        .join(db.TagName) \
        .filter(db.TagName.name.ilike(name)) \
        .first()

def get_tags_by_names(names):
    names = misc.icase_unique(names)
    if len(names) == 0:
        return []
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | db.TagName.name.ilike(name)
    return db.session.query(db.Tag).join(db.TagName).filter(expr).all()

def get_or_create_tags_by_names(names):
    names = misc.icase_unique(names)
    for name in names:
        _verify_name_validity(name)
    related_tags = get_tags_by_names(names)
    new_tags = []
    for name in names:
        found = False
        for related_tag in related_tags:
            if _check_name_intersection(_get_plain_names(related_tag), [name]):
                found = True
                break
        if not found:
            new_tag = create_tag(
                names=[name],
                category_name=tag_categories.get_default_category().name,
                suggestions=[],
                implications=[])
            db.session.add(new_tag)
            new_tags.append(new_tag)
    return related_tags, new_tags

def create_tag(names, category_name, suggestions, implications):
    tag = db.Tag()
    tag.creation_time = datetime.datetime.now()
    update_names(tag, names)
    update_category_name(tag, category_name)
    update_suggestions(tag, suggestions)
    update_implications(tag, implications)
    return tag

def update_category_name(tag, category_name):
    category = db.session \
        .query(db.TagCategory) \
        .filter(db.TagCategory.name == category_name) \
        .first()
    if not category:
        category_names = tag_categories.get_all_category_names()
        raise InvalidTagCategoryError(
            'Category %r is invalid. Valid categories: %r.' % (
                category_name, category_names))
    tag.category = category

def update_names(tag, names):
    names = misc.icase_unique([name for name in names if name])
    if not len(names):
        raise InvalidTagNameError('At least one name must be specified.')
    for name in names:
        _verify_name_validity(name)
    expr = sqlalchemy.sql.false()
    for name in names:
        if misc.value_exceeds_column_size(name, db.TagName.name):
            raise InvalidTagNameError('Name is too long.')
        expr = expr | db.TagName.name.ilike(name)
    if tag.tag_id:
        expr = expr & (db.TagName.tag_id != tag.tag_id)
    existing_tags = db.session.query(db.TagName).filter(expr).all()
    if len(existing_tags):
        raise TagAlreadyExistsError(
            'One of names is already used by another tag.')
    tag_names_to_remove = []
    for tag_name in tag.names:
        if not _check_name_intersection([tag_name.name], names):
            tag_names_to_remove.append(tag_name)
    for tag_name in tag_names_to_remove:
        tag.names.remove(tag_name)
    for name in names:
        if not _check_name_intersection(_get_plain_names(tag), [name]):
            tag.names.append(db.TagName(name))

def update_implications(tag, relations):
    if _check_name_intersection(_get_plain_names(tag), relations):
        raise InvalidTagRelationError('Tag cannot imply itself.')
    related_tags, new_tags = get_or_create_tags_by_names(relations)
    db.session.flush()
    tag.implications = related_tags + new_tags

def update_suggestions(tag, relations):
    if _check_name_intersection(_get_plain_names(tag), relations):
        raise InvalidTagRelationError('Tag cannot suggest itself.')
    related_tags, new_tags = get_or_create_tags_by_names(relations)
    db.session.flush()
    tag.suggestions = related_tags + new_tags
