'''
add get pool posts around function

Revision ID: f0b8a4298dc7
Created at: 2021-05-08 21:23:48.782025
'''

import sqlalchemy as sa
from alembic import op

from alembic_utils.pg_function import PGFunction
from sqlalchemy import text as sql_text

revision = 'f0b8a4298dc7'
down_revision = 'adcd63ff76a2'
branch_labels = None
depends_on = None

def upgrade():
    public_get_pool_posts_around = PGFunction(
        schema="public",
        signature="get_pool_posts_around( P_POOL_ID int, P_POST_ID int )",
        definition='returns TABLE (\n    ORD int,\n    POOL_ID int,\n    POST_ID int,\n    DELTA int\n  )\n  LANGUAGE PLPGSQL\nAS $$\nBEGIN\n  RETURN QUERY WITH main AS (\n    SELECT * FROM pool_post WHERE pool_post.pool_id = P_POOL_ID AND pool_post.post_id = P_POST_ID\n  ),\n    around AS (\n      (SELECT pool_post.ord,\n              pool_post.pool_id,\n              pool_post.post_id,\n              1 as delta,\n              main.ord AS target_ord,\n              main.pool_id AS target_pool_id\n         FROM pool_post, main\n        WHERE pool_post.ord > main.ord\n          AND pool_post.pool_id = main.pool_id\n        ORDER BY pool_post.ord ASC LIMIT 1)\n        UNION\n        (SELECT pool_post.ord,\n                pool_post.pool_id,\n                pool_post.post_id,\n                -1 as delta,\n                main.ord AS target_ord,\n                main.pool_id AS target_pool_id\n           FROM pool_post, main\n          WHERE pool_post.ord < main.ord\n            AND pool_post.pool_id = main.pool_id\n          ORDER BY pool_post.ord DESC LIMIT 1)\n    )\n      SELECT around.ord, around.pool_id, around.post_id, around.delta FROM around;\nEND\n$$'
    )
    op.create_entity(public_get_pool_posts_around)

def downgrade():
    public_get_pool_posts_around = PGFunction(
        schema="public",
        signature="get_pool_posts_around( P_POOL_ID int, P_POST_ID int )",
        definition='returns TABLE (\n    ORD int,\n    POOL_ID int,\n    POST_ID int,\n    DELTA int\n  )\n  LANGUAGE PLPGSQL\nAS $$\nBEGIN\n  RETURN QUERY WITH main AS (\n    SELECT * FROM pool_post WHERE pool_post.pool_id = P_POOL_ID AND pool_post.post_id = P_POST_ID\n  ),\n    around AS (\n      (SELECT pool_post.ord,\n              pool_post.pool_id,\n              pool_post.post_id,\n              1 as delta,\n              main.ord AS target_ord,\n              main.pool_id AS target_pool_id\n         FROM pool_post, main\n        WHERE pool_post.ord > main.ord\n          AND pool_post.pool_id = main.pool_id\n        ORDER BY pool_post.ord ASC LIMIT 1)\n        UNION\n        (SELECT pool_post.ord,\n                pool_post.pool_id,\n                pool_post.post_id,\n                -1 as delta,\n                main.ord AS target_ord,\n                main.pool_id AS target_pool_id\n           FROM pool_post, main\n          WHERE pool_post.ord < main.ord\n            AND pool_post.pool_id = main.pool_id\n          ORDER BY pool_post.ord DESC LIMIT 1)\n    )\n      SELECT around.ord, around.pool_id, around.post_id, around.delta FROM around;\nEND\n$$'
    )
    op.drop_entity(public_get_pool_posts_around)
