from sqlalchemy.inspection import inspect

def get_resource_info(entity):
    serializers = {
        'tag': lambda tag: tag.first_name,
        'tag_category': lambda category: category.name,
        'comment': lambda comment: comment.comment_id,
        'post': lambda post: post.post_id,
    }

    resource_type = entity.__table__.name
    assert resource_type in serializers

    primary_key = inspect(entity).identity
    assert primary_key is not None
    assert len(primary_key) == 1

    resource_repr = serializers[resource_type](entity)
    assert resource_repr

    resource_id = primary_key[0]
    assert resource_id

    return (resource_type, resource_id, resource_repr)

def get_aux_entity(session, get_table_info, entity, user):
    table, get_column = get_table_info(entity)
    return session \
        .query(table) \
        .filter(get_column(table) == get_column(entity)) \
        .filter(table.user_id == user.user_id) \
        .one_or_none()
