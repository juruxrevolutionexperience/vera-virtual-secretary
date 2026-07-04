#!/bin/bash
# Levanta el ai-service de Vera.
# Ejecutar desde la carpeta ai-service/: bash start.sh

set -e
cd "$(dirname "$0")"

# venv persistente fuera de releases para sobrevivir deploys
VENV_PATH="/home/forge/app.jurux.app/shared/ai-service-venv"

if [ ! -d "$VENV_PATH" ]; then
    echo "Creando entorno virtual persistente..."
    python3 -m venv "$VENV_PATH"
fi

# Instalar / actualizar dependencias
echo "Instalando dependencias..."
"$VENV_PATH/bin/pip" install --quiet --upgrade pip
"$VENV_PATH/bin/pip" install --quiet -r requirements.txt

# Verificar .env
if [ ! -f ".env" ]; then
    echo "ERROR: falta ai-service/.env con GROQ_API_KEY y GOOGLE_API_KEY"
    exit 1
fi

# Arrancar — bind SOLO a localhost: Laravel lo llama desde la misma máquina,
# nunca debe ser accesible desde internet (defensa en profundidad además del token)
echo "Arrancando Vera ai-service en puerto 8001..."
"$VENV_PATH/bin/uvicorn" main:app --host 127.0.0.1 --port 8001 --workers 2
