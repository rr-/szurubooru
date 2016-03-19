import json
import falcon

class JsonTranslator(object):
    def process_request(self, request, response):
        if request.content_length in (None, 0):
            return

        body = request.stream.read()
        if not body:
            raise falcon.HTTPBadRequest(
                'Empty request body',
                'A valid JSON document is required.')

        try:
            request.context['doc'] = json.loads(body.decode('utf-8'))
        except (ValueError, UnicodeDecodeError):
            raise falcon.HTTPError(
                falcon.HTTP_401,
                'Malformed JSON',
                'Could not decode the request body. The '
                'JSON was incorrect or not encoded as UTF-8.')

    def process_response(self, request, response, resource):
        if 'result' not in request.context:
            return
        response.body = json.dumps(request.context['result'])
