''' Base model for every database resource. '''

from sqlalchemy.ext.declarative import declarative_base
Base = declarative_base()  # pylint: disable=C0103
