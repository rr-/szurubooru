from sqlalchemy import Column, Integer, DateTime, String, PickleType, ForeignKey
from sqlalchemy.orm import relationship
from szurubooru.db.base import Base

class Snapshot(Base):
    __tablename__ = 'snapshot'

    OPERATION_CREATED = 'added'
    OPERATION_MODIFIED = 'modified'
    OPERATION_DELETED = 'deleted'

    snapshot_id = Column('id', Integer, primary_key=True)
    creation_time = Column('creation_time', DateTime, nullable=False)
    resource_type = Column('resource_type', String(32), nullable=False)
    resource_id = Column('resource_id', Integer, nullable=False)
    operation = Column('operation', String(16), nullable=False)
    user_id = Column('user_id', Integer, ForeignKey('user.id'))
    data = Column('data', PickleType)

    user = relationship('User')
