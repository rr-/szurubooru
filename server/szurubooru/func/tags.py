import re
from datetime import datetime
from typing import Any, Callable, Dict, List, Optional, Tuple

import sqlalchemy as sa

from szurubooru import config, db, errors, model, rest
from szurubooru.func import serialization, tag_categories, util


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


def _verify_name_validity(name: str) -> None:
    if util.value_exceeds_column_size(name, model.TagName.name):
        raise InvalidTagNameError("Name is too long.")
    name_regex = config.config["tag_name_regex"]
    if not re.match(name_regex, name):
        raise InvalidTagNameError("Name must satisfy regex %r." % name_regex)


def _get_names(tag: model.Tag) -> List[str]:
    assert tag
    return [tag_name.name for tag_name in tag.names]


def _lower_list(names: List[str]) -> List[str]:
    return [name.lower() for name in names]


def _check_name_intersection(
    names1: List[str], names2: List[str], case_sensitive: bool
) -> bool:
    if not case_sensitive:
        names1 = _lower_list(names1)
        names2 = _lower_list(names2)
    return len(set(names1).intersection(names2)) > 0


def sort_tags(tags: List[model.Tag]) -> List[model.Tag]:
    default_category_name = tag_categories.get_default_category_name()
    return sorted(
        tags,
        key=lambda tag: (
            tag.category.order,
            default_category_name == tag.category.name,
            tag.category.name,
            tag.names[0].name,
        ),
    )


def serialize_relation(tag):
    return {
        "names": [tag_name.name for tag_name in tag.names],
        "category": tag.category.name,
        "usages": tag.post_count,
    }


class TagSerializer(serialization.BaseSerializer):
    def __init__(self, tag: model.Tag) -> None:
        self.tag = tag

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "names": self.serialize_names,
            "category": self.serialize_category,
            "version": self.serialize_version,
            "description": self.serialize_description,
            "creationTime": self.serialize_creation_time,
            "lastEditTime": self.serialize_last_edit_time,
            "usages": self.serialize_usages,
            "suggestions": self.serialize_suggestions,
            "implications": self.serialize_implications,
        }

    def serialize_names(self) -> Any:
        return [tag_name.name for tag_name in self.tag.names]

    def serialize_category(self) -> Any:
        return self.tag.category.name

    def serialize_version(self) -> Any:
        return self.tag.version

    def serialize_description(self) -> Any:
        return self.tag.description

    def serialize_creation_time(self) -> Any:
        return self.tag.creation_time

    def serialize_last_edit_time(self) -> Any:
        return self.tag.last_edit_time

    def serialize_usages(self) -> Any:
        return self.tag.post_count

    def serialize_suggestions(self) -> Any:
        return [
            serialize_relation(relation)
            for relation in sort_tags(self.tag.suggestions)
        ]

    def serialize_implications(self) -> Any:
        return [
            serialize_relation(relation)
            for relation in sort_tags(self.tag.implications)
        ]


def serialize_tag(
    tag: model.Tag, options: List[str] = []
) -> Optional[rest.Response]:
    if not tag:
        return None
    return TagSerializer(tag).serialize(options)


def try_get_tag_by_name(name: str) -> Optional[model.Tag]:
    return (
        db.session.query(model.Tag)
        .join(model.TagName)
        .filter(sa.func.lower(model.TagName.name) == name.lower())
        .one_or_none()
    )


def get_tag_by_name(name: str) -> model.Tag:
    tag = try_get_tag_by_name(name)
    if not tag:
        raise TagNotFoundError("Tag %r not found." % name)
    return tag


def get_tags_by_names(names: List[str]) -> List[model.Tag]:
    """
    Returns a list of all tags which names include all the letters from the input list
    """
    names = util.icase_unique(names)
    if len(names) == 0:
        return []
    return (
        db.session.query(model.Tag)
        .join(model.TagName)
        .filter(
            sa.sql.or_(
                sa.func.lower(model.TagName.name) == name.lower()
                for name in names
            )
        )
        .all()
    )


def get_tags_by_exact_names(names: List[str]) -> List[model.Tag]:
    """
    Returns a list of tags matching the names from the input list
    """
    entries = []
    if len(names) == 0:
        return []
    names = [name.lower() for name in names]
    entries = (
        db.session.query(model.Tag)
            .join(model.TagName)
            .filter(
                sa.func.lower(model.TagName.name).in_(names)
            )
            .all())
    return entries


def get_or_create_tags_by_names(
    names: List[str],
) -> Tuple[List[model.Tag], List[model.Tag]]:
    names = util.icase_unique(names)
    existing_tags = get_tags_by_names(names)
    new_tags = []
    tag_category_name = tag_categories.get_default_category_name()
    for name in names:
        found = False
        for existing_tag in existing_tags:
            if _check_name_intersection(
                _get_names(existing_tag), [name], False
            ):
                found = True
                break
        if not found:
            new_tag = create_tag(
                names=[name],
                category_name=tag_category_name,
                suggestions=[],
                implications=[],
            )
            db.session.add(new_tag)
            new_tags.append(new_tag)
    return existing_tags, new_tags


def get_tag_siblings(tag: model.Tag) -> List[model.Tag]:
    assert tag
    tag_alias = sa.orm.aliased(model.Tag)
    pt_alias1 = sa.orm.aliased(model.PostTag)
    pt_alias2 = sa.orm.aliased(model.PostTag)
    result = (
        db.session.query(tag_alias, sa.func.count(pt_alias2.post_id))
        .join(pt_alias1, pt_alias1.tag_id == tag_alias.tag_id)
        .join(pt_alias2, pt_alias2.post_id == pt_alias1.post_id)
        .filter(pt_alias2.tag_id == tag.tag_id)
        .filter(pt_alias1.tag_id != tag.tag_id)
        .group_by(tag_alias.tag_id)
        .order_by(sa.func.count(pt_alias2.post_id).desc())
        .order_by(tag_alias.first_name)
        .limit(50)
    )
    return result


def delete(source_tag: model.Tag) -> None:
    assert source_tag
    db.session.execute(
        sa.sql.expression.delete(model.TagSuggestion).where(
            model.TagSuggestion.child_id == source_tag.tag_id
        )
    )
    db.session.execute(
        sa.sql.expression.delete(model.TagImplication).where(
            model.TagImplication.child_id == source_tag.tag_id
        )
    )
    db.session.delete(source_tag)


def merge_tags(source_tag: model.Tag, target_tag: model.Tag) -> None:
    assert source_tag
    assert target_tag
    if source_tag.tag_id == target_tag.tag_id:
        raise InvalidTagRelationError("Cannot merge tag with itself.")

    def merge_posts(source_tag_id: int, target_tag_id: int) -> None:
        alias1 = model.PostTag
        alias2 = sa.orm.util.aliased(model.PostTag)
        update_stmt = sa.sql.expression.update(alias1).where(
            alias1.tag_id == source_tag_id
        )
        update_stmt = update_stmt.where(
            ~sa.exists()
            .where(alias1.post_id == alias2.post_id)
            .where(alias2.tag_id == target_tag_id)
        )
        update_stmt = update_stmt.values(tag_id=target_tag_id)
        db.session.execute(update_stmt)

    def merge_relations(
        table: model.Base, source_tag_id: int, target_tag_id: int
    ) -> None:
        alias1 = table
        alias2 = sa.orm.util.aliased(table)
        update_stmt = (
            sa.sql.expression.update(alias1)
            .where(alias1.parent_id == source_tag_id)
            .where(alias1.child_id != target_tag_id)
            .where(
                ~sa.exists()
                .where(alias2.child_id == alias1.child_id)
                .where(alias2.parent_id == target_tag_id)
            )
            .values(parent_id=target_tag_id)
        )
        db.session.execute(update_stmt)

        update_stmt = (
            sa.sql.expression.update(alias1)
            .where(alias1.child_id == source_tag_id)
            .where(alias1.parent_id != target_tag_id)
            .where(
                ~sa.exists()
                .where(alias2.parent_id == alias1.parent_id)
                .where(alias2.child_id == target_tag_id)
            )
            .values(child_id=target_tag_id)
        )
        db.session.execute(update_stmt)

    def merge_suggestions(source_tag_id: int, target_tag_id: int) -> None:
        merge_relations(model.TagSuggestion, source_tag_id, target_tag_id)

    def merge_implications(source_tag_id: int, target_tag_id: int) -> None:
        merge_relations(model.TagImplication, source_tag_id, target_tag_id)

    merge_posts(source_tag.tag_id, target_tag.tag_id)
    merge_suggestions(source_tag.tag_id, target_tag.tag_id)
    merge_implications(source_tag.tag_id, target_tag.tag_id)
    delete(source_tag)


def create_tag(
    names: List[str],
    category_name: str,
    suggestions: List[str],
    implications: List[str],
) -> model.Tag:
    tag = model.Tag()
    tag.creation_time = datetime.utcnow()
    update_tag_names(tag, names)
    update_tag_category_name(tag, category_name)
    update_tag_suggestions(tag, suggestions)
    update_tag_implications(tag, implications)
    return tag


def update_tag_category_name(tag: model.Tag, category_name: str) -> None:
    assert tag
    tag.category = tag_categories.get_category_by_name(category_name)


def update_tag_names(tag: model.Tag, names: List[str]) -> None:
    # sanitize
    assert tag
    names = util.icase_unique([name for name in names if name])
    if not len(names):
        raise InvalidTagNameError("At least one name must be specified.")
    for name in names:
        _verify_name_validity(name)

    # check for existing tags
    expr = sa.sql.false()
    for name in names:
        expr = expr | (sa.func.lower(model.TagName.name) == name.lower())
    if tag.tag_id:
        expr = expr & (model.TagName.tag_id != tag.tag_id)
    existing_tags = db.session.query(model.TagName).filter(expr).all()
    if len(existing_tags):
        raise TagAlreadyExistsError(
            "One of names is already used by another tag."
        )

    # remove unwanted items
    for tag_name in tag.names[:]:
        if not _check_name_intersection([tag_name.name], names, True):
            tag.names.remove(tag_name)
    # add wanted items
    for name in names:
        if not _check_name_intersection(_get_names(tag), [name], True):
            tag.names.append(model.TagName(name, -1))

    # set alias order to match the request
    for i, name in enumerate(names):
        for tag_name in tag.names:
            if tag_name.name.lower() == name.lower():
                tag_name.order = i


# TODO: what to do with relations that do not yet exist?
def update_tag_implications(tag: model.Tag, relations: List[str]) -> None:
    assert tag
    if _check_name_intersection(_get_names(tag), relations, False):
        raise InvalidTagRelationError("Tag cannot imply itself.")
    tag.implications = get_tags_by_names(relations)


# TODO: what to do with relations that do not yet exist?
def update_tag_suggestions(tag: model.Tag, relations: List[str]) -> None:
    assert tag
    if _check_name_intersection(_get_names(tag), relations, False):
        raise InvalidTagRelationError("Tag cannot suggest itself.")
    tag.suggestions = get_tags_by_names(relations)


def update_tag_description(tag: model.Tag, description: str) -> None:
    assert tag
    if util.value_exceeds_column_size(description, model.Tag.description):
        raise InvalidTagDescriptionError("Description is too long.")
    tag.description = description or None
