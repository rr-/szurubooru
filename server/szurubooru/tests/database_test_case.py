import unittest
import sqlalchemy
from szurubooru import db

class DatabaseTestCase(unittest.TestCase):
    def setUp(self):
        engine = sqlalchemy.create_engine('sqlite:///:memory:')
        session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
        self.session = sqlalchemy.orm.scoped_session(session_maker)
        db.Base.query = self.session.query_property()
        db.Base.metadata.create_all(bind=engine)
