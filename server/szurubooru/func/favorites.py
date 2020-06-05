from datetime import datetime
from typing import Any, Callable, Optional, Tuple

from szurubooru import db, errors, model


class InvalidFavoriteTargetError(errors.ValidationError):
    pass


def _get_table_info(
    entity: model.Base,
) -> Tuple[model.Base, Callable[[model.Base], Any]]:
    assert entity
    resource_type, _, _ = model.util.get_resource_info(entity)
    if resource_type == "post":
        return model.PostFavorite, lambda table: table.post_id
    raise InvalidFavoriteTargetError()


def _get_fav_entity(entity: model.Base, user: model.User) -> model.Base:
    assert entity
    assert user
    return model.util.get_aux_entity(db.session, _get_table_info, entity, user)


def has_favorited(entity: model.Base, user: model.User) -> bool:
    assert entity
    assert user
    return _get_fav_entity(entity, user) is not None


def unset_favorite(entity: model.Base, user: Optional[model.User]) -> None:
    assert entity
    assert user
    fav_entity = _get_fav_entity(entity, user)
    if fav_entity:
        db.session.delete(fav_entity)


def set_favorite(entity: model.Base, user: Optional[model.User]) -> None:
    from szurubooru.func import scores

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
        fav_entity.time = datetime.utcnow()
        db.session.add(fav_entity)
