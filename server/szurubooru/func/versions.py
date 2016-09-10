from szurubooru import errors


def verify_version(entity, context, field_name='version'):
    actual_version = context.get_param_as_int(field_name, required=True)
    expected_version = entity.version
    if actual_version != expected_version:
        raise errors.IntegrityError(
            'Someone else modified this in the meantime. ' +
            'Please try again.')


def bump_version(entity):
    entity.version = entity.version + 1
