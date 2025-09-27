from datetime import datetime
from typing import Any, Callable, Dict, List, Optional, Tuple

from szurubooru import db, errors, model, rest
from szurubooru.func import (
    serialization,
)

class PostBannedError(errors.ValidationError):
    def __init__(self, message: str = "This file was banned", extra_fields: Dict[str, str] = None) -> None:
        super().__init__(message, extra_fields)


class HashNotBannedError(errors.ValidationError):
    pass


class BanSerializer(serialization.BaseSerializer):
    def __init__(self, ban: model.PostBan) -> None:
        self.ban = ban

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "checksum": self.serialize_checksum,
            "time": self.serialize_time
        }

    def serialize_checksum(self) -> Any:
        return self.ban.checksum

    def serialize_time(self) -> Any:
        return self.ban.time


def create_ban(post: model.Post) -> model.PostBan:
    ban = model.PostBan()
    ban.checksum = post.checksum
    ban.time = datetime.utcnow()

    db.session.add(ban)
    return ban


def try_get_ban_by_checksum(checksum: str) -> Optional[model.PostBan]:
    return (
        db.session.query(model.PostBan)
        .filter(model.PostBan.checksum == checksum)
        .one_or_none()
    )


def get_bans_by_hash(hash: str) -> model.PostBan:
    ban = try_get_ban_by_checksum(hash)
    if ban is None:
        raise HashNotBannedError("Hash %s is not banned" % hash)
    return ban


def delete(ban: model.PostBan) -> None:
    db.session.delete(ban)


def serialize_ban(
    ban: model.PostBan, options: List[str] = []
) -> Optional[rest.Response]:
    if not ban:
        return None
    return BanSerializer(ban).serialize(options)
