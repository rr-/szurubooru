''' Exports create_app. '''

import os
import logging
import coloredlogs
import sqlalchemy.orm.exc
from szurubooru import config, errors, rest
# pylint: disable=unused-import
from szurubooru import api, middleware


def _on_auth_error(ex):
    raise rest.errors.HttpForbidden(
        title='Authentication error', description=str(ex))


def _on_validation_error(ex):
    raise rest.errors.HttpBadRequest(
        title='Validation error', description=str(ex))


def _on_search_error(ex):
    raise rest.errors.HttpBadRequest(
        title='Search error', description=str(ex))


def _on_integrity_error(ex):
    raise rest.errors.HttpConflict(
        title='Integrity violation', description=ex.args[0])


def _on_not_found_error(ex):
    raise rest.errors.HttpNotFound(
        title='Not found', description=str(ex))


def _on_processing_error(ex):
    raise rest.errors.HttpBadRequest(
        title='Processing error', description=str(ex))


def _on_stale_data_error(_ex):
    raise rest.errors.HttpConflict(
        'Someone else modified this in the meantime. Please try again.')


def validate_config():
    '''
    Check whether config doesn't contain errors that might prove
    lethal at runtime.
    '''
    from szurubooru.func.auth import RANK_MAP
    for privilege, rank in config.config['privileges'].items():
        if rank not in RANK_MAP.values():
            raise errors.ConfigError(
                'Rank %r for privilege %r is missing' % (rank, privilege))
    if config.config['default_rank'] not in RANK_MAP.values():
        raise errors.ConfigError(
            'Default rank %r is not on the list of known ranks' % (
                config.config['default_rank']))

    for key in ['base_url', 'api_url', 'data_url', 'data_dir']:
        if not config.config[key]:
            raise errors.ConfigError(
                'Service is not configured: %r is missing' % key)

    if not os.path.isabs(config.config['data_dir']):
        raise errors.ConfigError(
            'data_dir must be an absolute path')

    if not config.config['database']:
        raise errors.ConfigError('Database is not configured')


def create_app():
    ''' Create a WSGI compatible App object. '''
    validate_config()
    coloredlogs.install(fmt='[%(asctime)-15s] %(name)s %(message)s')
    if config.config['debug']:
        logging.getLogger('szurubooru').setLevel(logging.INFO)
    if config.config['show_sql']:
        logging.getLogger('sqlalchemy.engine').setLevel(logging.INFO)

    rest.errors.handle(errors.AuthError, _on_auth_error)
    rest.errors.handle(errors.ValidationError, _on_validation_error)
    rest.errors.handle(errors.SearchError, _on_search_error)
    rest.errors.handle(errors.IntegrityError, _on_integrity_error)
    rest.errors.handle(errors.NotFoundError, _on_not_found_error)
    rest.errors.handle(errors.ProcessingError, _on_processing_error)
    rest.errors.handle(sqlalchemy.orm.exc.StaleDataError, _on_stale_data_error)

    return rest.application
