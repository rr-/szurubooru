"""
Add file last modified time

Revision ID: 46c358b0ca93
Created at: 2020-08-26 17:08:17.845827
"""

import sqlalchemy as sa
from alembic import op

revision = "46c358b0ca93"
down_revision = "54de8acc6cef"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column(
        "post",
        sa.Column("file_last_modified_time", sa.DateTime(), nullable=True),
    )

    op.execute(
        """
        DO
        $do$
        DECLARE creation_time_candidate TIMESTAMP;
        BEGIN
        WHILE EXISTS (
            SELECT creation_time from "post"
            WHERE file_last_modified_time IS NULL
        ) LOOP
            FOR creation_time_candidate IN (
                SELECT creation_time FROM "post"
                WHERE file_last_modified_time IS NULL
            ) LOOP
                UPDATE "post"
                SET file_last_modified_time = creation_time
                WHERE
                    NOT EXISTS (
                        SELECT creation_time FROM "post"
                        WHERE file_last_modified_time = creation_time_candidate
                );
            END LOOP;
        END LOOP;
        END
        $do$
        """
    )

    op.alter_column("post", "file_last_modified_time", nullable=False)


def downgrade():
    op.drop_column("post", "file_last_modified_time")
