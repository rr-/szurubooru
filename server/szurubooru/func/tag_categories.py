import re
from szurubooru import config, db, errors
from szurubooru.func import util, snapshots

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

def serialize_category(category):
    return {
        'name': category.name,
        'color': category.color,
    }

def serialize_category_with_details(category):
    return {
        'tagCategory': serialize_category(category),
        'snapshots': snapshots.get_serialized_history(category),
    }

def create_category(name, color):
    category = db.TagCategory()
    update_category_name(category, name)
    update_category_color(category, color)
    return category

def update_category_name(category, name):
    if not name:
        raise InvalidTagCategoryNameError('Name cannot be empty.')
    expr = db.TagCategory.name.ilike(name)
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
    if not color:
        raise InvalidTagCategoryNameError('Color cannot be empty.')
    if util.value_exceeds_column_size(color, db.TagCategory.color):
        raise InvalidTagCategoryColorError('Color is too long.')
    category.color = color

def try_get_category_by_name(name):
    return db.session \
        .query(db.TagCategory) \
        .filter(db.TagCategory.name.ilike(name)) \
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
    return db.session \
        .query(db.TagCategory) \
        .order_by(db.TagCategory.tag_category_id.asc()) \
        .limit(1) \
        .first()

def get_default_category():
    category = try_get_default_category()
    if not category:
        raise TagCategoryNotFoundError('No tag category created yet.')
    return category
