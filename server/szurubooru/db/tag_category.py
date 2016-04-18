from sqlalchemy import Column, Integer, String
from szurubooru.db.base import Base

class TagCategory(Base):
    __tablename__ = 'tag_category'

    tag_category_id = Column('id', Integer, primary_key=True)
    name = Column('name', String(32), nullable=False)
    color = Column('color', String(32), nullable=False, default='#000000')

    def __init__(self, name):
        self.name = name
