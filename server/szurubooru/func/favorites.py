import datetime
from szurubooru import db
from szurubooru.func import util

def _get_table_info(entity):
    resource_type, _, _ = util.get_resource_info(entity)
    if resource_type == 'post':
        return db.PostFavorite, lambda table: table.post_id
    else:
        assert False

def _get_fav_entity(entity, user):
    table, get_column = _get_table_info(entity)
    return db.session \
        .query(table) \
        .filter(get_column(table) == get_column(entity)) \
        .filter(table.user_id == user.user_id) \
        .one_or_none()

def has_favorited(entity, user):
    return _get_fav_entity(entity, user) is not None

def unset_favorite(entity, user):
    fav_entity = _get_fav_entity(entity, user)
    if fav_entity:
        db.session.delete(fav_entity)

def set_favorite(entity, user):
    fav_entity = _get_fav_entity(entity, user)
    if not fav_entity:
        table, get_column = _get_table_info(entity)
        fav_entity = table()
        setattr(fav_entity, get_column(table).name, get_column(entity))
        fav_entity.user = user
        fav_entity.time = datetime.datetime.now()
        db.session.add(fav_entity)
