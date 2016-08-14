import re
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.func import util, snapshots, cache

class TagCategoryNotFoundError(errors.NotFoundError): pass
class TagCategoryAlreadyExistsError(errors.ValidationError): pass
class TagCategoryIsInUseError(errors.ValidationError): pass
class InvalidTagCategoryNameError(errors.ValidationError): pass
class InvalidTagCategoryColorError(errors.ValidationError): pass

def _verify_name_validity(name):
    name_regex = config.config['tag_category_name_regex']
    if not re.match(name_regex, name):
        raise InvalidTagCategoryNameError(
            'Name must satisfy regex %r.' % name_regex)

def serialize_category(category, options=None):
    return util.serialize_entity(
        category,
        {
            'name': lambda: category.name,
            'version': lambda: category.version,
            'color': lambda: category.color,
            'usages': lambda: category.tag_count,
            'default': lambda: category.default,
            'snapshots': lambda: snapshots.get_serialized_history(category),
        },
        options)

def create_category(name, color):
    category = db.TagCategory()
    update_category_name(category, name)
    update_category_color(category, color)
    if not get_all_categories():
        category.default = True
    return category

def update_category_name(category, name):
    assert category
    if not name:
        raise InvalidTagCategoryNameError('Name cannot be empty.')
    expr = sqlalchemy.func.lower(db.TagCategory.name) == name.lower()
    if category.tag_category_id:
        expr = expr & (db.TagCategory.tag_category_id != category.tag_category_id)
    already_exists = db.session.query(db.TagCategory).filter(expr).count() > 0
    if already_exists:
        raise TagCategoryAlreadyExistsError(
            'A category with this name already exists.')
    if util.value_exceeds_column_size(name, db.TagCategory.name):
        raise InvalidTagCategoryNameError('Name is too long.')
    _verify_name_validity(name)
    category.name = name

def update_category_color(category, color):
    assert category
    if not color:
        raise InvalidTagCategoryColorError('Color cannot be empty.')
    if not re.match(r'^#?[0-9a-z]+$', color):
        raise InvalidTagCategoryColorError('Invalid color.')
    if util.value_exceeds_column_size(color, db.TagCategory.color):
        raise InvalidTagCategoryColorError('Color is too long.')
    category.color = color

def try_get_category_by_name(name):
    return db.session \
        .query(db.TagCategory) \
        .filter(sqlalchemy.func.lower(db.TagCategory.name) == name.lower()) \
        .one_or_none()

def get_category_by_name(name):
    category = try_get_category_by_name(name)
    if not category:
        raise TagCategoryNotFoundError('Tag category %r not found.' % name)
    return category

def get_all_category_names():
    return [row[0] for row in db.session.query(db.TagCategory.name).all()]

def get_all_categories():
    return db.session.query(db.TagCategory).all()

def try_get_default_category():
    key = 'default-tag-category'
    if cache.has(key):
        return cache.get(key)
    category = db.session \
        .query(db.TagCategory) \
        .filter(db.TagCategory.default) \
        .first()
    # if for some reason (e.g. as a result of migration) there's no default
    # category, get the first record available.
    if not category:
        category = db.session \
            .query(db.TagCategory) \
            .order_by(db.TagCategory.tag_category_id.asc()) \
            .first()
    cache.put(key, category)
    return category

def get_default_category():
    category = try_get_default_category()
    if not category:
        raise TagCategoryNotFoundError('No tag category created yet.')
    return category

def set_default_category(category):
    assert category
    old_category = try_get_default_category()
    if old_category:
        old_category.default = False
    category.default = True
