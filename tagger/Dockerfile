FROM python:3.10-slim

WORKDIR /opt/app

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8000

ENV HF_HOME=/data/huggingface_cache
ENV PYTHONUNBUFFERED=1

CMD ["uvicorn", "server:app", "--host", "0.0.0.0", "--port", "8000"]
