import os
import io
import csv
import yaml
import numpy as np
from PIL import Image
import pillow_avif
from pillow_heif import register_heif_opener
register_heif_opener()
import onnxruntime as ort
from huggingface_hub import hf_hub_download
from fastapi import FastAPI, Request, Response, HTTPException

app = FastAPI()

def load_config():
    config_paths = ["config.yaml", "/opt/app/config.yaml", "../server/config.yaml"]
    for path in config_paths:
        if os.path.exists(path):
            try:
                with open(path, "r") as f:
                    return yaml.safe_load(f) or {}
            except Exception as e:
                print(f"Error reading config at {path}: {e}")
    return {}

config = load_config()
tagger_config = config.get("tagger", {})
model_name = tagger_config.get("model", "SmilingWolf/wd-eva02-large-tagger-v3")

print(f"Loading SmilingWolf tagger model: {model_name}")

try:
    model_path = hf_hub_download(repo_id=model_name, filename="model.onnx")
    csv_path = hf_hub_download(repo_id=model_name, filename="selected_tags.csv")
except Exception as e:
    print(f"Failed to download/load model from Hugging Face: {e}")
    # If Hugging Face is offline, we'll catch it here and raise errors during request handling instead of failing startup
    model_path = None
    csv_path = None

# Group tag indexes by category
general_tags = []
character_tags = []
rating_tags = []

if csv_path:
    with open(csv_path, "r", encoding="utf-8") as f:
        reader = csv.reader(f)
        next(reader)  # skip header row
        for i, row in enumerate(reader):
            if not row:
                continue
            tag_name = row[1]
            category = int(row[2])
            if category == 0:
                general_tags.append((i, tag_name))
            elif category == 4:
                character_tags.append((i, tag_name))
            elif category == 9:
                rating_tags.append((i, tag_name))

# Set up ONNX runtime session
if model_path:
    session = ort.InferenceSession(model_path)
    input_name = session.get_inputs()[0].name
    input_shape = session.get_inputs()[0].shape

    # Dynamically find the model's expected resolution and layout (NCHW vs NHWC)
    if input_shape[1] == 3:
        target_size = input_shape[2]
        is_nchw = True
    elif input_shape[3] == 3:
        target_size = input_shape[1]
        is_nchw = False
    else:
        target_size = 448
        is_nchw = False
    print(f"Model input: {input_name}, shape: {input_shape}, target_size: {target_size}, NCHW: {is_nchw}")
else:
    session = None
    target_size = 448
    is_nchw = False

@app.get("/health")
async def health():
    if not session:
        return {"status": "error", "message": "Model not loaded"}
    return {"status": "ok", "model": model_name, "target_size": target_size, "is_nchw": is_nchw}

@app.post("/tag")
async def tag_image(request: Request, threshold: float = None):
    if not session:
        raise HTTPException(status_code=500, detail="Model session is not initialized")
    
    if threshold is None:
        threshold = tagger_config.get("threshold", 0.35)
        
    body = await request.body()
    if not body:
        raise HTTPException(status_code=400, detail="Empty request body")
        
    try:
        image = Image.open(io.BytesIO(body))
        
        # Convert transparency to white background
        if image.mode in ("RGBA", "LA") or "transparency" in image.info:
            image = image.convert("RGBA")
            background = Image.new("RGB", image.size, (255, 255, 255))
            background.paste(image, mask=image.split()[3])
            image = background
        else:
            image = image.convert("RGB")
            
        # Pad image to make it square
        width, height = image.size
        size = max(width, height)
        padded = Image.new("RGB", (size, size), (255, 255, 255))
        padded.paste(image, ((size - width) // 2, (size - height) // 2))
        
        # Resize to expected model size
        try:
            resample_filter = Image.Resampling.LANCZOS
        except AttributeError:
            resample_filter = Image.ANTIALIAS
        resized = padded.resize((target_size, target_size), resample_filter)
        
        # Convert to BGR array (needed for SmilingWolf models)
        img_array = np.array(resized, dtype=np.float32)
        img_array = img_array[:, :, ::-1]  # RGB to BGR
        
        if is_nchw:
            img_array = img_array.transpose((2, 0, 1))
            
        img_array = np.expand_dims(img_array, axis=0)
        
        # Run ONNX model prediction
        outputs = session.run(None, {input_name: img_array})
        scores = outputs[0][0]
        
        # Filter predictions above threshold
        result_tags = []
        for idx, tag_name in general_tags:
            prob = float(scores[idx])
            if prob >= threshold:
                result_tags.append({
                    "name": tag_name,
                    "category": "general",
                    "probability": prob
                })
                
        for idx, tag_name in character_tags:
            prob = float(scores[idx])
            if prob >= threshold:
                result_tags.append({
                    "name": tag_name,
                    "category": "character",
                    "probability": prob
                })
                
        # Get safety ratings
        ratings = {}
        for idx, tag_name in rating_tags:
            ratings[tag_name] = float(scores[idx])
            
        return {
            "tags": result_tags,
            "ratings": ratings
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
