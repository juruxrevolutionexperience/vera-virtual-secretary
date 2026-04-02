#!/bin/bash
# Levanta el ai-service de Vera.
# Ejecutar desde la carpeta ai-service/: bash start.sh

set -e
cd "$(dirname "$0")"

# Crear venv si no existe
if [ ! -d "venv" ]; then
    echo "Creando entorno virtual..."
    python3 -m venv venv
fi

# Instalar / actualizar dependencias
echo "Instalando dependencias..."
venv/bin/pip install --quiet --upgrade pip
venv/bin/pip install --quiet -r requirements.txt

# Verificar .env
if [ ! -f ".env" ]; then
    echo "ERROR: falta ai-service/.env con GROQ_API_KEY y GOOGLE_API_KEY"
    exit 1
fi

# Arrancar
echo "Arrancando Vera ai-service en puerto 8001..."
venv/bin/uvicorn main:app --host 0.0.0.0 --port 8001 --workers 2
