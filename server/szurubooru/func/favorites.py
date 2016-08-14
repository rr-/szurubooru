import datetime
from szurubooru import db, errors
from szurubooru.func import scores


class InvalidFavoriteTargetError(errors.ValidationError):
    pass


def _get_table_info(entity):
    assert entity
    resource_type, _, _ = db.util.get_resource_info(entity)
    if resource_type == 'post':
        return db.PostFavorite, lambda table: table.post_id
    raise InvalidFavoriteTargetError()


def _get_fav_entity(entity, user):
    assert entity
    assert user
    return db.util.get_aux_entity(db.session, _get_table_info, entity, user)


def has_favorited(entity, user):
    assert entity
    assert user
    return _get_fav_entity(entity, user) is not None


def unset_favorite(entity, user):
    assert entity
    assert user
    fav_entity = _get_fav_entity(entity, user)
    if fav_entity:
        db.session.delete(fav_entity)


def set_favorite(entity, user):
    assert entity
    assert user
    try:
        scores.set_score(entity, user, 1)
    except scores.InvalidScoreTargetError:
        pass
    fav_entity = _get_fav_entity(entity, user)
    if not fav_entity:
        table, get_column = _get_table_info(entity)
        fav_entity = table()
        setattr(fav_entity, get_column(table).name, get_column(entity))
        fav_entity.user = user
        fav_entity.time = datetime.datetime.utcnow()
        db.session.add(fav_entity)
