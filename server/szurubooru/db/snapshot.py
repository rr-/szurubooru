from sqlalchemy import Column, Integer, DateTime, Unicode, PickleType, ForeignKey
from sqlalchemy.orm import relationship
from szurubooru.db.base import Base

class Snapshot(Base):
    __tablename__ = 'snapshot'

    OPERATION_CREATED = 'created'
    OPERATION_MODIFIED = 'modified'
    OPERATION_DELETED = 'deleted'

    snapshot_id = Column('id', Integer, primary_key=True)
    creation_time = Column('creation_time', DateTime, nullable=False)
    resource_type = Column('resource_type', Unicode(32), nullable=False)
    resource_id = Column('resource_id', Integer, nullable=False)
    resource_repr = Column('resource_repr', Unicode(64), nullable=False)
    operation = Column('operation', Unicode(16), nullable=False)
    user_id = Column('user_id', Integer, ForeignKey('user.id'))
    data = Column('data', PickleType)

    user = relationship('User')
