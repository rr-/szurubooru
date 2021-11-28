from datetime import datetime
from typing import Optional

from exif import Image


def resolve_date_taken(content: bytes) -> Optional[datetime]:
    img = Image(content)

    if img.has_exif and "datetime" in img.list_all():
        return datetime.strptime(img.datetime, "%Y:%m:%d %H:%M:%S")
    return None
