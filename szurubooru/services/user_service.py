from szurubooru.model.user import User

class UserService(object):
    def __init__(self, session):
        self._session = session

    def get_by_name(self, name):
        self._session.query(User).filter_by(name=name).first()
