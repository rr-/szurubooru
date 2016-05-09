'''
Create indexes

Revision ID: 49ab4e1139ef
Created at: 2016-05-09 09:38:28.078936
'''

import sqlalchemy as sa
from alembic import op

revision = '49ab4e1139ef'
down_revision = '23abaf4a0a4b'
branch_labels = None
depends_on = None

def upgrade():
    op.create_index(op.f('ix_comment_post_id'), 'comment', ['post_id'], unique=False)
    op.create_index(op.f('ix_comment_user_id'), 'comment', ['user_id'], unique=False)
    op.create_index(op.f('ix_comment_score_user_id'), 'comment_score', ['user_id'], unique=False)
    op.create_index(op.f('ix_post_user_id'), 'post', ['user_id'], unique=False)
    op.create_index(op.f('ix_post_favorite_post_id'), 'post_favorite', ['post_id'], unique=False)
    op.create_index(op.f('ix_post_favorite_user_id'), 'post_favorite', ['user_id'], unique=False)
    op.create_index(op.f('ix_post_feature_post_id'), 'post_feature', ['post_id'], unique=False)
    op.create_index(op.f('ix_post_feature_user_id'), 'post_feature', ['user_id'], unique=False)
    op.create_index(op.f('ix_post_note_post_id'), 'post_note', ['post_id'], unique=False)
    op.create_index(op.f('ix_post_relation_child_id'), 'post_relation', ['child_id'], unique=False)
    op.create_index(op.f('ix_post_relation_parent_id'), 'post_relation', ['parent_id'], unique=False)
    op.create_index(op.f('ix_post_score_post_id'), 'post_score', ['post_id'], unique=False)
    op.create_index(op.f('ix_post_score_user_id'), 'post_score', ['user_id'], unique=False)
    op.create_index(op.f('ix_post_tag_post_id'), 'post_tag', ['post_id'], unique=False)
    op.create_index(op.f('ix_post_tag_tag_id'), 'post_tag', ['tag_id'], unique=False)
    op.create_index(op.f('ix_snapshot_resource_id'), 'snapshot', ['resource_id'], unique=False)
    op.create_index(op.f('ix_snapshot_resource_type'), 'snapshot', ['resource_type'], unique=False)
    op.create_index(op.f('ix_tag_category_id'), 'tag', ['category_id'], unique=False)
    op.create_index(op.f('ix_tag_implication_child_id'), 'tag_implication', ['child_id'], unique=False)
    op.create_index(op.f('ix_tag_implication_parent_id'), 'tag_implication', ['parent_id'], unique=False)
    op.create_index(op.f('ix_tag_name_tag_id'), 'tag_name', ['tag_id'], unique=False)
    op.create_index(op.f('ix_tag_suggestion_child_id'), 'tag_suggestion', ['child_id'], unique=False)
    op.create_index(op.f('ix_tag_suggestion_parent_id'), 'tag_suggestion', ['parent_id'], unique=False)

def downgrade():
    op.drop_index(op.f('ix_tag_suggestion_parent_id'), table_name='tag_suggestion')
    op.drop_index(op.f('ix_tag_suggestion_child_id'), table_name='tag_suggestion')
    op.drop_index(op.f('ix_tag_name_tag_id'), table_name='tag_name')
    op.drop_index(op.f('ix_tag_implication_parent_id'), table_name='tag_implication')
    op.drop_index(op.f('ix_tag_implication_child_id'), table_name='tag_implication')
    op.drop_index(op.f('ix_tag_category_id'), table_name='tag')
    op.drop_index(op.f('ix_snapshot_resource_type'), table_name='snapshot')
    op.drop_index(op.f('ix_snapshot_resource_id'), table_name='snapshot')
    op.drop_index(op.f('ix_post_tag_tag_id'), table_name='post_tag')
    op.drop_index(op.f('ix_post_tag_post_id'), table_name='post_tag')
    op.drop_index(op.f('ix_post_score_user_id'), table_name='post_score')
    op.drop_index(op.f('ix_post_score_post_id'), table_name='post_score')
    op.drop_index(op.f('ix_post_relation_parent_id'), table_name='post_relation')
    op.drop_index(op.f('ix_post_relation_child_id'), table_name='post_relation')
    op.drop_index(op.f('ix_post_note_post_id'), table_name='post_note')
    op.drop_index(op.f('ix_post_feature_user_id'), table_name='post_feature')
    op.drop_index(op.f('ix_post_feature_post_id'), table_name='post_feature')
    op.drop_index(op.f('ix_post_favorite_user_id'), table_name='post_favorite')
    op.drop_index(op.f('ix_post_favorite_post_id'), table_name='post_favorite')
    op.drop_index(op.f('ix_post_user_id'), table_name='post')
    op.drop_index(op.f('ix_comment_score_user_id'), table_name='comment_score')
    op.drop_index(op.f('ix_comment_user_id'), table_name='comment')
    op.drop_index(op.f('ix_comment_post_id'), table_name='comment')
