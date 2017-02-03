import datetime
import json
import os
import re
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.func import util, tag_categories


class TagNotFoundError(errors.NotFoundError):
    pass


class TagAlreadyExistsError(errors.ValidationError):
    pass


class TagIsInUseError(errors.ValidationError):
    pass


class InvalidTagNameError(errors.ValidationError):
    pass


class InvalidTagRelationError(errors.ValidationError):
    pass


class InvalidTagCategoryError(errors.ValidationError):
    pass


class InvalidTagDescriptionError(errors.ValidationError):
    pass


def _verify_name_validity(name):
    if util.value_exceeds_column_size(name, db.TagName.name):
        raise InvalidTagNameError('Name is too long.')
    name_regex = config.config['tag_name_regex']
    if not re.match(name_regex, name):
        raise InvalidTagNameError('Name must satisfy regex %r.' % name_regex)


def _get_names(tag):
    assert tag
    return [tag_name.name for tag_name in tag.names]


def _lower_list(names):
    return [name.lower() for name in names]


def _check_name_intersection(names1, names2, case_sensitive):
    if not case_sensitive:
        names1 = _lower_list(names1)
        names2 = _lower_list(names2)
    return len(set(names1).intersection(names2)) > 0


def sort_tags(tags):
    default_category_name = tag_categories.get_default_category_name()
    return sorted(
        tags,
        key=lambda tag: (
            default_category_name == tag.category.name,
            tag.category.name,
            tag.names[0].name)
    )


def serialize_tag(tag, options=None):
    return util.serialize_entity(
        tag,
        {
            'names': lambda: [tag_name.name for tag_name in tag.names],
            'category': lambda: tag.category.name,
            'version': lambda: tag.version,
            'description': lambda: tag.description,
            'creationTime': lambda: tag.creation_time,
            'lastEditTime': lambda: tag.last_edit_time,
            'usages': lambda: tag.post_count,
            'suggestions': lambda: [
                relation.names[0].name
                for relation in sort_tags(tag.suggestions)],
            'implications': lambda: [
                relation.names[0].name
                for relation in sort_tags(tag.implications)],
        },
        options)


def export_to_json():
    tags = {}
    categories = {}

    for result in db.session.query(
            db.TagCategory.tag_category_id,
            db.TagCategory.name,
            db.TagCategory.color).all():
        categories[result[0]] = {
            'name': result[1],
            'color': result[2],
        }

    for result in (
            db.session
            .query(db.TagName.tag_id, db.TagName.name)
            .order_by(db.TagName.order)
            .all()):
        if not result[0] in tags:
            tags[result[0]] = {'names': []}
        tags[result[0]]['names'].append(result[1])

    for result in (
            db.session
            .query(db.TagSuggestion.parent_id, db.TagName.name)
            .join(db.TagName, db.TagName.tag_id == db.TagSuggestion.child_id)
            .all()):
        if 'suggestions' not in tags[result[0]]:
            tags[result[0]]['suggestions'] = []
        tags[result[0]]['suggestions'].append(result[1])

    for result in (
            db.session
            .query(db.TagImplication.parent_id, db.TagName.name)
            .join(db.TagName, db.TagName.tag_id == db.TagImplication.child_id)
            .all()):
        if 'implications' not in tags[result[0]]:
            tags[result[0]]['implications'] = []
        tags[result[0]]['implications'].append(result[1])

    for result in db.session.query(
            db.Tag.tag_id,
            db.Tag.category_id,
            db.Tag.post_count).all():
        tags[result[0]]['category'] = categories[result[1]]['name']
        tags[result[0]]['usages'] = result[2]

    output = {
        'categories': list(categories.values()),
        'tags': list(tags.values()),
    }

    export_path = os.path.join(config.config['data_dir'], 'tags.json')
    with open(export_path, 'w') as handle:
        handle.write(json.dumps(output, separators=(',', ':')))


def try_get_tag_by_name(name):
    return (
        db.session
        .query(db.Tag)
        .join(db.TagName)
        .filter(sqlalchemy.func.lower(db.TagName.name) == name.lower())
        .one_or_none())


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
    existing_tags = get_tags_by_names(names)
    new_tags = []
    tag_category_name = tag_categories.get_default_category_name()
    for name in names:
        found = False
        for existing_tag in existing_tags:
            if _check_name_intersection(
                    _get_names(existing_tag), [name], False):
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
    assert tag
    tag_alias = sqlalchemy.orm.aliased(db.Tag)
    pt_alias1 = sqlalchemy.orm.aliased(db.PostTag)
    pt_alias2 = sqlalchemy.orm.aliased(db.PostTag)
    result = (
        db.session
        .query(tag_alias, sqlalchemy.func.count(pt_alias2.post_id))
        .join(pt_alias1, pt_alias1.tag_id == tag_alias.tag_id)
        .join(pt_alias2, pt_alias2.post_id == pt_alias1.post_id)
        .filter(pt_alias2.tag_id == tag.tag_id)
        .filter(pt_alias1.tag_id != tag.tag_id)
        .group_by(tag_alias.tag_id)
        .order_by(sqlalchemy.func.count(pt_alias2.post_id).desc())
        .limit(50))
    return result


def delete(source_tag):
    assert source_tag
    db.session.execute(
        sqlalchemy.sql.expression.delete(db.TagSuggestion)
        .where(db.TagSuggestion.child_id == source_tag.tag_id))
    db.session.execute(
        sqlalchemy.sql.expression.delete(db.TagImplication)
        .where(db.TagImplication.child_id == source_tag.tag_id))
    db.session.delete(source_tag)


def merge_tags(source_tag, target_tag):
    assert source_tag
    assert target_tag
    if source_tag.tag_id == target_tag.tag_id:
        raise InvalidTagRelationError('Cannot merge tag with itself.')

    def merge_posts(source_tag_id, target_tag_id):
        alias1 = db.PostTag
        alias2 = sqlalchemy.orm.util.aliased(db.PostTag)
        update_stmt = (
            sqlalchemy.sql.expression.update(alias1)
            .where(alias1.tag_id == source_tag_id))
        update_stmt = (
            update_stmt
            .where(
                ~sqlalchemy.exists()
                .where(alias1.post_id == alias2.post_id)
                .where(alias2.tag_id == target_tag_id)))
        update_stmt = update_stmt.values(tag_id=target_tag_id)
        db.session.execute(update_stmt)

    def merge_relations(table, source_tag_id, target_tag_id):
        alias1 = table
        alias2 = sqlalchemy.orm.util.aliased(table)
        update_stmt = (
            sqlalchemy.sql.expression.update(alias1)
            .where(alias1.parent_id == source_tag_id)
            .where(alias1.child_id != target_tag_id)
            .where(
                ~sqlalchemy.exists()
                .where(alias2.child_id == alias1.child_id)
                .where(alias2.parent_id == target_tag_id))
            .values(parent_id=target_tag_id))
        db.session.execute(update_stmt)

        update_stmt = (
            sqlalchemy.sql.expression.update(alias1)
            .where(alias1.child_id == source_tag_id)
            .where(alias1.parent_id != target_tag_id)
            .where(
                ~sqlalchemy.exists()
                .where(alias2.parent_id == alias1.parent_id)
                .where(alias2.child_id == target_tag_id))
            .values(child_id=target_tag_id))
        db.session.execute(update_stmt)

    def merge_suggestions(source_tag_id, target_tag_id):
        merge_relations(db.TagSuggestion, source_tag_id, target_tag_id)

    def merge_implications(source_tag_id, target_tag_id):
        merge_relations(db.TagImplication, source_tag_id, target_tag_id)

    merge_posts(source_tag.tag_id, target_tag.tag_id)
    merge_suggestions(source_tag.tag_id, target_tag.tag_id)
    merge_implications(source_tag.tag_id, target_tag.tag_id)
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
    assert tag
    tag.category = tag_categories.get_category_by_name(category_name)


def update_tag_names(tag, names):
    # sanitize
    assert tag
    names = util.icase_unique([name for name in names if name])
    if not len(names):
        raise InvalidTagNameError('At least one name must be specified.')
    for name in names:
        _verify_name_validity(name)

    # check for existing tags
    expr = sqlalchemy.sql.false()
    for name in names:
        expr = expr | (sqlalchemy.func.lower(db.TagName.name) == name.lower())
    if tag.tag_id:
        expr = expr & (db.TagName.tag_id != tag.tag_id)
    existing_tags = db.session.query(db.TagName).filter(expr).all()
    if len(existing_tags):
        raise TagAlreadyExistsError(
            'One of names is already used by another tag.')

    # remove unwanted items
    for tag_name in tag.names[:]:
        if not _check_name_intersection([tag_name.name], names, True):
            tag.names.remove(tag_name)
    # add wanted items
    for name in names:
        if not _check_name_intersection(_get_names(tag), [name], True):
            tag.names.append(db.TagName(name, None))

    # set alias order to match the request
    for i, name in enumerate(names):
        for tag_name in tag.names:
            if tag_name.name.lower() == name.lower():
                tag_name.order = i


# TODO: what to do with relations that do not yet exist?
def update_tag_implications(tag, relations):
    assert tag
    if _check_name_intersection(_get_names(tag), relations, False):
        raise InvalidTagRelationError('Tag cannot imply itself.')
    tag.implications = get_tags_by_names(relations)


# TODO: what to do with relations that do not yet exist?
def update_tag_suggestions(tag, relations):
    assert tag
    if _check_name_intersection(_get_names(tag), relations, False):
        raise InvalidTagRelationError('Tag cannot suggest itself.')
    tag.suggestions = get_tags_by_names(relations)


def update_tag_description(tag, description):
    assert tag
    if util.value_exceeds_column_size(description, db.Tag.description):
        raise InvalidTagDescriptionError('Description is too long.')
    tag.description = description
