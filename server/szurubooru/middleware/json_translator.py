import cgi
import datetime
import json
import falcon

def json_serializer(obj):
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
        if request.content_length in (None, 0):
            return

        request.context.files = {}
        if 'multipart/form-data' in (request.content_type or ''):
            # obscure, claims to "avoid a bug in cgi.FieldStorage"
            request.env.setdefault('QUERY_STRING', '')

            form = cgi.FieldStorage(fp=request.stream, environ=request.env)
            for key in form:
                if key != 'metadata':
                    _original_file_name = getattr(form[key], 'filename', None)
                    request.context.files[key] = form.getvalue(key)
            body = form.getvalue('metadata')
        else:
            body = request.stream.read()

        if not body:
            raise falcon.HTTPBadRequest(
                'Empty request body',
                'A valid JSON document is required.')

        try:
            if isinstance(body, bytes):
                body = body.decode('utf-8')

            request.context.request = json.loads(body)
        except (ValueError, UnicodeDecodeError):
            raise falcon.HTTPError(
                falcon.HTTP_401,
                'Malformed JSON',
                'Could not decode the request body. The '
                'JSON was incorrect or not encoded as UTF-8.')

    def process_response(self, request, response, _resource):
        if 'result' not in request.context:
            return
        response.body = json.dumps(
            request.context.result, default=json_serializer, indent=2)
