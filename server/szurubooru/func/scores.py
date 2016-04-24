import datetime
from szurubooru import db, errors
from szurubooru.func import util

class InvalidScoreError(errors.ValidationError): pass

def _get_table_info(entity):
    resource_type, _, _ = util.get_resource_info(entity)
    if resource_type == 'post':
        return db.PostScore, lambda table: table.post_id
    elif resource_type == 'comment':
        return db.CommentScore, lambda table: table.comment_id
    else:
        assert False

def _get_score_entity(entity, user):
    table, get_column = _get_table_info(entity)
    return db.session \
        .query(table) \
        .filter(get_column(table) == get_column(entity)) \
        .filter(table.user_id == user.user_id) \
        .one_or_none()

def delete_score(entity, user):
    score_entity = _get_score_entity(entity, user)
    if score_entity:
        db.session.delete(score_entity)

def get_score(entity, user):
    score_entity = _get_score_entity(entity, user)
    if score_entity:
        return score_entity.score
    else:
        return 0

def set_score(entity, user, score):
    if not score:
        delete_score(entity, user)
        return
    if score not in (-1, 1):
        raise InvalidScoreError(
            'Score %r is invalid. Valid scores: %r.' % (score, (-1, 1)))
    score_entity = _get_score_entity(entity, user)
    if score_entity:
        score_entity.score = score
    else:
        table, get_column = _get_table_info(entity)
        score_entity = table()
        setattr(score_entity, get_column(table).name, get_column(entity))
        score_entity.score = score
        score_entity.user = user
        score_entity.time = datetime.datetime.now()
        db.session.add(score_entity)
