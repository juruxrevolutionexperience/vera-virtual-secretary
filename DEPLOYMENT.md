# 🚀 DEPLOYMENT GUIDE - VIRTUAL EMPLOYEE (VERA AI)

## Configuración de Gmail/SMTP para Envío de Emails

### 📧 Configurar Gmail en Producción o Testing

Para que los emails se envíen realmente (no a Mailpit), necesitas configurar Gmail SMTP.

#### **Paso 1: Crear App Password en Google**

1. Ve a: https://myaccount.google.com/apppasswords
2. Inicia sesión con tu cuenta de Gmail
3. **Si no tienes verificación en 2 pasos:**
   - Ve a: https://myaccount.google.com/security
   - Activa "Verificación en 2 pasos" (requerido para App Passwords)
4. **Crear App Password:**
   - Nombre: `Virtual Employee Production` (o el nombre que prefieras)
   - Clic en "Crear"
   - Google te mostrará una contraseña de 16 caracteres (ej: `abcd efgh ijkl mnop`)
   - **COPIA Y GUARDA** esta contraseña

#### **Paso 2: Configurar .env**

Edita tu archivo `.env` con estas variables:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD="abcd efgh ijkl mnop"  # Tu App Password (con o sin espacios)
MAIL_FROM_ADDRESS="tu-email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**⚠️ IMPORTANTE:**
- `MAIL_USERNAME` y `MAIL_FROM_ADDRESS` deben ser el mismo email que gestiona Vera
- La `MAIL_PASSWORD` DEBE ir entre comillas dobles si tiene espacios
- El email que gestiona Vera es el REMITENTE (quien envía)
- Los destinatarios se configuran en cada email programado

#### **Paso 3: Limpiar caché**

```bash
php artisan config:clear
php artisan cache:clear
```

#### **Paso 4: Testear la configuración**

```bash
php artisan tinker
```

```php
Mail::raw('Test email', function($message) {
    $message->to('destinatario@ejemplo.com')
            ->subject('Test desde Vera AI');
});
```

Si no da error, la configuración está correcta.

---

### 📝 Configuraciones para Diferentes Entornos

#### **Desarrollo Local (Mailpit)**

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
```

Mailpit captura todos los emails y los muestra en: http://localhost:8025

#### **Testing/Staging (Gmail)**

Usa la configuración de Gmail mostrada arriba.

#### **Producción (Gmail o SMTP Profesional)**

Para producción, considera usar:
- Gmail (hasta 500 emails/día)
- Gmail Workspace (hasta 2000 emails/día)
- SendGrid, Mailgun, SES (para volúmenes mayores)

---

## Configuración del Scheduler en Producción

### 1. Configurar Cron Job en el Servidor

Después de desplegar la aplicación en el servidor, debes configurar UN SOLO cron job que ejecute el scheduler de Laravel cada minuto.

#### Linux/Ubuntu (VPS, DigitalOcean, AWS EC2, etc.)

Edita el crontab del usuario que ejecuta la aplicación:

```bash
crontab -e
```

Agrega esta línea:

```bash
* * * * * cd /ruta/a/tu/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

**Reemplaza `/ruta/a/tu/proyecto`** con la ruta real de tu aplicación en el servidor.

**Ejemplo:**
```bash
* * * * * cd /var/www/virtualEmployee && php artisan schedule:run >> /dev/null 2>&1
```

#### ¿Por qué `schedule:run` y no el comando específico?

Porque `schedule:run`:
- Ejecuta TODAS las tareas programadas del `app/Console/Kernel.php`
- Si añades nuevas tareas en el futuro, funcionan automáticamente sin tocar el cron
- Es la forma oficial recomendada por Laravel
- Laravel internamente decide qué comandos ejecutar según su schedule

---

### 2. Desarrollo Local con Docker (Sail)

#### Opción A: Cron del sistema (WSL/Linux/Mac)

Edita el crontab local:

```bash
crontab -e
```

Agrega:

```bash
* * * * * ./vendor/bin/sail artisan emails:send-scheduled >> /tmp/cron-emails.log 2>&1
```

**Ver logs en tiempo real:**
```bash
tail -f /tmp/cron-emails.log
```

#### Opción B: Servicio de Scheduler en Docker (Recomendado para producción)

Si usas Docker en producción, crea un contenedor dedicado en `docker-compose.yml`:

```yaml
scheduler:
  build:
    context: .
    dockerfile: Dockerfile
  command: php artisan schedule:work
  volumes:
    - .:/var/www/html
  depends_on:
    - mysql
    - redis
  restart: unless-stopped
```

**Ventaja:** El comando `schedule:work` ejecuta el scheduler en un loop infinito sin necesidad de cron.

---

### 3. Verificar que el Cron Funciona

Después de configurar el cron, espera 2-3 minutos y verifica que se ejecute:

```bash
# Ver logs del cron del sistema
grep CRON /var/log/syslog | tail -20

# Ver logs de Laravel
tail -f storage/logs/laravel.log
```

**Verificar manualmente que el comando funciona:**

```bash
php artisan emails:send-scheduled
```

Si este comando funciona manualmente, funcionará con el cron.

---

### 4. Tareas Programadas Actuales

Estas son las tareas que se ejecutan automáticamente cuando usas `schedule:run`:

| Tarea | Frecuencia | Comando | Archivo | Descripción |
|-------|-----------|---------|---------|-------------|
| Sincronización incremental de Gmail | Cada 2 minutos | `incremental-sync-emails` | `app/Console/Kernel.php:85` | Obtiene nuevos emails de Gmail |
| Envío de emails programados | Cada minuto | `emails:send-scheduled` | `app/Console/Kernel.php:92` | Envía emails que llegaron a su hora programada |

**Ver todas las tareas programadas:**

```bash
php artisan schedule:list
```

**Ver detalles de un command específico:**

```bash
php artisan help emails:send-scheduled
```

---

### 5. Monitoreo de Emails Programados

#### Ver emails programados pendientes

```bash
php artisan tinker
```

```php
// Contar emails pendientes
\App\Models\ScheduledEmail::where('status', 'pending')->count();

// Ver emails programados para las próximas 24 horas
\App\Models\ScheduledEmail::where('status', 'pending')
    ->where('scheduled_at', '<=', now()->addDay())
    ->orderBy('scheduled_at')
    ->get(['id', 'to', 'subject', 'scheduled_at']);

// Ver últimos emails enviados
\App\Models\ScheduledEmail::where('status', 'sent')
    ->orderBy('sent_at', 'desc')
    ->take(5)
    ->get(['id', 'to', 'subject', 'sent_at']);
```

#### Logs del sistema

Los logs se guardan en `storage/logs/laravel.log`. Buscar por:

```bash
grep "ScheduledEmail" storage/logs/laravel.log | tail -20
```

---

### 6. Troubleshooting

#### ❌ El cron no se ejecuta

**Diagnóstico:**

1. **Verificar que el cron está configurado:**
   ```bash
   crontab -l
   ```

2. **Verificar permisos del proyecto:**
   ```bash
   ls -la /ruta/a/tu/proyecto
   ```

   El usuario del cron debe tener permisos de lectura/escritura.

3. **Verificar logs del sistema:**
   ```bash
   # Linux/Ubuntu
   grep CRON /var/log/syslog

   # CentOS/Red Hat
   grep CRON /var/log/cron
   ```

4. **Ejecutar el command manualmente:**
   ```bash
   cd /ruta/a/tu/proyecto
   php artisan schedule:run -v
   ```

   Esto mostrará qué comandos se ejecutaron.

#### ❌ Los emails programados no se envían

**Diagnóstico:**

1. **Verificar que el command funciona manualmente:**
   ```bash
   php artisan emails:send-scheduled
   ```

   Si da error, revisar:
   - ¿Hay conexión a la base de datos?
   - ¿Las credenciales de email son correctas?

2. **Verificar las credenciales de email en `.env`:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=tu-email@gmail.com
   MAIL_PASSWORD=tu-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=tu-email@gmail.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

3. **Verificar que hay emails programados pendientes:**
   ```bash
   php artisan tinker
   ```

   ```php
   \App\Models\ScheduledEmail::where('status', 'pending')
       ->where('scheduled_at', '<=', now())
       ->get();
   ```

   Si no hay resultados, no hay emails para enviar.

4. **Verificar timezone:**

   El timezone de Laravel debe coincidir con el del servidor:

   ```php
   // config/app.php
   'timezone' => 'America/Mexico_City', // o tu timezone
   ```

   Verificar timezone actual:
   ```bash
   php artisan tinker
   ```

   ```php
   echo config('app.timezone'); // Debe mostrar tu timezone
   echo now(); // Debe mostrar la hora correcta
   ```

#### ❌ El command se ejecuta pero no envía emails

1. **Verificar en la tabla scheduled_emails:**
   ```sql
   SELECT id, to, subject, scheduled_at, status, sent_at, error_message
   FROM scheduled_emails
   WHERE status = 'pending'
   ORDER BY scheduled_at DESC
   LIMIT 10;
   ```

2. **Verificar el campo `error_message`:**

   Si un email falló, el error estará en ese campo.

3. **Testear envío de email simple:**
   ```bash
   php artisan tinker
   ```

   ```php
   Mail::raw('Test email', function($message) {
       $message->to('tu@email.com')
               ->subject('Test desde Laravel');
   });
   ```

   Si este test falla, el problema es la configuración de email, no el scheduler.

---

### 7. Best Practices en Producción

#### ✅ Usar Queue para emails

En producción, envía emails a través de queues para no bloquear el request:

```php
// En lugar de:
Mail::send(...);

// Usa:
Mail::queue(...);
```

Luego corre workers:

```bash
php artisan queue:work --tries=3 --timeout=60
```

#### ✅ Supervisor para mantener workers vivos

Instala Supervisor para que los workers se reinicien automáticamente:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/tu/proyecto/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/a/tu/proyecto/storage/logs/worker.log
```

#### ✅ Monitoreo con Laravel Horizon (Opcional)

Para proyectos grandes, considera usar [Laravel Horizon](https://laravel.com/docs/horizon) para monitorear queues.

---

### 8. Diferencias Local vs Producción

| Aspecto | Local (Docker/Sail) | Producción (VPS/Cloud) |
|---------|---------------------|------------------------|
| **Cron** | Crontab del host ejecuta `sail artisan` | Crontab ejecuta `php artisan` |
| **Path** | `./vendor/bin/sail artisan comando` | `php artisan comando` |
| **Scheduler** | Manual: `sail artisan schedule:work` | Cron: `* * * * * php artisan schedule:run` |
| **Workers** | Manual: `sail artisan queue:work` | Supervisor: Mantiene workers vivos 24/7 |
| **Logs** | `/tmp/cron-emails.log` o `storage/logs/` | `storage/logs/` + syslog |
| **Permisos** | Usuario local | Usuario web (www-data, nginx, etc.) |

---

## ✅ Checklist Post-Deployment

- [ ] Cron job configurado en el servidor
- [ ] `php artisan schedule:list` muestra todas las tareas
- [ ] Sincronización de Gmail funciona cada 2 minutos
- [ ] Emails programados se envían automáticamente
- [ ] Logs no muestran errores
- [ ] Workers corriendo con Supervisor (si usas queues)
- [ ] Monitoreo configurado (opcional: Laravel Horizon/Telescope)
- [ ] Variables de entorno `.env` correctas
- [ ] Timezone configurado correctamente

---

## 📞 Soporte

Si encuentras problemas durante el deployment:

1. Revisa `storage/logs/laravel.log`
2. Ejecuta el command manualmente para ver errores
3. Verifica que el cron se está ejecutando en los logs del sistema
4. Asegúrate de que las credenciales de email funcionan

---

## 🤖 Vera AI - Configuración y Comportamiento

### Cómo Vera Respeta TODA tu Configuración

Vera AI verifica **TODAS** las configuraciones del usuario antes de generar una respuesta automática. Cada select, cada checkbox, cada ajuste en `/vera/config` afecta directamente cómo Vera actúa.

#### ✅ Configuraciones que Vera Verifica Automáticamente

**1. Control General**
- `auto_response_enabled` - Si está desactivado, Vera NO genera respuestas
- `auto_response_mode` - Controla cuándo responder:
  - `no` - Nunca responder automáticamente
  - `ask_first` - Solo emails con preguntas directas
  - `important_only` - Solo emails importantes
  - `suggest_responses` - Sugerir respuestas (requiere aprobación)
  - `always` - Siempre responder automáticamente

**2. Horarios de Disponibilidad** ⏰
- `availability_start` / `availability_end` - Vera solo genera respuestas dentro del horario configurado
- Ejemplo: Si configuras 09:00-18:00, Vera NO responderá fuera de ese horario
- Soporta horarios que cruzan medianoche (ej: 22:00-06:00)

**3. Auto-responders Activos** 🤖
- Si tienes un auto-responder activo (ej: "Fuera de Oficina"), Vera NO genera respuestas
- Evita respuestas duplicadas o contradictorias
- Vera verifica si el auto-responder respondería al email específico

**4. Emails Programados** 📅
- Si ya programaste un email para un remitente, Vera NO generará respuesta automática
- Previene respuestas conflictivas cuando ya tienes comunicación planificada

**5. Remitentes Bloqueados y Prioritarios**
- `blocked_senders` - Vera NUNCA responde a remitentes bloqueados
- `allowed_senders` - Vera SIEMPRE responde a remitentes prioritarios (ignora otros filtros)
- Soporta patrones: emails exactos, dominios (@empresa.com), substrings (noreply)

**6. Palabras Clave Bloqueadas** 🔤
- `blocked_keywords` - Vera NO responde si el email contiene palabras clave bloqueadas
- Busca en asunto y cuerpo del email

**7. Filtros de Categorías** 🏷️
- `response_allowed_categories` - Solo responder a estas categorías
- `response_blocked_categories` - Nunca responder a estas categorías
- Ejemplo: Bloquear "promociones" y "newsletters"

**8. Nivel de Autonomía** 🎯
- `autonomy_level` (conservative/moderate/aggressive)
- `confidence_threshold` - Solo envía respuestas con este nivel de confianza o superior

**9. Estilo de Escritura** ✍️
- `writing_style` (formal/professional/casual)
- Vera adapta el tono de las respuestas generadas

**10. Aprobación Manual**
- `auto_response_require_approval` - Si está activo, las respuestas necesitan aprobación en `/vera/responses`

#### 🔄 Orden de Verificación (Pipeline de Decisión)

Cuando llega un email, Vera verifica en este orden:

1. ❌ ¿Email archivado? → NO responder
2. ❌ ¿Auto-respuestas deshabilitadas? → NO responder
3. ⏰ ¿Fuera del horario disponible? → NO responder
4. 🤖 ¿Hay auto-responder activo? → NO responder
5. 📅 ¿Ya hay email programado para este remitente? → NO responder
6. ⭐ ¿Es remitente prioritario? → SÍ responder (saltar filtros)
7. 🚫 ¿Es remitente bloqueado? → NO responder
8. 🚫 ¿Es email automático/newsletter? → NO responder
9. 🚫 ¿Categoría bloqueada? → NO responder
10. 🔤 ¿Contiene palabras clave bloqueadas? → NO responder
11. ✅ ¿Es persona real? → Evaluar según modo de respuesta

#### 📍 Archivo de Implementación

Toda la lógica de verificación está en:
- `app/Services/VeraResponseGenerator.php` - Método `needsResponse()`
- Líneas 272-467: Filtros y verificaciones
- Líneas 689-777: Métodos helper para horarios, auto-responders y emails programados

---

**Última actualización:** 04/02/2026
**Versión:** 1.1
**Autor:** Virtual Employee Development Team
