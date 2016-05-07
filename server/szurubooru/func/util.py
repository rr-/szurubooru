import datetime
import hashlib
import re
from sqlalchemy.inspection import inspect
from szurubooru.errors import ValidationError

def unalias_dict(input_dict):
    output_dict = {}
    for key_list, value in input_dict.items():
        if isinstance(key_list, str):
            key_list = [key_list]
        for key in key_list:
            output_dict[key] = value
    return output_dict

def get_md5(source):
    if not isinstance(source, bytes):
        source = source.encode('utf-8')
    md5 = hashlib.md5()
    md5.update(source)
    return md5.hexdigest()

def flip(source):
    return {v: k for k, v in source.items()}

def get_resource_info(entity):
    serializers = {
        'tag': lambda tag: tag.first_name,
        'tag_category': lambda category: category.name,
        'comment': lambda comment: comment.comment_id,
        'post': lambda post: post.post_id,
    }

    resource_type = entity.__table__.name
    assert resource_type in serializers

    primary_key = inspect(entity).identity
    assert primary_key is not None
    assert len(primary_key) == 1

    resource_repr = serializers[resource_type](entity)
    assert resource_repr

    resource_id = primary_key[0]
    assert resource_id

    return (resource_type, resource_id, resource_repr)

def is_valid_email(email):
    ''' Return whether given email address is valid or empty. '''
    return not email or re.match(r'^[^@]*@[^@]*\.[^@]*$', email)

class dotdict(dict): # pylint: disable=invalid-name
    ''' dot.notation access to dictionary attributes. '''
    def __getattr__(self, attr):
        return self.get(attr)
    __setattr__ = dict.__setitem__
    __delattr__ = dict.__delitem__

def parse_time_range(value, timezone=datetime.timezone(datetime.timedelta())):
    ''' Return tuple containing min/max time for given text representation. '''
    one_day = datetime.timedelta(days=1)
    one_second = datetime.timedelta(seconds=1)

    value = value.lower()
    if not value:
        raise ValidationError('Empty date format.')

    if value == 'today':
        now = datetime.datetime.now(tz=timezone)
        return (
            datetime.datetime(now.year, now.month, now.day, 0, 0, 0),
            datetime.datetime(now.year, now.month, now.day, 0, 0, 0) \
                + one_day - one_second)

    if value == 'yesterday':
        now = datetime.datetime.now(tz=timezone)
        return (
            datetime.datetime(now.year, now.month, now.day, 0, 0, 0) - one_day,
            datetime.datetime(now.year, now.month, now.day, 0, 0, 0) \
                - one_second)

    match = re.match(r'^(\d{4})$', value)
    if match:
        year = int(match.group(1))
        return (
            datetime.datetime(year, 1, 1),
            datetime.datetime(year + 1, 1, 1) - one_second)

    match = re.match(r'^(\d{4})-(\d{1,2})$', value)
    if match:
        year = int(match.group(1))
        month = int(match.group(2))
        return (
            datetime.datetime(year, month, 1),
            datetime.datetime(year, month + 1, 1) - one_second)

    match = re.match(r'^(\d{4})-(\d{1,2})-(\d{1,2})$', value)
    if match:
        year = int(match.group(1))
        month = int(match.group(2))
        day = int(match.group(3))
        return (
            datetime.datetime(year, month, day),
            datetime.datetime(year, month, day + 1) - one_second)

    raise ValidationError('Invalid date format: %r.' % value)

def icase_unique(source):
    target = []
    target_low = []
    for source_item in source:
        if source_item.lower() not in target_low:
            target.append(source_item)
            target_low.append(source_item.lower())
    return target

def value_exceeds_column_size(value, column):
    if not value:
        return False
    max_length = column.property.columns[0].type.length
    if max_length is None:
        return False
    return len(value) > max_length
