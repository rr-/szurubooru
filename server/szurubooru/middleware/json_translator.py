import datetime
import json
import falcon

def json_serial(obj):
    ''' JSON serializer for objects not serializable by default JSON code '''
    if isinstance(obj, datetime.datetime):
        serial = obj.isoformat()
        return serial
    raise TypeError('Type not serializable')

class JsonTranslator(object):
    '''
    Translates API requests and API responses to JSON using requests'
    context.
    '''

    def process_request(self, request, _response):
        ''' Executed before passing the request to the API. '''
        if request.content_length in (None, 0):
            return

        body = request.stream.read()
        if not body:
            raise falcon.HTTPBadRequest(
                'Empty request body',
                'A valid JSON document is required.')

        try:
            request.context.request = json.loads(body.decode('utf-8'))
        except (ValueError, UnicodeDecodeError):
            raise falcon.HTTPError(
                falcon.HTTP_401,
                'Malformed JSON',
                'Could not decode the request body. The '
                'JSON was incorrect or not encoded as UTF-8.')

    def process_response(self, request, response, _resource):
        ''' Executed before passing the response to falcon. '''
        if 'result' not in request.context:
            return
        response.body = json.dumps(
            request.context.result, default=json_serial, indent=2)
