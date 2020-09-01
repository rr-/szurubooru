from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import auth, mailer


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "secret": "x",
            "domain": "http://example.com",
            "name": "Test instance",
            "smtp": {
                "from": "noreply@example.com",
            },
        }
    )


def test_reset_sending_email(context_factory, user_factory):
    db.session.add(
        user_factory(
            name="u1", rank=model.User.RANK_REGULAR, email="user@example.com"
        )
    )
    db.session.flush()
    for initiating_user in ["u1", "user@example.com"]:
        with patch("szurubooru.func.mailer.send_mail"):
            assert (
                api.password_reset_api.start_password_reset(
                    context_factory(), {"user_name": initiating_user}
                )
                == {}
            )
            mailer.send_mail.assert_called_once_with(
                "noreply@example.com",
                "user@example.com",
                "Password reset for Test instance",
                "You (or someone else) requested to reset your password "
                + "on Test instance.\nIf you wish to proceed, click this l"
                + "ink: http://example.com/password-reset/u1:4ac0be176fb36"
                + "4f13ee6b634c43220e2\nOtherwise, please ignore this email.",
            )


def test_trying_to_reset_non_existing(context_factory):
    with pytest.raises(errors.NotFoundError):
        api.password_reset_api.start_password_reset(
            context_factory(), {"user_name": "u1"}
        )


def test_trying_to_reset_without_email(context_factory, user_factory):
    db.session.add(
        user_factory(name="u1", rank=model.User.RANK_REGULAR, email=None)
    )
    db.session.flush()
    with pytest.raises(errors.ValidationError):
        api.password_reset_api.start_password_reset(
            context_factory(), {"user_name": "u1"}
        )


def test_confirming_with_good_token(context_factory, user_factory):
    user = user_factory(
        name="u1", rank=model.User.RANK_REGULAR, email="user@example.com"
    )
    old_hash = user.password_hash
    db.session.add(user)
    db.session.flush()
    context = context_factory(
        params={"token": "4ac0be176fb364f13ee6b634c43220e2"}
    )
    result = api.password_reset_api.finish_password_reset(
        context, {"user_name": "u1"}
    )
    assert user.password_hash != old_hash
    assert auth.is_valid_password(user, result["password"]) is True


def test_trying_to_confirm_non_existing(context_factory):
    with pytest.raises(errors.NotFoundError):
        api.password_reset_api.finish_password_reset(
            context_factory(), {"user_name": "u1"}
        )


def test_trying_to_confirm_without_token(context_factory, user_factory):
    db.session.add(
        user_factory(
            name="u1", rank=model.User.RANK_REGULAR, email="user@example.com"
        )
    )
    db.session.flush()
    with pytest.raises(errors.ValidationError):
        api.password_reset_api.finish_password_reset(
            context_factory(params={}), {"user_name": "u1"}
        )


def test_trying_to_confirm_with_bad_token(context_factory, user_factory):
    db.session.add(
        user_factory(
            name="u1", rank=model.User.RANK_REGULAR, email="user@example.com"
        )
    )
    db.session.flush()
    with pytest.raises(errors.ValidationError):
        api.password_reset_api.finish_password_reset(
            context_factory(params={"token": "bad"}), {"user_name": "u1"}
        )
