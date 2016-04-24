'''
Delete post columns

Revision ID: ed6dd16a30f3
Created at: 2016-04-24 16:29:25.309154
'''

import sqlalchemy as sa
from alembic import op

revision = 'ed6dd16a30f3'
down_revision = '46df355634dc'
branch_labels = None
depends_on = None

def upgrade():
    op.drop_column('post', 'auto_comment_edit_time')
    op.drop_column('post', 'auto_fav_count')
    op.drop_column('post', 'auto_comment_creation_time')
    op.drop_column('post', 'auto_feature_count')
    op.drop_column('post', 'auto_comment_count')
    op.drop_column('post', 'auto_score')
    op.drop_column('post', 'auto_fav_time')
    op.drop_column('post', 'auto_feature_time')
    op.drop_column('post', 'auto_note_count')

def downgrade():
    op.add_column('post', sa.Column('auto_note_count', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_feature_time', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_fav_time', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_score', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_comment_count', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_feature_count', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_comment_creation_time', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_fav_count', sa.INTEGER(), autoincrement=False, nullable=False))
    op.add_column('post', sa.Column('auto_comment_edit_time', sa.INTEGER(), autoincrement=False, nullable=False))
