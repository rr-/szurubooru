import unittest
import sqlalchemy
from szurubooru.model import Base

class DatabaseTestCase(unittest.TestCase):
    def setUp(self):
        engine = sqlalchemy.create_engine('sqlite:///:memory:')
        session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
        self.session = sqlalchemy.orm.scoped_session(session_maker)
        Base.query = self.session.query_property()
        Base.metadata.create_all(bind=engine)
