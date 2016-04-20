import re
from szurubooru import config, db, errors
from szurubooru.func import misc

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

def create_category(name, color):
    category = db.TagCategory()
    update_name(category, name)
    update_color(category, color)
    return category

def update_name(category, name):
    if not name:
        raise InvalidTagCategoryNameError('Name cannot be empty.')
    expr = db.TagCategory.name.ilike(name)
    if category.tag_category_id:
        expr = expr & (db.TagCategory.tag_category_id != category.tag_category_id)
    already_exists = db.session.query(db.TagCategory).filter(expr).count() > 0
    if already_exists:
        raise TagCategoryAlreadyExistsError(
            'A category with this name already exists.')
    if misc.value_exceeds_column_size(name, db.TagCategory.name):
        raise InvalidTagCategoryNameError('Name is too long.')
    _verify_name_validity(name)
    category.name = name

def update_color(category, color):
    if not color:
        raise InvalidTagCategoryNameError('Color cannot be empty.')
    if misc.value_exceeds_column_size(color, db.TagCategory.color):
        raise InvalidTagCategoryColorError('Color is too long.')
    category.color = color

def get_category_by_name(name):
    return db.session \
        .query(db.TagCategory) \
        .filter(db.TagCategory.name.ilike(name)) \
        .first()

def get_all_category_names():
    return [row[0] for row in db.session.query(db.TagCategory.name).all()]

def get_all_categories():
    return db.session.query(db.TagCategory).all()

def get_default_category():
    return db.session \
        .query(db.TagCategory) \
        .order_by(db.TagCategory.tag_category_id.asc()) \
        .limit(1) \
        .one()
