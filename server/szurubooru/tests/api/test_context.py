import pytest
from szurubooru import api, errors

def test_has_param():
    ctx = api.Context()
    ctx.input = {'key': 'value'}
    assert ctx.has_param('key')
    assert not ctx.has_param('key2')

def test_get_file():
    ctx = api.Context()
    ctx.files = {'key': b'content'}
    assert ctx.get_file('key') == b'content'
    assert ctx.get_file('key2') is None

def test_getting_list_parameter():
    ctx = api.Context()
    ctx.input = {'key': 'value', 'list': ['1', '2', '3']}
    assert ctx.get_param_as_list('key') == ['value']
    assert ctx.get_param_as_list('key2') is None
    assert ctx.get_param_as_list('key2', default=['def']) == ['def']
    assert ctx.get_param_as_list('list') == ['1', '2', '3']
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_list('key2', required=True)

def test_getting_string_parameter():
    ctx = api.Context()
    ctx.input = {'key': 'value', 'list': ['1', '2', '3']}
    assert ctx.get_param_as_string('key') == 'value'
    assert ctx.get_param_as_string('key2') is None
    assert ctx.get_param_as_string('key2', default='def') == 'def'
    assert ctx.get_param_as_string('list') == '1,2,3' # falcon issue #749
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_string('key2', required=True)

def test_getting_int_parameter():
    ctx = api.Context()
    ctx.input = {'key': '50', 'err': 'invalid', 'list': [1, 2, 3]}
    assert ctx.get_param_as_int('key') == 50
    assert ctx.get_param_as_int('key2') is None
    assert ctx.get_param_as_int('key2', default=5) == 5
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_int('list')
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_int('key2', required=True)
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_int('err')
    with pytest.raises(errors.ValidationError):
        assert ctx.get_param_as_int('key', min=50) == 50
        ctx.get_param_as_int('key', min=51)
    with pytest.raises(errors.ValidationError):
        assert ctx.get_param_as_int('key', max=50) == 50
        ctx.get_param_as_int('key', max=49)
