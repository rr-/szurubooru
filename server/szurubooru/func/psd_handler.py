from PIL import Image
from io import BytesIO

def handle_psd_file(path: str) -> None:
    with open(path, "rb") as f:
        psd_content = f.read()
    png_content = convert_psd_to_png(psd_content)
    png_path = path.replace(".psd", ".png")
    with open(png_path, "wb") as f:
        f.write(png_content)

def convert_psd_to_png(content: bytes) -> bytes:
    img = Image.open(BytesIO(content))
    img_byte_arr = BytesIO()
    img.save(img_byte_arr, format="PNG")
    return img_byte_arr.getvalue()
