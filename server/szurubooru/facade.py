import logging
import os
import threading
import time
from typing import Any, Callable, Type

import coloredlogs
import sqlalchemy as sa
import sqlalchemy.orm.exc

from szurubooru import api, config, db, errors, middleware, rest
from szurubooru.func.file_uploads import purge_old_uploads
from szurubooru.func.posts import (
    update_all_md5_checksums,
    update_all_post_signatures,
)


def _map_error(
    ex: Exception, target_class: Type[rest.errors.BaseHttpError], title: str
) -> rest.errors.BaseHttpError:
    return target_class(
        name=type(ex).__name__,
        title=title,
        description=str(ex),
        extra_fields=getattr(ex, "extra_fields", {}),
    )


def _on_auth_error(ex: Exception) -> None:
    raise _map_error(ex, rest.errors.HttpForbidden, "Authentication error")


def _on_validation_error(ex: Exception) -> None:
    raise _map_error(ex, rest.errors.HttpBadRequest, "Validation error")


def _on_search_error(ex: Exception) -> None:
    raise _map_error(ex, rest.errors.HttpBadRequest, "Search error")


def _on_integrity_error(ex: Exception) -> None:
    raise _map_error(ex, rest.errors.HttpConflict, "Integrity violation")


def _on_not_found_error(ex: Exception) -> None:
    raise _map_error(ex, rest.errors.HttpNotFound, "Not found")


def _on_processing_error(ex: Exception) -> None:
    raise _map_error(ex, rest.errors.HttpBadRequest, "Processing error")


def _on_third_party_error(ex: Exception) -> None:
    raise _map_error(
        ex, rest.errors.HttpInternalServerError, "Server configuration error"
    )


def _on_stale_data_error(_ex: Exception) -> None:
    raise rest.errors.HttpConflict(
        name="IntegrityError",
        title="Integrity violation",
        description=(
            "Someone else modified this in the meantime. " "Please try again."
        ),
    )


def validate_config() -> None:
    """
    Check whether config doesn't contain errors that might prove
    lethal at runtime.
    """
    from szurubooru.func.auth import RANK_MAP

    for privilege, rank in config.config["privileges"].items():
        if rank not in RANK_MAP.values():
            raise errors.ConfigError(
                "Rank %r for privilege %r is missing" % (rank, privilege)
            )
    if config.config["default_rank"] not in RANK_MAP.values():
        raise errors.ConfigError(
            "Default rank %r is not on the list of known ranks"
            % (config.config["default_rank"])
        )

    for key in ["data_url", "data_dir"]:
        if not config.config[key]:
            raise errors.ConfigError(
                "Service is not configured: %r is missing" % key
            )

    if not os.path.isabs(config.config["data_dir"]):
        raise errors.ConfigError("data_dir must be an absolute path")

    if not config.config["database"]:
        raise errors.ConfigError("Database is not configured")

    if config.config["webhooks"] and not isinstance(
        config.config["webhooks"], list
    ):
        raise errors.ConfigError("Webhooks must be provided as a list of URLs")

    if config.config["smtp"]["host"]:
        if not config.config["smtp"]["port"]:
            raise errors.ConfigError("SMTP host is set but port is not set")
        if not config.config["smtp"]["user"]:
            raise errors.ConfigError(
                "SMTP host is set but username is not set"
            )
        if not config.config["smtp"]["pass"]:
            raise errors.ConfigError(
                "SMTP host is set but password is not set"
            )
        if not config.config["smtp"]["from"]:
            raise errors.ConfigError(
                "From address must be set to use mail-based password reset"
            )


def purge_old_uploads_daemon() -> None:
    while True:
        try:
            purge_old_uploads()
        except Exception as ex:
            logging.exception(ex)
        time.sleep(60 * 5)


_live_migrations = (
    update_all_post_signatures,
    update_all_md5_checksums,
)


def create_app() -> Callable[[Any, Any], Any]:
    """Create a WSGI compatible App object."""
    validate_config()
    coloredlogs.install(fmt="[%(asctime)-15s] %(name)s %(message)s")
    if config.config["debug"]:
        logging.getLogger("szurubooru").setLevel(logging.INFO)
    if config.config["show_sql"]:
        logging.getLogger("sqlalchemy.engine").setLevel(logging.INFO)

    threading.Thread(target=purge_old_uploads_daemon, daemon=True).start()

    for migration in _live_migrations:
        threading.Thread(target=migration, daemon=False).start()

    db.session.commit()

    rest.errors.handle(errors.AuthError, _on_auth_error)
    rest.errors.handle(errors.ValidationError, _on_validation_error)
    rest.errors.handle(errors.SearchError, _on_search_error)
    rest.errors.handle(errors.IntegrityError, _on_integrity_error)
    rest.errors.handle(errors.NotFoundError, _on_not_found_error)
    rest.errors.handle(errors.ProcessingError, _on_processing_error)
    rest.errors.handle(errors.ThirdPartyError, _on_third_party_error)
    rest.errors.handle(sa.orm.exc.StaleDataError, _on_stale_data_error)

    return rest.application


app = create_app()
