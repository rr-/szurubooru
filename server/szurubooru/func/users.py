import copy
import re
from datetime import datetime
from typing import Any, Callable, Dict, List, Optional, Union

import sqlalchemy as sa

from szurubooru import config, db, errors, model, rest
from szurubooru.func import auth, files, images, serialization, util, tags


class UserNotFoundError(errors.NotFoundError):
    pass


class UserAlreadyExistsError(errors.ValidationError):
    pass


class InvalidUserNameError(errors.ValidationError):
    pass


class InvalidEmailError(errors.ValidationError):
    pass


class InvalidPasswordError(errors.ValidationError):
    pass


class InvalidRankError(errors.ValidationError):
    pass


class InvalidAvatarError(errors.ValidationError):
    pass


def get_avatar_path(user_name: str) -> str:
    return "avatars/" + user_name.lower() + ".png"


def get_avatar_url(user: model.User) -> str:
    assert user
    if user.avatar_style == user.AVATAR_GRAVATAR:
        assert user.email or user.name
        return "https://gravatar.com/avatar/%s?d=retro&s=%d" % (
            util.get_md5((user.email or user.name).lower()),
            config.config["thumbnails"]["avatar_width"],
        )
    assert user.name
    return "%s/avatars/%s.png" % (
        config.config["data_url"].rstrip("/"),
        user.name.lower(),
    )


def get_email(
    user: model.User, auth_user: model.User, force_show_email: bool
) -> Union[bool, str]:
    assert user
    assert auth_user
    if (
        not force_show_email
        and auth_user.user_id != user.user_id
        and not auth.has_privilege(auth_user, "users:edit:any:email")
    ):
        return False
    return user.email


def get_liked_post_count(
    user: model.User, auth_user: model.User
) -> Union[bool, int]:
    assert user
    assert auth_user
    if auth_user.user_id != user.user_id:
        return False
    return user.liked_post_count


def get_disliked_post_count(
    user: model.User, auth_user: model.User
) -> Union[bool, int]:
    assert user
    assert auth_user
    if auth_user.user_id != user.user_id:
        return False
    return user.disliked_post_count


class UserSerializer(serialization.BaseSerializer):
    def __init__(
        self,
        user: model.User,
        auth_user: model.User,
        force_show_email: bool = False,
    ) -> None:
        self.user = user
        self.auth_user = auth_user
        self.force_show_email = force_show_email

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "name": self.serialize_name,
            "creationTime": self.serialize_creation_time,
            "lastLoginTime": self.serialize_last_login_time,
            "version": self.serialize_version,
            "rank": self.serialize_rank,
            "blocklist": self.serialize_blocklist,
            "avatarStyle": self.serialize_avatar_style,
            "avatarUrl": self.serialize_avatar_url,
            "commentCount": self.serialize_comment_count,
            "uploadedPostCount": self.serialize_uploaded_post_count,
            "favoritePostCount": self.serialize_favorite_post_count,
            "likedPostCount": self.serialize_liked_post_count,
            "dislikedPostCount": self.serialize_disliked_post_count,
            "email": self.serialize_email,
        }

    def serialize_name(self) -> Any:
        return self.user.name

    def serialize_creation_time(self) -> Any:
        return self.user.creation_time

    def serialize_last_login_time(self) -> Any:
        return self.user.last_login_time

    def serialize_version(self) -> Any:
        return self.user.version

    def serialize_rank(self) -> Any:
        return self.user.rank

    def serialize_avatar_style(self) -> Any:
        return self.user.avatar_style

    def serialize_avatar_url(self) -> Any:
        return get_avatar_url(self.user)

    def serialize_blocklist(self) -> Any:
        return [tags.serialize_tag(tag) for tag in get_blocklist_tag_from_user(self.user)]

    def serialize_comment_count(self) -> Any:
        return self.user.comment_count

    def serialize_uploaded_post_count(self) -> Any:
        return self.user.post_count

    def serialize_favorite_post_count(self) -> Any:
        return self.user.favorite_post_count

    def serialize_liked_post_count(self) -> Any:
        return get_liked_post_count(self.user, self.auth_user)

    def serialize_disliked_post_count(self) -> Any:
        return get_disliked_post_count(self.user, self.auth_user)

    def serialize_email(self) -> Any:
        return get_email(self.user, self.auth_user, self.force_show_email)


def serialize_user(
    user: Optional[model.User],
    auth_user: model.User,
    options: List[str] = [],
    force_show_email: bool = False,
) -> Optional[rest.Response]:
    if not user:
        return None
    return UserSerializer(user, auth_user, force_show_email).serialize(options)


def serialize_micro_user(
    user: Optional[model.User], auth_user: model.User
) -> Optional[rest.Response]:
    return serialize_user(
        user, auth_user=auth_user, options=["name", "avatarUrl"]
    )


def get_user_count() -> int:
    return db.session.query(model.User).count()


def try_get_user_by_name(name: str) -> Optional[model.User]:
    return (
        db.session.query(model.User)
        .filter(sa.func.lower(model.User.name) == sa.func.lower(name))
        .one_or_none()
    )


def get_user_by_name(name: str) -> model.User:
    user = try_get_user_by_name(name)
    if not user:
        raise UserNotFoundError("User %r not found." % name)
    return user


def try_get_user_by_name_or_email(name_or_email: str) -> Optional[model.User]:
    return (
        db.session.query(model.User)
        .filter(
            (sa.func.lower(model.User.name) == sa.func.lower(name_or_email))
            | (sa.func.lower(model.User.email) == sa.func.lower(name_or_email))
        )
        .one_or_none()
    )


def get_user_by_name_or_email(name_or_email: str) -> model.User:
    user = try_get_user_by_name_or_email(name_or_email)
    if not user:
        raise UserNotFoundError("User %r not found." % name_or_email)
    return user


def create_user(name: str, password: str, email: str) -> model.User:
    user = model.User()
    update_user_name(user, name)
    update_user_password(user, password)
    update_user_email(user, email)
    if get_user_count() > 0:
        user.rank = util.flip(auth.RANK_MAP)[config.config["default_rank"]]
    else:
        user.rank = model.User.RANK_ADMINISTRATOR
    user.creation_time = datetime.utcnow()
    user.avatar_style = model.User.AVATAR_GRAVATAR
    return user


def update_user_name(user: model.User, name: str) -> None:
    assert user
    if not name:
        raise InvalidUserNameError("Name cannot be empty.")
    if util.value_exceeds_column_size(name, model.User.name):
        raise InvalidUserNameError("User name is too long.")
    name = name.strip()
    name_regex = config.config["user_name_regex"]
    if not re.match(name_regex, name):
        raise InvalidUserNameError(
            "User name %r must satisfy regex %r." % (name, name_regex)
        )
    other_user = try_get_user_by_name(name)
    if other_user and other_user.user_id != user.user_id:
        raise UserAlreadyExistsError("User %r already exists." % name)
    if user.name and files.has(get_avatar_path(user.name)):
        files.move(get_avatar_path(user.name), get_avatar_path(name))
    user.name = name


def update_user_password(user: model.User, password: str) -> None:
    assert user
    if not password:
        raise InvalidPasswordError("Password cannot be empty.")
    password_regex = config.config["password_regex"]
    if not re.match(password_regex, password):
        raise InvalidPasswordError(
            "Password must satisfy regex %r." % password_regex
        )
    user.password_salt = auth.create_password()
    password_hash, revision = auth.get_password_hash(
        user.password_salt, password
    )
    user.password_hash = password_hash
    user.password_revision = revision


def update_user_email(user: model.User, email: str) -> None:
    assert user
    email = email.strip()
    if util.value_exceeds_column_size(email, model.User.email):
        raise InvalidEmailError("Email is too long.")
    if not util.is_valid_email(email):
        raise InvalidEmailError("E-mail is invalid.")
    user.email = email or None


def update_user_rank(
    user: model.User, rank: str, auth_user: model.User
) -> None:
    assert user
    if not rank:
        raise InvalidRankError("Rank cannot be empty.")
    rank = util.flip(auth.RANK_MAP).get(rank.strip(), None)
    all_ranks = list(auth.RANK_MAP.values())
    if not rank:
        raise InvalidRankError("Rank can be either of %r." % all_ranks)
    if rank in (model.User.RANK_ANONYMOUS, model.User.RANK_NOBODY):
        raise InvalidRankError("Rank %r cannot be used." % auth.RANK_MAP[rank])
    if (
        all_ranks.index(auth_user.rank) < all_ranks.index(rank)
        and get_user_count() > 0
    ):
        raise errors.AuthError("Trying to set higher rank than your own.")
    user.rank = rank


def get_blocklist_from_user(user: model.User) -> List[model.UserTagBlocklist]:
    """
    Return the UserTagBlocklist objects related to given user
    """
    rez = (db.session.query(model.UserTagBlocklist)
        .filter(
            model.UserTagBlocklist.user_id == user.user_id
        )
        .all())
    return rez


def get_blocklist_tag_from_user(user: model.User) -> List[model.UserTagBlocklist]:
    """
    Return the Tags blocklisted by given user
    """
    rez = (db.session.query(model.UserTagBlocklist.tag_id)
        .filter(
            model.UserTagBlocklist.user_id == user.user_id
        ))
    rez2 = (db.session.query(model.Tag)
        .filter(
            model.Tag.tag_id.in_(rez)
        ).all())
    return rez2


def update_user_blocklist(user: model.User, new_blocklist_tags: Optional[List[model.Tag]]) -> List[List[model.UserTagBlocklist]]:
    """
    Modify blocklist for given user.
    If new_blocklist_tags is None, set the blocklist to configured default tag blocklist.
    """
    assert user
    to_add: List[model.UserTagBlocklist] = []
    to_remove: List[model.UserTagBlocklist] = []

    if new_blocklist_tags is None:  # We're creating the user, use default config blocklist
        if 'default_tag_blocklist' in config.config.keys():
            for e in tags.get_tags_by_exact_names(config.config['default_tag_blocklist'].split(' ')):
                to_add.append(model.UserTagBlocklist(user_id=user.user_id, tag_id=e.tag_id))
    else:
        new_blocklist_ids: List[int] = [e.tag_id for e in new_blocklist_tags]
        previous_blocklist_tags: List[model.Tag] = get_blocklist_from_user(user)
        previous_blocklist_ids: List[int] = [e.tag_id for e in previous_blocklist_tags]
        original_previous_blocklist_ids = copy.copy(previous_blocklist_ids)

        ## Remove tags no longer in the new list
        for i in range(len(original_previous_blocklist_ids)):
            old_tag_id = original_previous_blocklist_ids[i]
            if old_tag_id not in new_blocklist_ids:
                to_remove.append(previous_blocklist_tags[i])
                previous_blocklist_ids.remove(old_tag_id)

        ## Add tags not yet in the original list
        for new_tag_id in new_blocklist_ids:
            if new_tag_id not in previous_blocklist_ids:
                to_add.append(model.UserTagBlocklist(user_id=user.user_id, tag_id=new_tag_id))
    return to_add, to_remove


def update_user_avatar(
    user: model.User, avatar_style: str, avatar_content: Optional[bytes] = None
) -> None:
    assert user
    if avatar_style == "gravatar":
        user.avatar_style = user.AVATAR_GRAVATAR
    elif avatar_style == "manual":
        user.avatar_style = user.AVATAR_MANUAL
        avatar_path = "avatars/" + user.name.lower() + ".png"
        if not avatar_content:
            if files.has(avatar_path):
                return
            raise InvalidAvatarError("Avatar content missing.")
        image = images.Image(avatar_content)
        image.resize_fill(
            int(config.config["thumbnails"]["avatar_width"]),
            int(config.config["thumbnails"]["avatar_height"]),
        )
        files.save(avatar_path, image.to_png())
    else:
        raise InvalidAvatarError(
            "Avatar style %r is invalid. Valid avatar styles: %r."
            % (avatar_style, ["gravatar", "manual"])
        )


def bump_user_login_time(user: model.User) -> None:
    assert user
    user.last_login_time = datetime.utcnow()


def reset_user_password(user: model.User) -> str:
    assert user
    password = auth.create_password()
    user.password_salt = auth.create_password()
    password_hash, revision = auth.get_password_hash(
        user.password_salt, password
    )
    user.password_hash = password_hash
    user.password_revision = revision
    return password
