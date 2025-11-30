# Guía de Configuración: Integración de Email con Gmail y Outlook

Esta guía te ayudará a configurar la integración de emails de Vera con Gmail y Outlook mediante OAuth2.

## Tabla de Contenidos

1. [Configuración de Google (Gmail)](#1-configuración-de-google-gmail)
2. [Configuración de Microsoft (Outlook)](#2-configuración-de-microsoft-outlook)
3. [Configuración del Archivo .env](#3-configuración-del-archivo-env)
4. [Configuración de Laravel Queue](#4-configuración-de-laravel-queue)
5. [Ejecutar Migraciones](#5-ejecutar-migraciones)
6. [Verificar la Instalación](#6-verificar-la-instalación)
7. [Solución de Problemas](#7-solución-de-problemas)

---

## 1. Configuración de Google (Gmail)

### Paso 1.1: Crear un Proyecto en Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Dale un nombre a tu proyecto (ej: "Vera Email Integration")

### Paso 1.2: Habilitar la Gmail API

1. En el menú lateral, ve a **APIs & Services** > **Library**
2. Busca "Gmail API"
3. Haz clic en "Gmail API" y luego en **Enable**

### Paso 1.3: Configurar la Pantalla de Consentimiento OAuth

1. Ve a **APIs & Services** > **OAuth consent screen**
2. Selecciona **External** (o Internal si es para uso interno de tu organización)
3. Completa la información requerida:
   - **App name**: Vera
   - **User support email**: tu email
   - **Developer contact information**: tu email
4. En **Scopes**, añade los siguientes scopes:
   ```
   https://www.googleapis.com/auth/gmail.readonly
   https://www.googleapis.com/auth/gmail.send
   https://www.googleapis.com/auth/gmail.modify
   https://www.googleapis.com/auth/gmail.labels
   ```
5. Guarda y continúa

### Paso 1.4: Crear Credenciales OAuth 2.0

1. Ve a **APIs & Services** > **Credentials**
2. Haz clic en **Create Credentials** > **OAuth client ID**
3. Selecciona **Web application**
4. Configura:
   - **Name**: Vera Web Client
   - **Authorized JavaScript origins**: `http://localhost:8000` (o tu dominio)
   - **Authorized redirect URIs**:
     ```
     http://localhost:8000/email-accounts/google/callback
     ```
     (O reemplaza con tu URL de producción cuando despliegues)
5. Haz clic en **Create**
6. **IMPORTANTE**: Copia el **Client ID** y **Client Secret** - los necesitarás para el archivo .env

---

## 2. Configuración de Microsoft (Outlook)

### Paso 2.1: Registrar una Aplicación en Azure

1. Ve a [Azure Portal](https://portal.azure.com/)
2. Ve a **Azure Active Directory** > **App registrations**
3. Haz clic en **New registration**
4. Configura:
   - **Name**: Vera Email Integration
   - **Supported account types**: Accounts in any organizational directory and personal Microsoft accounts
   - **Redirect URI**:
     - Tipo: Web
     - URI: `http://localhost:8000/email-accounts/microsoft/callback`
5. Haz clic en **Register**

### Paso 2.2: Configurar Permisos (API Permissions)

1. En tu aplicación registrada, ve a **API permissions**
2. Haz clic en **Add a permission** > **Microsoft Graph** > **Delegated permissions**
3. Añade los siguientes permisos:
   ```
   Mail.Read
   Mail.Send
   Mail.ReadWrite
   offline_access
   User.Read
   ```
4. Haz clic en **Add permissions**
5. **OPCIONAL**: Si eres admin, puedes dar consentimiento para toda la organización

### Paso 2.3: Crear un Client Secret

1. Ve a **Certificates & secrets**
2. Haz clic en **New client secret**
3. Dale una descripción (ej: "Vera Integration Secret")
4. Selecciona una expiración (recomendado: 24 meses)
5. Haz clic en **Add**
6. **IMPORTANTE**: Copia el **Value** del secret inmediatamente - no podrás verlo de nuevo

### Paso 2.4: Obtener el Application (client) ID

1. Ve a **Overview** en tu aplicación
2. Copia el **Application (client) ID** - lo necesitarás para el .env

---

## 3. Configuración del Archivo .env

Abre tu archivo `.env` en la raíz del proyecto y añade/actualiza las siguientes variables:

```env
# Google OAuth (Gmail)
GOOGLE_CLIENT_ID=tu-client-id-de-google.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret-de-google

# Microsoft OAuth (Outlook)
MICROSOFT_CLIENT_ID=tu-application-client-id-de-azure
MICROSOFT_CLIENT_SECRET=tu-client-secret-de-azure
MICROSOFT_TENANT=common

# URL de tu aplicación
APP_URL=http://localhost:8000
```

**Notas**:
- `MICROSOFT_TENANT` puede ser:
  - `common` - Para cuentas personales de Microsoft y organizaciones
  - `organizations` - Solo cuentas de organizaciones
  - `consumers` - Solo cuentas personales de Microsoft
  - Un ID específico de tenant para una organización específica

---

## 4. Configuración de Laravel Queue

La sincronización de emails se ejecuta en background usando Laravel Queue. Necesitas configurar esto:

### Opción A: Queue Driver = Database (Recomendado para desarrollo)

1. En tu `.env`, asegúrate de tener:
   ```env
   QUEUE_CONNECTION=database
   ```

2. Crea la tabla de jobs:
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

3. Inicia el worker de la queue:
   ```bash
   php artisan queue:work
   ```

### Opción B: Queue Driver = Redis (Recomendado para producción)

1. Instala Redis en tu sistema
2. En tu `.env`:
   ```env
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

3. Inicia el worker:
   ```bash
   php artisan queue:work redis
   ```

### Configurar el Scheduler (Opcional pero Recomendado)

Para sincronización automática cada 5 minutos, añade esto a tu crontab:

```bash
* * * * * cd /ruta/a/tu/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

O en desarrollo, ejecuta manualmente:
```bash
php artisan schedule:work
```

---

## 5. Ejecutar Migraciones

Si aún no has ejecutado las migraciones de la base de datos:

```bash
php artisan migrate
```

Esto creará las siguientes tablas:
- `email_accounts` - Almacena las cuentas conectadas y tokens OAuth
- `sync_logs` - Registro de todas las sincronizaciones
- Actualiza `emails` con la columna `email_account_id`

---

## 6. Verificar la Instalación

### Paso 6.1: Verificar las Dependencias

Asegúrate de que los paquetes estén instalados:

```bash
composer show | grep google/apiclient
composer show | grep microsoft/microsoft-graph
composer show | grep laravel/socialite
```

### Paso 6.2: Verificar las Rutas

```bash
php artisan route:list --name=email-accounts
```

Deberías ver rutas como:
- `email-accounts.google.redirect`
- `email-accounts.google.callback`
- `email-accounts.microsoft.redirect`
- `email-accounts.microsoft.callback`

### Paso 6.3: Probar la Conexión

1. Inicia el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

2. Inicia el queue worker en otra terminal:
   ```bash
   php artisan queue:work
   ```

3. Ve a: `http://localhost:8000/email-accounts`

4. Haz clic en **Conectar Gmail** o **Conectar Outlook**

5. Completa el flujo de autenticación OAuth

6. Verifica que:
   - La cuenta aparezca en la lista
   - El estado sea "Activa"
   - La sincronización se inicie automáticamente

---

## 7. Solución de Problemas

### Error: "redirect_uri_mismatch"

**Causa**: La URI de redirección no coincide con la configurada en Google/Microsoft.

**Solución**:
1. Verifica que `APP_URL` en `.env` sea correcto
2. Verifica que la redirect URI en Google Cloud Console / Azure Portal sea exactamente:
   - Google: `{APP_URL}/email-accounts/google/callback`
   - Microsoft: `{APP_URL}/email-accounts/microsoft/callback`

### Error: "Invalid client secret"

**Causa**: El client secret es incorrecto o ha expirado.

**Solución**:
1. Regenera el secret en Google Cloud Console / Azure Portal
2. Actualiza el valor en `.env`
3. Ejecuta `php artisan config:clear`

### La sincronización no se ejecuta

**Causa**: El queue worker no está corriendo.

**Solución**:
1. Ejecuta `php artisan queue:work`
2. O verifica que el scheduler esté configurado con cron

### No se están descargando emails

**Posibles causas**:

1. **Tokens expirados**: Ve a `/email-accounts` y verifica el estado de la cuenta
2. **Permisos insuficientes**: Verifica que todos los scopes estén configurados
3. **Queue no procesando**: Revisa los logs en `storage/logs/laravel.log`

**Solución**:
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Limpiar jobs fallidos
php artisan queue:flush

# Forzar sincronización manual
php artisan tinker
>>> $account = App\Models\EmailAccount::first();
>>> App\Jobs\SyncEmailAccount::dispatch($account, 'full');
```

### Error de SSL Certificate

**Causa**: Firewall o antivirus bloqueando la conexión.

**Solución**:
1. Desactiva temporalmente el firewall/antivirus
2. O configura certificados SSL correctamente

---

## Comandos Útiles

```bash
# Ver cuentas conectadas
php artisan tinker
>>> App\Models\EmailAccount::all();

# Ver logs de sincronización
>>> App\Models\SyncLog::latest()->take(5)->get();

# Forzar sincronización completa
>>> $account = App\Models\EmailAccount::find(1);
>>> App\Jobs\SyncEmailAccount::dispatch($account, 'full');

# Ver emails sincronizados
>>> App\Models\Email::count();
>>> App\Models\Email::latest()->take(5)->get(['subject', 'from_email', 'received_at']);

# Limpiar cache de configuración
php artisan config:clear
php artisan cache:clear
```

---

## Arquitectura de la Sincronización

### Flujo de Sincronización

1. **OAuth2 Flow**: Usuario conecta su cuenta
2. **Primera Sincronización**: Job `SyncEmailAccount` se dispara automáticamente (tipo: `full`)
3. **Sincronización Incremental**: Cada 5 minutos, se sincronizan solo emails nuevos (tipo: `incremental`)
4. **Token Refresh**: Cada hora, se verifican y refrescan tokens próximos a expirar

### Archivos Clave

- **Controllers**: `app/Http/Controllers/EmailAccountController.php`
- **Services**:
  - `app/Services/GmailSyncService.php`
  - `app/Services/OutlookSyncService.php`
- **Jobs**:
  - `app/Jobs/SyncEmailAccount.php`
  - `app/Jobs/RefreshEmailAccountToken.php`
- **Models**:
  - `app/Models/EmailAccount.php`
  - `app/Models/SyncLog.php`
  - `app/Models/Email.php`
- **Scheduler**: `app/Console/Kernel.php`

---

## Seguridad

- Los tokens OAuth se almacenan **encriptados** en la base de datos usando Laravel Crypt
- Los refresh tokens permiten renovar el acceso sin reautenticación
- Se implementa manejo de errores para evitar fugas de información sensible
- Los logs registran actividad pero NO almacenan contenido de emails sensibles

---

## Próximos Pasos

Una vez configurado, podrás:

1. Ver todos tus emails sincronizados en `/emails`
2. Gestionar múltiples cuentas desde `/email-accounts`
3. Realizar acciones bidireccionales (marcar como leído, archivar, etc.)
4. Implementar automatizaciones con IA en el futuro

---

## Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Revisa la tabla `sync_logs` para ver errores de sincronización
3. Verifica que todas las credenciales en `.env` sean correctas
4. Asegúrate de que el queue worker esté corriendo

---

**Última actualización**: Noviembre 2025
**Versión de Laravel**: 12.x
**Autor**: Equipo Vera
