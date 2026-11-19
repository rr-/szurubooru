import pytest
import copy
from unittest.mock import patch, MagicMock
import urllib.error
from szurubooru import db, model, config
from szurubooru.func import posts, tags, tagger, tag_categories

def test_is_tagger_enabled(config_injector):
    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {"enabled": True}
    config_injector(custom_config)
    assert tagger.is_tagger_enabled() is True

    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {"enabled": False}
    config_injector(custom_config)
    assert tagger.is_tagger_enabled() is False

    custom_config = copy.deepcopy(config.config)
    if "tagger" in custom_config:
        del custom_config["tagger"]
    config_injector(custom_config)
    assert tagger.is_tagger_enabled() is False

@patch("urllib.request.urlopen")
def test_get_tags_from_tagger_disabled(mock_urlopen, config_injector):
    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {"enabled": False}
    config_injector(custom_config)
    res = tagger.get_tags_from_tagger(b"image_content")
    assert res is None
    mock_urlopen.assert_not_called()

@patch("urllib.request.urlopen")
def test_get_tags_from_tagger_success(mock_urlopen, config_injector):
    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {
        "enabled": True,
        "url": "http://tagger:8000",
        "classify_safety": True,
        "safety_mappings": {
            "general": "safe",
            "sensitive": "sketchy",
            "questionable": "sketchy",
            "explicit": "unsafe"
        }
    }
    config_injector(custom_config)
    
    mock_res = MagicMock()
    mock_res.read.return_value = b'{"tags": [{"name": "1girl", "category": "general"}, {"name": "miku", "category": "character"}], "ratings": {"general": 0.1, "sensitive": 0.8, "questionable": 0.05, "explicit": 0.05}}'
    mock_urlopen.return_value.__enter__.return_value = mock_res

    res = tagger.get_tags_from_tagger(b"image_content")
    assert res == ([("1girl", "general"), ("miku", "character")], "sketchy")

@patch("urllib.request.urlopen")
def test_get_tags_from_tagger_error(mock_urlopen, config_injector):
    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {
        "enabled": True,
        "url": "http://tagger:8000"
    }
    custom_config["allow_broken_uploads"] = False
    config_injector(custom_config)
    
    mock_urlopen.side_effect = urllib.error.URLError("Tagger offline")
    with pytest.raises(urllib.error.URLError):
        tagger.get_tags_from_tagger(b"image_content")

    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {
        "enabled": True,
        "url": "http://tagger:8000"
    }
    custom_config["allow_broken_uploads"] = True
    config_injector(custom_config)
    res = tagger.get_tags_from_tagger(b"image_content")
    assert res is None

@patch("szurubooru.func.posts.generate_post_thumbnail")
@patch("szurubooru.func.files.save")
@patch("szurubooru.func.tagger.is_tagger_enabled")
@patch("szurubooru.func.tagger.get_tags_from_tagger")
def test_create_post_with_tagger(mock_get_tags, mock_enabled, mock_save, mock_gen_thumb, config_injector, user_factory, tag_category_factory):
    # Ensure default category exists
    default_cat = tag_category_factory(name="default", default=True)
    db.session.add(default_cat)
    db.session.flush()

    mock_enabled.return_value = True
    mock_get_tags.return_value = (
        [("1girl", "general"), ("hatsune_miku", "character")],
        "sketchy"
    )
    
    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {
        "enabled": True,
        "url": "http://tagger:8000",
        "category_mappings": {
            "general": "general",
            "character": "character"
        }
    }
    custom_config["allow_broken_uploads"] = False
    config_injector(custom_config)

    content = b"GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;"
    
    user = user_factory()
    
    post, new_tags = posts.create_post(content, ["manual_tag"], user)
    
    assert hasattr(post, "_auto_safety")
    assert post._auto_safety == "sketchy"
    posts.update_post_safety(post, "safe")
    assert post.safety == model.Post.SAFETY_SKETCHY
    
    tag_names = [t.first_name for t in post.tags]
    assert "manual_tag" in tag_names
    assert "1girl" in tag_names
    assert "hatsune_miku" in tag_names
    
    t_1girl = next(t for t in post.tags if t.first_name == "1girl")
    t_miku = next(t for t in post.tags if t.first_name == "hatsune_miku")
    
    assert t_1girl.category.name == "general"
    assert t_miku.category.name == "character"


@patch("szurubooru.func.files.get")
@patch("szurubooru.func.tagger.get_tags_from_tagger")
def test_get_post_auto_tags_api(mock_get_tags, mock_files_get, config_injector, post_factory, context_factory, user_factory):
    from szurubooru import api
    
    post = post_factory(type=model.Post.TYPE_IMAGE)
    db.session.add(post)
    db.session.commit()
    
    mock_files_get.return_value = b"image content"
    mock_get_tags.return_value = (
        [("1girl", "general"), ("hatsune_miku", "character")],
        "sketchy"
    )
    
    custom_config = copy.deepcopy(config.config)
    custom_config["tagger"] = {
        "enabled": True,
        "url": "http://tagger:8000",
        "category_mappings": {
            "general": "general",
            "character": "character"
        }
    }
    custom_config["privileges"] = {
        "posts:edit:tags": model.User.RANK_REGULAR
    }
    config_injector(custom_config)
    
    ctx = context_factory(user=user_factory(rank=model.User.RANK_REGULAR))
    res = api.post_api.get_post_auto_tags(ctx, {"post_id": str(post.post_id)})
    
    assert res == {
        "tags": [
            {"name": "1girl", "category": "general"},
            {"name": "hatsune_miku", "category": "character"}
        ],
        "safety": "sketchy"
    }
    
    mock_files_get.assert_called_once()
    mock_get_tags.assert_called_once_with(b"image content")

