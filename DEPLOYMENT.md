# 🚀 DEPLOYMENT GUIDE - VIRTUAL EMPLOYEE (VERA AI)

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

**Última actualización:** 04/02/2026
**Versión:** 1.0
**Autor:** Virtual Employee Development Team
