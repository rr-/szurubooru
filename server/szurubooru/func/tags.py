import datetime
import json
import os
import re
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.func import util, tag_categories, snapshots

class TagNotFoundError(errors.NotFoundError): pass
class TagAlreadyExistsError(errors.ValidationError): pass
class TagIsInUseError(errors.ValidationError): pass
class InvalidTagNameError(errors.ValidationError): pass
class InvalidTagRelationError(errors.ValidationError): pass

DEFAULT_CATEGORY_NAME = 'Default'
DEFAULT_CATEGORY_COLOR = 'default'

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

def _get_default_category_name():
    tag_category = tag_categories.try_get_default_category()
    if tag_category:
        return tag_category.name
    else:
        return DEFAULT_CATEGORY_NAME

def serialize_tag(tag, options=None):
    return util.serialize_entity(
        tag,
        {
            'names': lambda: [tag_name.name for tag_name in tag.names],
            'category': lambda: tag.category.name,
            'description': lambda: tag.description,
            'creationTime': lambda: tag.creation_time,
            'lastEditTime': lambda: tag.last_edit_time,
            'usages': lambda: tag.post_count,
            'suggestions': lambda: [
                relation.names[0].name for relation in tag.suggestions],
            'implications': lambda: [
                relation.names[0].name for relation in tag.implications],
            'snapshots': lambda: snapshots.get_serialized_history(tag),
        },
        options)

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

def try_get_tag_by_name(name):
    return db.session \
        .query(db.Tag) \
        .join(db.TagName) \
        .filter(sqlalchemy.func.lower(db.TagName.name) == name.lower()) \
        .one_or_none()

def get_tag_by_name(name):
    tag = try_get_tag_by_name(name)
    if not tag:
        raise TagNotFoundError('Tag %r not found.' % name)
    return tag

def get_tags_by_names(names):
    names = util.icase_unique(names)
    if len(names) == 0:
        return []
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | (sqlalchemy.func.lower(db.TagName.name) == name.lower())
    return db.session.query(db.Tag).join(db.TagName).filter(expr).all()

def get_or_create_tags_by_names(names):
    names = util.icase_unique(names)
    for name in names:
        _verify_name_validity(name)
    existing_tags = get_tags_by_names(names)
    new_tags = []
    tag_category_name = _get_default_category_name()
    for name in names:
        found = False
        for existing_tag in existing_tags:
            if _check_name_intersection(_get_plain_names(existing_tag), [name]):
                found = True
                break
        if not found:
            new_tag = create_tag(
                names=[name],
                category_name=tag_category_name,
                suggestions=[],
                implications=[])
            db.session.add(new_tag)
            new_tags.append(new_tag)
    return existing_tags, new_tags

def get_tag_siblings(tag):
    tag_alias = sqlalchemy.orm.aliased(db.Tag)
    pt_alias1 = sqlalchemy.orm.aliased(db.PostTag)
    pt_alias2 = sqlalchemy.orm.aliased(db.PostTag)
    result = db.session \
        .query(tag_alias, sqlalchemy.func.count(pt_alias2.post_id)) \
        .join(pt_alias1, pt_alias1.tag_id == tag_alias.tag_id) \
        .join(pt_alias2, pt_alias2.post_id == pt_alias1.post_id) \
        .filter(pt_alias2.tag_id == tag.tag_id) \
        .filter(pt_alias1.tag_id != tag.tag_id) \
        .group_by(tag_alias.tag_id) \
        .order_by(sqlalchemy.func.count(pt_alias2.post_id).desc()) \
        .limit(50)
    return result

def delete(source_tag):
    db.session.execute(
        sqlalchemy.sql.expression.delete(db.TagSuggestion) \
            .where(db.TagSuggestion.child_id == source_tag.tag_id))
    db.session.execute(
        sqlalchemy.sql.expression.delete(db.TagImplication) \
            .where(db.TagImplication.child_id == source_tag.tag_id))
    db.session.delete(source_tag)

def merge_tags(source_tag, target_tag):
    pt1 = sqlalchemy.orm.util.aliased(db.PostTag)
    pt2 = sqlalchemy.orm.util.aliased(db.PostTag)
    ids_to_be_tagged = sqlalchemy.sql.expression \
        .select([pt1.tag_id]) \
        .where(pt1.tag_id == source_tag.tag_id) \
        .where(~sqlalchemy.exists() \
            .where(pt2.post_id == pt1.post_id) \
            .where(pt2.tag_id == target_tag.tag_id))

    update_stmt = sqlalchemy.sql.expression.update(db.PostTag) \
        .where(db.PostTag.tag_id.in_(ids_to_be_tagged)) \
        .values(tag_id=target_tag.tag_id)
    db.session.execute(update_stmt)
    delete(source_tag)

def create_tag(names, category_name, suggestions, implications):
    tag = db.Tag()
    tag.creation_time = datetime.datetime.utcnow()
    update_tag_names(tag, names)
    update_tag_category_name(tag, category_name)
    update_tag_suggestions(tag, suggestions)
    update_tag_implications(tag, implications)
    return tag

def update_tag_category_name(tag, category_name):
    category = db.session \
        .query(db.TagCategory) \
        .filter(db.TagCategory.name == category_name) \
        .first()
    if not category:
        category = tag_categories.create_category(
            category_name, DEFAULT_CATEGORY_COLOR)
        db.session.add(category)
    tag.category = category

def update_tag_names(tag, names):
    names = util.icase_unique([name for name in names if name])
    if not len(names):
        raise InvalidTagNameError('At least one name must be specified.')
    for name in names:
        _verify_name_validity(name)
    expr = sqlalchemy.sql.false()
    for name in names:
        if util.value_exceeds_column_size(name, db.TagName.name):
            raise InvalidTagNameError('Name is too long.')
        expr = expr | (sqlalchemy.func.lower(db.TagName.name) == name.lower())
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

def update_tag_implications(tag, relations):
    if _check_name_intersection(_get_plain_names(tag), relations):
        raise InvalidTagRelationError('Tag cannot imply itself.')
    tag.implications = get_tags_by_names(relations)

def update_tag_suggestions(tag, relations):
    if _check_name_intersection(_get_plain_names(tag), relations):
        raise InvalidTagRelationError('Tag cannot suggest itself.')
    tag.suggestions = get_tags_by_names(relations)

def update_tag_description(tag, description):
    tag.description = description
