from typing import List

import sqlalchemy as sa

from szurubooru import db, model, search

_search_executor_config = search.configs.PostSearchConfig()
_search_executor = search.Executor(_search_executor_config)


# TODO(hunternif): this ignores the query, e.g. rating.
# (But we're actually using a "similar" search query on the client anyway.)
def find_similar_posts(
    source_post: model.Post, limit: int, query_text: str = ''
) -> List[model.Post]:
    post_alias = sa.orm.aliased(model.Post)
    pt_alias = sa.orm.aliased(model.PostTag)
    result = (
        db.session.query(post_alias)
        .join(pt_alias, pt_alias.post_id == post_alias.post_id)
        .filter(
            sa.sql.or_(
                pt_alias.tag_id == tag.tag_id for tag in source_post.tags
            )
        )
        .filter(pt_alias.post_id != source_post.post_id)
        .group_by(post_alias.post_id)
        .order_by(sa.func.count(pt_alias.tag_id).desc())
        .order_by(post_alias.post_id.desc())
        .limit(limit)
    )
    return result
