import json
import logging
import urllib.request
import urllib.error
from typing import Dict, List, Tuple, Optional
from szurubooru import config

logger = logging.getLogger(__name__)

def is_tagger_enabled() -> bool:
    tagger_conf = config.config.get("tagger", {})
    return bool(tagger_conf.get("enabled", False))

def get_tags_from_tagger(image_content: bytes) -> Optional[Tuple[List[Tuple[str, str]], Optional[str]]]:
    """
    Sends the image bytes to the tagger microservice.
    Returns:
        Tuple: (list of (tag_name, tag_category), predicted_safety_rating) or None on failure.
    """
    if not is_tagger_enabled():
        return None

    tagger_conf = config.config.get("tagger", {})
    tagger_url = tagger_conf.get("url", "http://tagger:8000")
    if not tagger_url:
        return None

    url = f"{tagger_url.rstrip('/')}/tag"
    req = urllib.request.Request(url, data=image_content)
    req.add_header("Content-Type", "application/octet-stream")

    try:
        with urllib.request.urlopen(req, timeout=30) as res:
            response_data = json.loads(res.read().decode("utf-8"))
    except Exception as ex:
        logger.error(f"Error communicating with tagger service at {url}: {ex}")
        if not config.config.get("allow_broken_uploads", False):
            raise
        return None

    tags_list = []
    for item in response_data.get("tags", []):
        tags_list.append((item["name"], item["category"]))

    ratings = response_data.get("ratings", {})
    predicted_safety = None
    if ratings and tagger_conf.get("classify_safety", True):
        # Find the highest scoring safety rating
        highest_rating = max(ratings, key=ratings.get)
        safety_mappings = tagger_conf.get("safety_mappings", {
            "general": "safe",
            "sensitive": "sketchy",
            "questionable": "sketchy",
            "explicit": "unsafe"
        })
        predicted_safety = safety_mappings.get(highest_rating, "safe")

    return tags_list, predicted_safety
