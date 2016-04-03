class ImbueContext(object):
    ''' Decorates context with methods from falcon's request. '''

    def process_request(self, request, _response):
        request.context.get_param_as_string = request.get_param_as_string
        request.context.get_param_as_bool = request.get_param_as_bool
        request.context.get_param_as_int = request.get_param_as_int
        request.context.get_param_as_list = request.get_param_as_list
