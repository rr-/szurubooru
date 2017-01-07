from szurubooru.rest import routes
from szurubooru.func import auth, file_uploads


@routes.post('/uploads/?')
def create_temporary_file(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'uploads:create')
    content = ctx.get_file('content', required=True, allow_tokens=False)
    token = file_uploads.save(content)
    return {'token': token}
