''' Exports Config. '''

import os
import configobj

class Config(object):
    ''' INI config parser and container. '''
    def __init__(self):
        self.config = configobj.ConfigObj('config.ini.dist')
        if os.path.exists('config.ini'):
            self.config.merge(configobj.ConfigObj('config.ini'))

    def __getitem__(self, key):
        return self.config[key]
