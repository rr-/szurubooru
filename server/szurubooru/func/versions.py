from szurubooru import errors, model, rest


def verify_version(
    entity: model.Base, context: rest.Context, field_name: str = "version"
) -> None:
    actual_version = context.get_param_as_int(field_name)
    expected_version = entity.version
    if actual_version != expected_version:
        raise errors.IntegrityError(
            "Someone else modified this in the meantime. "
            + "Please try again."
        )


def bump_version(entity: model.Base) -> None:
    entity.version = entity.version + 1
