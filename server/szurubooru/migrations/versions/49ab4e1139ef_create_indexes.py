"""
Create indexes

Revision ID: 49ab4e1139ef
Created at: 2016-05-09 09:38:28.078936
"""

import sqlalchemy as sa
from alembic import op

revision = "49ab4e1139ef"
down_revision = "23abaf4a0a4b"
branch_labels = None
depends_on = None


def upgrade():
    for index_name, table_name, column_name in [
        ("ix_comment_post_id", "comment", "post_id"),
        ("ix_comment_user_id", "comment", "user_id"),
        ("ix_comment_score_user_id", "comment_score", "user_id"),
        ("ix_post_user_id", "post", "user_id"),
        ("ix_post_favorite_post_id", "post_favorite", "post_id"),
        ("ix_post_favorite_user_id", "post_favorite", "user_id"),
        ("ix_post_feature_post_id", "post_feature", "post_id"),
        ("ix_post_feature_user_id", "post_feature", "user_id"),
        ("ix_post_note_post_id", "post_note", "post_id"),
        ("ix_post_relation_child_id", "post_relation", "child_id"),
        ("ix_post_relation_parent_id", "post_relation", "parent_id"),
        ("ix_post_score_post_id", "post_score", "post_id"),
        ("ix_post_score_user_id", "post_score", "user_id"),
        ("ix_post_tag_post_id", "post_tag", "post_id"),
        ("ix_post_tag_tag_id", "post_tag", "tag_id"),
        ("ix_snapshot_resource_id", "snapshot", "resource_id"),
        ("ix_snapshot_resource_type", "snapshot", "resource_type"),
        ("ix_tag_category_id", "tag", "category_id"),
        ("ix_tag_implication_child_id", "tag_implication", "child_id"),
        ("ix_tag_implication_parent_id", "tag_implication", "parent_id"),
        ("ix_tag_name_tag_id", "tag_name", "tag_id"),
        ("ix_tag_suggestion_child_id", "tag_suggestion", "child_id"),
        ("ix_tag_suggestion_parent_id", "tag_suggestion", "parent_id"),
    ]:
        op.create_index(
            op.f(index_name), table_name, [column_name], unique=False
        )


def downgrade():
    for index_name, table_name in [
        ("ix_tag_suggestion_parent_id", "tag_suggestion"),
        ("ix_tag_suggestion_child_id", "tag_suggestion"),
        ("ix_tag_name_tag_id", "tag_name"),
        ("ix_tag_implication_parent_id", "tag_implication"),
        ("ix_tag_implication_child_id", "tag_implication"),
        ("ix_tag_category_id", "tag"),
        ("ix_snapshot_resource_type", "snapshot"),
        ("ix_snapshot_resource_id", "snapshot"),
        ("ix_post_tag_tag_id", "post_tag"),
        ("ix_post_tag_post_id", "post_tag"),
        ("ix_post_score_user_id", "post_score"),
        ("ix_post_score_post_id", "post_score"),
        ("ix_post_relation_parent_id", "post_relation"),
        ("ix_post_relation_child_id", "post_relation"),
        ("ix_post_note_post_id", "post_note"),
        ("ix_post_feature_user_id", "post_feature"),
        ("ix_post_feature_post_id", "post_feature"),
        ("ix_post_favorite_user_id", "post_favorite"),
        ("ix_post_favorite_post_id", "post_favorite"),
        ("ix_post_user_id", "post"),
        ("ix_comment_score_user_id", "comment_score"),
        ("ix_comment_user_id", "comment"),
        ("ix_comment_post_id", "comment"),
    ]:
        op.drop_index(op.f(index_name), table_name=table_name)
