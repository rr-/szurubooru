from sqlalchemy.orm import relationship
from sqlalchemy import (
    Column, Integer, DateTime, Unicode, PickleType, ForeignKey)
from szurubooru.model.base import Base


class Snapshot(Base):
    __tablename__ = 'snapshot'

    OPERATION_CREATED = 'created'
    OPERATION_MODIFIED = 'modified'
    OPERATION_DELETED = 'deleted'
    OPERATION_MERGED = 'merged'

    snapshot_id = Column('id', Integer, primary_key=True)
    creation_time = Column('creation_time', DateTime, nullable=False)
    operation = Column('operation', Unicode(16), nullable=False)
    resource_type = Column(
        'resource_type', Unicode(32), nullable=False, index=True)
    resource_pkey = Column(
        'resource_pkey', Integer, nullable=False, index=True)
    resource_name = Column(
        'resource_name', Unicode(64), nullable=False)
    user_id = Column(
        'user_id',
        Integer,
        ForeignKey('user.id', ondelete='set null'),
        nullable=True)
    data = Column('data', PickleType)

    user = relationship('User')
