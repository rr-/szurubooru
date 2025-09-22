import unittest.mock

import pytest

from szurubooru import errors, rest
from szurubooru.func import net


def test_has_param():
    ctx = rest.Context(env={}, method=None, url=None, params={"key": "value"})
    assert ctx.has_param("key")
    assert not ctx.has_param("non-existing")


def test_get_file():
    ctx = rest.Context(
        env={}, method=None, url=None, files={"key": b"content"}
    )
    assert ctx.get_file("key") == b"content"
    with pytest.raises(errors.ValidationError):
        ctx.get_file("non-existing")


def test_get_file_from_url():
    with unittest.mock.patch("szurubooru.func.net.download"):
        net.download.return_value = b"content"
        ctx = rest.Context(
            env={}, method=None, url=None, params={"keyUrl": "example.com"}
        )
        assert ctx.get_file("key") == b"content"
        net.download.assert_called_once_with(
            "example.com", use_downloader=False
        )
        with pytest.raises(errors.ValidationError):
            assert ctx.get_file("non-existing")


def test_getting_list_parameter():
    ctx = rest.Context(
        env={},
        method=None,
        url=None,
        params={"key": "value", "list": ["1", "2", "3"]},
    )
    assert ctx.get_param_as_list("key") == ["value"]
    assert ctx.get_param_as_list("list") == ["1", "2", "3"]
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_list("non-existing")
    assert ctx.get_param_as_list("non-existing", default=["def"]) == ["def"]


def test_getting_string_parameter():
    ctx = rest.Context(
        env={},
        method=None,
        url=None,
        params={"key": "value", "list": ["1", "2", "3"]},
    )
    assert ctx.get_param_as_string("key") == "value"
    assert ctx.get_param_as_string("list") == "1,2,3"
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_string("non-existing")
    assert ctx.get_param_as_string("non-existing", default="x") == "x"


def test_getting_int_parameter():
    ctx = rest.Context(
        env={},
        method=None,
        url=None,
        params={"key": "50", "err": "invalid", "list": [1, 2, 3]},
    )
    assert ctx.get_param_as_int("key") == 50
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_int("list")
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_int("non-existing")
    assert ctx.get_param_as_int("non-existing", default=5) == 5
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_int("err")
    with pytest.raises(errors.ValidationError):
        assert ctx.get_param_as_int("key", min=50) == 50
        ctx.get_param_as_int("key", min=51)
    with pytest.raises(errors.ValidationError):
        assert ctx.get_param_as_int("key", max=50) == 50
        ctx.get_param_as_int("key", max=49)


def test_getting_bool_parameter():
    def test(value):
        ctx = rest.Context(
            env={}, method=None, url=None, params={"key": value}
        )
        return ctx.get_param_as_bool("key")

    assert test("1") is True
    assert test("y") is True
    assert test("yes") is True
    assert test("yep") is True
    assert test("yup") is True
    assert test("yeah") is True
    assert test("t") is True
    assert test("true") is True
    assert test("TRUE") is True

    assert test("0") is False
    assert test("n") is False
    assert test("no") is False
    assert test("nope") is False
    assert test("f") is False
    assert test("false") is False
    assert test("FALSE") is False

    with pytest.raises(errors.ValidationError):
        test("herp")
    with pytest.raises(errors.ValidationError):
        test("2")
    with pytest.raises(errors.ValidationError):
        test(["1", "2"])

    ctx = rest.Context(env={}, method=None, url=None)
    with pytest.raises(errors.ValidationError):
        ctx.get_param_as_bool("non-existing")
    assert ctx.get_param_as_bool("non-existing", default=True) is True
