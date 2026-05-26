from typing import Dict

from szurubooru import rest
from szurubooru.func import auth, file_uploads


@rest.routes.post("/uploads/?")
def create_temporary_file(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "uploads:create")
    content = ctx.get_file(
        "content",
        allow_tokens=False,
        use_downloader=auth.has_privilege(
            ctx.user, "uploads:use_downloader"
        ),
    )
    token = file_uploads.save(content)
    return {"token": token}
