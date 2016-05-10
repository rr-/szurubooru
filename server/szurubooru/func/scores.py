import datetime
from szurubooru import db, errors

class InvalidScoreError(errors.ValidationError): pass

def _get_table_info(entity):
    resource_type, _, _ = db.util.get_resource_info(entity)
    if resource_type == 'post':
        return db.PostScore, lambda table: table.post_id
    elif resource_type == 'comment':
        return db.CommentScore, lambda table: table.comment_id
    assert False

def _get_score_entity(entity, user):
    return db.util.get_aux_entity(db.session, _get_table_info, entity, user)

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
