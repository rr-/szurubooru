import sqlalchemy as sa

from szurubooru.model.base import Base


class Snapshot(Base):
    __tablename__ = "snapshot"

    OPERATION_CREATED = "created"
    OPERATION_MODIFIED = "modified"
    OPERATION_DELETED = "deleted"
    OPERATION_MERGED = "merged"

    snapshot_id = sa.Column("id", sa.Integer, primary_key=True)
    creation_time = sa.Column("creation_time", sa.DateTime, nullable=False)
    operation = sa.Column("operation", sa.Unicode(16), nullable=False)
    resource_type = sa.Column(
        "resource_type", sa.Unicode(32), nullable=False, index=True
    )
    resource_pkey = sa.Column(
        "resource_pkey", sa.Integer, nullable=False, index=True
    )
    resource_name = sa.Column("resource_name", sa.Unicode(128), nullable=False)
    user_id = sa.Column(
        "user_id",
        sa.Integer,
        sa.ForeignKey("user.id", ondelete="set null"),
        nullable=True,
    )
    data = sa.Column("data", sa.PickleType)

    user = sa.orm.relationship("User")
