FROM python:3.12-slim

WORKDIR /app

# Dependencias primero — así Docker cachea esta capa y no reinstala si solo cambia el código
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Código fuente (sin venv, sin chroma_db, sin .env — van fuera de la imagen)
COPY main.py .
COPY llm/ llm/
COPY rag/ rag/
COPY modes/ modes/

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2"]
