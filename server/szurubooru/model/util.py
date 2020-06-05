from typing import Any, Callable, Dict, Optional, Tuple, Union

import sqlalchemy as sa

from szurubooru.model.base import Base
from szurubooru.model.user import User


def get_resource_info(entity: Base) -> Tuple[Any, Any, Union[str, int]]:
    serializers = {
        "tag": lambda tag: tag.first_name,
        "tag_category": lambda category: category.name,
        "comment": lambda comment: comment.comment_id,
        "post": lambda post: post.post_id,
        "pool": lambda pool: pool.pool_id,
        "pool_category": lambda category: category.name,
    }  # type: Dict[str, Callable[[Base], Any]]

    resource_type = entity.__table__.name
    assert resource_type in serializers

    primary_key = sa.inspection.inspect(entity).identity  # type: Any
    assert primary_key is not None
    assert len(primary_key) == 1

    resource_name = serializers[resource_type](entity)  # type: Union[str, int]
    assert resource_name

    resource_pkey = primary_key[0]  # type: Any
    assert resource_pkey

    return (resource_type, resource_pkey, resource_name)


def get_aux_entity(
    session: Any,
    get_table_info: Callable[[Base], Tuple[Base, Callable[[Base], Any]]],
    entity: Base,
    user: User,
) -> Optional[Base]:
    table, get_column = get_table_info(entity)
    return (
        session.query(table)
        .filter(get_column(table) == get_column(entity))
        .filter(table.user_id == user.user_id)
        .one_or_none()
    )
