''' Exports Config. '''

import os
import configobj

class ConfigurationError(RuntimeError):
    ''' A problem with config.ini file. '''
    pass

class Config(object):
    ''' INI config parser and container. '''
    def __init__(self):
        self.config = configobj.ConfigObj('../config.ini.dist')
        if os.path.exists('../config.ini'):
            self.config.merge(configobj.ConfigObj('config.ini'))
        self._validate()

    def __getitem__(self, key):
        return self.config[key]

    def _validate(self):
        '''
        Checks whether config.ini doesn't contain errors that might prove
        lethal at runtime.
        '''
        all_ranks = self['service']['user_ranks']
        for privilege, rank in self['privileges'].items():
            if rank not in all_ranks:
                raise ConfigurationError(
                    'Rank %r for privilege %r is missing from user_ranks' % (
                        rank, privilege))
        for rank in ['anonymous', 'admin', 'nobody']:
            if rank not in all_ranks:
                raise ConfigurationError('Fixed rank %r is missing from user_ranks' % rank)
        if self['service']['default_user_rank'] not in all_ranks:
            raise ConfigurationError(
                'Default rank %r is missing from user_ranks' % (
                    self['service']['default_user_rank']))
