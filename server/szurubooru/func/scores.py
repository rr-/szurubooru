import datetime
from szurubooru import db, errors


class InvalidScoreTargetError(errors.ValidationError):
    pass


class InvalidScoreValueError(errors.ValidationError):
    pass


def _get_table_info(entity):
    assert entity
    resource_type, _, _ = db.util.get_resource_info(entity)
    if resource_type == 'post':
        return db.PostScore, lambda table: table.post_id
    elif resource_type == 'comment':
        return db.CommentScore, lambda table: table.comment_id
    raise InvalidScoreTargetError()


def _get_score_entity(entity, user):
    assert user
    return db.util.get_aux_entity(db.session, _get_table_info, entity, user)


def delete_score(entity, user):
    assert entity
    assert user
    score_entity = _get_score_entity(entity, user)
    if score_entity:
        db.session.delete(score_entity)


def get_score(entity, user):
    assert entity
    assert user
    table, get_column = _get_table_info(entity)
    row = db.session \
        .query(table.score) \
        .filter(get_column(table) == get_column(entity)) \
        .filter(table.user_id == user.user_id) \
        .one_or_none()
    return row[0] if row else 0


def set_score(entity, user, score):
    from szurubooru.func import favorites
    assert entity
    assert user
    if not score:
        delete_score(entity, user)
        try:
            favorites.unset_favorite(entity, user)
        except favorites.InvalidFavoriteTargetError:
            pass
        return
    if score not in (-1, 1):
        raise InvalidScoreValueError(
            'Score %r is invalid. Valid scores: %r.' % (score, (-1, 1)))
    score_entity = _get_score_entity(entity, user)
    if score_entity:
        score_entity.score = score
        if score < 1:
            try:
                favorites.unset_favorite(entity, user)
            except favorites.InvalidFavoriteTargetError:
                pass
    else:
        table, get_column = _get_table_info(entity)
        score_entity = table()
        setattr(score_entity, get_column(table).name, get_column(entity))
        score_entity.score = score
        score_entity.user = user
        score_entity.time = datetime.datetime.utcnow()
        db.session.add(score_entity)
