from datetime import datetime
from typing import Any, Optional, List, Dict, Callable

from szurubooru import db, model, rest
from szurubooru.func import auth, serialization, users


class UserTokenSerializer(serialization.BaseSerializer):
    def __init__(
            self,
            user_token: model.UserToken,
            auth_user: model.User) -> None:
        self.user_token = user_token
        self.auth_user = auth_user

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            'user': self.serialize_user,
            'token': self.serialize_token,
            'enabled': self.serialize_enabled,
            'version': self.serialize_version,
            'creationTime': self.serialize_creation_time,
            'lastEditTime': self.serialize_last_edit_time,
        }

    def serialize_user(self) -> Any:
        return users.serialize_micro_user(self.user_token.user, self.auth_user)

    def serialize_creation_time(self) -> Any:
        return self.user_token.creation_time

    def serialize_last_edit_time(self) -> Any:
        return self.user_token.last_edit_time

    def serialize_token(self) -> Any:
        return self.user_token.token

    def serialize_enabled(self) -> Any:
        return self.user_token.enabled

    def serialize_version(self) -> Any:
        return self.user_token.version


def serialize_user_token(
        user_token: Optional[model.UserToken],
        auth_user: model.User,
        options: List[str] = []) -> Optional[rest.Response]:
    if not user_token:
        return None
    return UserTokenSerializer(user_token, auth_user).serialize(options)


def get_user_token_by_user_and_token(user: model.User, token: str) -> model.UserToken:
    return (db.session.query(model.UserToken)
            .filter(model.UserToken.user_id == user.user_id, model.UserToken.token == token)
            .one_or_none())


def get_user_tokens(user: model.User) -> List[model.UserToken]:
    assert user
    return (db.session.query(model.UserToken)
            .filter(model.UserToken.user_id == user.user_id)
            .all())


def create_user_token(user: model.User) -> model.UserToken:
    assert user
    user_token = model.UserToken()
    user_token.user = user
    user_token.token = auth.generate_authorization_token()
    user_token.enabled = True
    user_token.creation_time = datetime.utcnow()
    db.session.add(user_token)
    db.session.commit()
    return user_token


def update_user_token_enabled(user_token: model.UserToken, enabled: bool) -> None:
    assert user_token
    user_token.enabled = enabled if enabled is not None else True


def update_user_token_edit_time(user_token: model.UserToken) -> None:
    assert user_token
    user_token.last_edit_time = datetime.utcnow()