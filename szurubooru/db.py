''' Exports TransactionManager. '''

from contextlib import contextmanager

class TransactionManager(object):
    ''' Helper class for managing database transactions. '''

    def __init__(self, session_factory):
        self._session_factory = session_factory

    @contextmanager
    def transaction(self):
        '''
        Provides a transactional scope around a series of DB operations that
        might change the database.
        '''
        return self._open_transaction(lambda session: session.commit)

    @contextmanager
    def read_only_transaction(self):
        '''
        Provides a transactional scope around a series of read-only DB
        operations.
        '''
        return self._open_transaction(lambda session: session.rollback)

    def _open_transaction(self, session_finalizer):
        session = self._session_factory()
        try:
            yield session
            session_finalizer(session)
        except:
            session.rollback()
            raise
        finally:
            session.close()
