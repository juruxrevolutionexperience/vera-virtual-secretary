<p align="center">
  <img src="public/images/logo.png" width="80" alt="Jurux Logo">
</p>

# Vera — Secretaria Virtual con IA

Vera es una secretaria virtual impulsada por inteligencia artificial que gestiona la agenda de profesionales y empresas de forma autónoma. Lee emails entrantes, detecta solicitudes de reunión, consulta disponibilidad en Google Calendar y responde al cliente confirmando la cita — sin intervención humana.

---

## Stack tecnológico

### Backend (Laravel)
- **Laravel 12** — framework principal
- **PHP 8.3** — lenguaje
- **MySQL 8.0** — base de datos principal
- **Redis** — cola de jobs (queue)
- **Laravel Horizon** — gestión de workers (local)
- **Laravel Cashier + Stripe** — facturación y planes
- **Laravel Socialite** — OAuth con Google
- **Spatie Permission** — roles y permisos
- **Resend** — emails transaccionales

### AI Service (Python)
- **FastAPI + Uvicorn** — servidor del microservicio
- **Groq LLaMA 3.3 70B** — LLM principal (respuestas rápidas y económicas)
- **Google Gemini** — LLM de fallback
- **ChromaDB** — base de datos vectorial (memoria RAG de Vera)
- **Sentence Transformers** — embeddings

### Frontend
- **Blade + Alpine.js** — vistas y reactividad
- **Tailwind CSS** — estilos
- **Vite** — bundler

### Integraciones
- **Gmail API** — lectura y respuesta de emails
- **Google Calendar API** — consulta y creación de eventos
- **Stripe** — pagos y suscripciones
- **Resend** — emails transaccionales

### Infraestructura
- **Laravel Forge** — deploy y gestión del servidor
- **Hetzner** — servidor VPS
- **Cloudflare** — DNS y proxy
- **Docker + Laravel Sail** — entorno de desarrollo local

---

## Arquitectura

```
Cliente (email/WhatsApp)
        ↓
    Gmail API
        ↓
  Laravel (vera:process-emails)
        ↓
  ProcessIncomingMessage [Job → Redis Queue]
        ↓
    ai-service (FastAPI :8001)
    ├── Groq LLaMA 3.3 70B
    ├── ChromaDB (memoria RAG)
    └── Gemini (fallback)
        ↓
  Google Calendar (disponibilidad)
        ↓
  Gmail API (respuesta al cliente)
```

---

## Estructura del proyecto

```
vera-virtual-secretary/
├── app/
│   ├── Console/Commands/     # Comandos artisan (vera:process-emails, etc.)
│   ├── Http/Controllers/     # Controladores Laravel
│   ├── Jobs/                 # Jobs de cola (ProcessIncomingMessage)
│   ├── Models/               # Modelos Eloquent
│   └── Services/             # GmailService, GoogleCalendarService, etc.
├── ai-service/               # Microservicio Python (territorio de Antonio)
│   ├── llm/                  # Cliente Groq/Gemini
│   ├── modes/                # Detector de modo (agenda, escalation, etc.)
│   ├── rag/                  # ChromaDB embeddings y búsqueda
│   ├── main.py               # Entrypoint FastAPI
│   ├── requirements.txt      # Dependencias Python
│   └── start.sh              # Script de arranque
├── resources/
│   └── views/                # Vistas Blade
├── docker-compose.yml        # Entorno local completo
└── .env.example              # Variables de entorno de referencia
```

---

## Entorno local — primeros pasos

### Requisitos
- Docker Desktop
- PHP 8.3 + Composer
- Node.js 20+

### Instalación

```bash
# 1. Clonar el repositorio
git clone git@github.com:juruxrevolutionexperience/vera-virtual-secretary.git
cd vera-virtual-secretary

# 2. Copiar variables de entorno
cp .env.example .env

# 3. Rellenar las claves en .env:
#    - GROQ_API_KEY
#    - GOOGLE_API_KEY (Gemini)
#    - GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET
#    - AI_SERVICE_SECRET
#    - STRIPE_KEY / STRIPE_SECRET (opcional en local)

# 4. Instalar dependencias PHP
composer install

# 5. Instalar dependencias JS
npm install

# 6. Levantar todos los servicios
docker compose up -d

# 7. Migraciones y seeders
docker compose exec laravel.test php artisan migrate --seed

# 8. Generar clave de app
docker compose exec laravel.test php artisan key:generate
```

### Servicios disponibles en local

| Servicio | URL |
|---|---|
| App Laravel | http://localhost |
| AI Service | http://localhost:8001 |
| phpMyAdmin | http://localhost:8080 |
| Mailpit (emails) | http://localhost:8025 |

---

## Variables de entorno clave

```env
# App
APP_ENV=local
APP_URL=http://localhost

# Base de datos
DB_CONNECTION=mysql
DB_DATABASE=vera

# Queue
QUEUE_CONNECTION=database   # local: database / producción: redis

# AI Service
AI_SERVICE_URL=http://ai-service:8001   # local (Docker)
AI_SERVICE_SECRET=tu_secret_aqui

# LLMs
GROQ_API_KEY=
GOOGLE_API_KEY=    # para embeddings y Gemini

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=

# Resend (emails transaccionales)
RESEND_API_KEY=
```

---

## Git workflow

```
main               → producción (solo merges desde develop)
develop            → integración (trabajo diario de Jurux)
feature/ai-service → Antonio (microservicio Python)
```

### Jurux — trabajo diario

```bash
git checkout develop
git pull origin develop

# ... hacer cambios en Laravel ...

git add .
git commit -m "feat: descripción"
git push origin develop

# Para deployar a producción:
git checkout main
git merge develop
git push origin main
git checkout develop
```

### Antonio — trabajo en ai-service

```bash
git checkout feature/ai-service
git pull origin develop   # sincronizar con lo último de Jurux

# ... hacer cambios en ai-service/ ...

git add ai-service/
git commit -m "feat: descripción"
git push origin feature/ai-service

# Cuando termina → abrir PR de feature/ai-service → develop en GitHub
# Jurux revisa y hace merge
```

**Regla:** nadie pushea directo a `main`.

---

## Producción

- **URL:** https://app.jurux.app
- **Servidor:** Hetzner (gestionado con Laravel Forge)
- **Deploy:** automático al hacer push a `main`
- **AI Service:** daemon supervisor con venv persistente en `shared/ai-service-venv/`
- **ChromaDB:** persistente en `shared/chroma_db/`
- **Queue worker:** Redis, gestionado por supervisor

### Deploy a producción

```bash
git checkout main
git merge develop
git push origin main
git checkout develop
```

---

## Comandos útiles

```bash
# Procesar emails entrantes manualmente
php artisan vera:process-emails

# Ver qué emails encontraría sin procesarlos
php artisan vera:process-emails --dry-run

# Ver jobs fallidos
php artisan queue:failed

# Limpiar cola
php artisan queue:clear

# Salud del ai-service
curl http://127.0.0.1:8001/health
```

---

## Equipo

| Persona | Rol | Área |
|---|---|---|
| Jurux | Full-stack Lead | Laravel, frontend, infraestructura |
| Antonio | AI Engineer | ai-service (Python), ChromaDB, LLMs |
