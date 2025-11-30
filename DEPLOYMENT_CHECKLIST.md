# 🚀 DEPLOYMENT CHECKLIST - JURUX (Producción)

**Versión:** 1.0
**Última actualización:** 15 de Noviembre 2025
**Proyecto:** JURUX - Vera (Secretaria Virtual)

---

## 📋 ÍNDICE

1. [Pre-Deployment](#pre-deployment)
2. [Configuración de Dominio](#configuración-de-dominio)
3. [Archivos a Actualizar](#archivos-a-actualizar)
4. [Variables de Entorno](#variables-de-entorno)
5. [Base de Datos](#base-de-datos)
6. [Servicios Externos](#servicios-externos)
7. [Performance y Seguridad](#performance-y-seguridad)
8. [Post-Deployment](#post-deployment)
9. [Monitoreo](#monitoreo)

---

## ✅ PRE-DEPLOYMENT

### Requisitos Previos

- [ ] **Dominio comprado** (recomendados: Namecheap, Porkbun, Google Domains)
- [ ] **Hosting seleccionado** (Laravel Forge, DigitalOcean, AWS, Vercel)
- [ ] **SSL/TLS certificado** (Let's Encrypt gratis con Forge/cPanel)
- [ ] **Cuentas de servicios creadas**:
  - [ ] Stripe (cuenta de producción)
  - [ ] Google Cloud Console (para APIs)
  - [ ] Email transaccional (SendGrid/Mailgun)
  - [ ] Monitoreo (Sentry para errors)

---

## 🌐 CONFIGURACIÓN DE DOMINIO

### DNS Records a Configurar

```
Tipo    Nombre    Valor                       TTL
A       @         [IP_DE_TU_SERVIDOR]        3600
A       www       [IP_DE_TU_SERVIDOR]        3600
CNAME   www       tudominio.com              3600
```

### SSL/HTTPS

- [ ] Certificado SSL instalado (Let's Encrypt gratis)
- [ ] Forzar HTTPS en todas las rutas
- [ ] Redirección www → no-www (o viceversa)
- [ ] HSTS headers configurados

---

## 📝 ARCHIVOS A ACTUALIZAR

### 1. `public/robots.txt`

**Línea 26** - Actualizar URL del sitemap:

```diff
- Sitemap: http://localhost:8000/sitemap.xml
+ Sitemap: https://tudominio.com/sitemap.xml
```

**Archivo completo:**
```txt
# robots.txt para JURUX - Vera
User-agent: *
Allow: /

# Páginas que NO queremos indexar
Disallow: /dashboard
Disallow: /profile
Disallow: /billing
Disallow: /virtual-secretary
Disallow: /admin
Disallow: /api

# Archivos técnicos
Disallow: /build/
Disallow: /vendor/
Disallow: /*.json$

# Sitemap
Sitemap: https://tudominio.com/sitemap.xml
```

---

### 2. `public/sitemap.xml`

**Actualizar TODAS las URLs:**

```diff
- <loc>http://localhost:8000/</loc>
+ <loc>https://tudominio.com/</loc>

- <loc>http://localhost:8000/privacy</loc>
+ <loc>https://tudominio.com/privacy</loc>

- <loc>http://localhost:8000/terms</loc>
+ <loc>https://tudominio.com/terms</loc>
```

**También actualizar `<lastmod>`:**
```xml
<lastmod>2025-11-15</lastmod> <!-- Fecha de deployment -->
```

---

### 3. `.env` (Archivo de Producción)

**Variables CRÍTICAS a cambiar:**

```bash
# GENERAL
APP_NAME="JURUX"
APP_ENV=production          # ⚠️ CAMBIAR de 'local' a 'production'
APP_DEBUG=false             # ⚠️ CAMBIAR a false
APP_URL=https://tudominio.com  # ⚠️ ACTUALIZAR

# BASE DE DATOS
DB_CONNECTION=mysql
DB_HOST=127.0.0.1           # o tu host de producción
DB_PORT=3306
DB_DATABASE=jurux_production  # ⚠️ ACTUALIZAR
DB_USERNAME=tu_usuario       # ⚠️ ACTUALIZAR
DB_PASSWORD=contraseña_segura # ⚠️ CAMBIAR

# STRIPE (PRODUCCIÓN)
STRIPE_KEY=pk_live_...       # ⚠️ Cambiar a claves LIVE
STRIPE_SECRET=sk_live_...    # ⚠️ Cambiar a claves LIVE
STRIPE_WEBHOOK_SECRET=whsec_...  # ⚠️ Nuevo webhook secret

# MAIL (Producción)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net  # o mailgun, ses, etc.
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=tu_api_key     # ⚠️ ACTUALIZAR
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com  # ⚠️ ACTUALIZAR
MAIL_FROM_NAME="${APP_NAME}"

# GOOGLE ANALYTICS
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX  # ⚠️ ACTUALIZAR con tu ID real

# SESSION & CACHE
SESSION_DRIVER=redis         # Mejor performance que 'file'
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# REDIS (si usas)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# FILESYSTEM
FILESYSTEM_DISK=s3           # Recomendado para producción
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=jurux-assets
```

---

### 4. `config/services.php`

**Verificar Google Analytics:**

```php
'google_analytics' => [
    'id' => env('GOOGLE_ANALYTICS_ID'), // Debe tener valor en .env
],
```

---

## 🔧 VARIABLES DE ENTORNO

### Checklist Completo `.env`

```bash
# APP
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_URL actualizada
- [ ] APP_KEY generada (php artisan key:generate)

# DATABASE
- [ ] DB_DATABASE actualizada
- [ ] DB_USERNAME actualizado
- [ ] DB_PASSWORD segura (mínimo 16 caracteres)

# STRIPE
- [ ] Claves LIVE (no test)
- [ ] Webhook secret de producción

# MAIL
- [ ] Proveedor configurado (SendGrid/Mailgun)
- [ ] MAIL_FROM_ADDRESS con tu dominio

# GOOGLE
- [ ] GOOGLE_ANALYTICS_ID configurado

# CACHE/SESSION
- [ ] Redis configurado (recomendado)
```

---

## 🗄️ BASE DE DATOS

### Migraciones y Seeders

```bash
# 1. Ejecutar migraciones en producción
php artisan migrate --force

# 2. NO ejecutar seeders en producción (solo si necesitas datos de prueba)
# php artisan db:seed  # ⚠️ Cuidado

# 3. Verificar tablas creadas
php artisan tinker
>>> \DB::select('SHOW TABLES');
```

### Backup Strategy

- [ ] Configurar backups automáticos diarios
- [ ] Probar restore de backup
- [ ] Almacenar backups fuera del servidor (S3, Dropbox)

---

## 🔌 SERVICIOS EXTERNOS

### 1. Stripe (Producción)

**Pasos:**

1. [ ] Ir a [Stripe Dashboard](https://dashboard.stripe.com)
2. [ ] Activar modo **LIVE** (toggle arriba derecha)
3. [ ] Copiar claves de producción:
   - [ ] Publishable key: `pk_live_...`
   - [ ] Secret key: `sk_live_...`
4. [ ] Crear webhook endpoint: `https://tudominio.com/stripe/webhook`
5. [ ] Seleccionar eventos:
   ```
   customer.subscription.created
   customer.subscription.updated
   customer.subscription.deleted
   invoice.payment_succeeded
   invoice.payment_failed
   ```
6. [ ] Copiar webhook secret: `whsec_...`
7. [ ] Actualizar `.env` con las claves LIVE

**Testing en Producción:**
```bash
# Probar webhook localmente primero
stripe listen --forward-to localhost:8000/stripe/webhook
```

---

### 2. Google Analytics

**Setup:**

1. [ ] Ir a [Google Analytics](https://analytics.google.com)
2. [ ] Crear propiedad nueva
3. [ ] Tipo: **Web**
4. [ ] Copiar Measurement ID: `G-XXXXXXXXXX`
5. [ ] Actualizar en `.env`:
   ```bash
   GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
   ```
6. [ ] Verificar que el código esté en `landing-new.blade.php` (líneas 14-22)

---

### 3. Google Search Console

**Registrar sitio:**

1. [ ] Ir a [Google Search Console](https://search.google.com/search-console)
2. [ ] Añadir propiedad: `https://tudominio.com`
3. [ ] Verificar propiedad (método DNS o HTML)
4. [ ] Enviar sitemap: `https://tudominio.com/sitemap.xml`
5. [ ] Solicitar indexación de URLs principales

---

### 4. Email Transaccional

**Opciones:**

**SendGrid** (Recomendado - 100 emails/día gratis)
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.tu_api_key_aqui
MAIL_ENCRYPTION=tls
```

**Mailgun** (10,000 emails/mes gratis)
```bash
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.tudominio.com
MAILGUN_SECRET=tu_secret_key
```

**Verificar configuración:**
```bash
php artisan tinker
>>> Mail::raw('Test email', function($msg) { $msg->to('tu@email.com')->subject('Test'); });
```

---

## 🔒 PERFORMANCE Y SEGURIDAD

### Optimizaciones de Performance

```bash
# 1. Cache de configuración
php artisan config:cache

# 2. Cache de rutas
php artisan route:cache

# 3. Cache de vistas
php artisan view:cache

# 4. Optimizar autoload de Composer
composer install --optimize-autoloader --no-dev

# 5. Minificar assets (ya hecho)
npm run build
```

---

### Seguridad

**Headers de Seguridad (en `public/.htaccess` o Nginx):**

```apache
# Apache (.htaccess)
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
```

```nginx
# Nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

**Otros:**
- [ ] CSRF protection habilitado (Laravel por defecto)
- [ ] Rate limiting en rutas sensibles
- [ ] Logging de eventos críticos
- [ ] Backups automáticos

---

## ✅ POST-DEPLOYMENT

### Checklist Inmediato Post-Deploy

```bash
# 1. Verificar app funciona
curl -I https://tudominio.com
# Debe responder 200 OK

# 2. Verificar HTTPS
curl -I http://tudominio.com
# Debe redirigir a https://

# 3. Test de registro de usuario
# Crear cuenta de prueba manualmente

# 4. Test de newsletter
# Suscribirse y verificar email

# 5. Verificar Stripe
# Hacer checkout de prueba con tarjeta test

# 6. Revisar logs
tail -f storage/logs/laravel.log
```

---

### Testing Funcional

- [ ] **Landing page** carga correctamente
- [ ] **Login/Register** funciona
- [ ] **Newsletter** subscribe funciona
- [ ] **Links legales** (Privacy, Terms) accesibles
- [ ] **Banner de cookies** aparece
- [ ] **Imágenes** cargan correctamente
- [ ] **Google Analytics** registra visitas
- [ ] **Responsive** funciona en mobile
- [ ] **Stripe checkout** funciona (modo test primero)

---

### Verificación SEO

- [ ] Google puede indexar el sitio
- [ ] `robots.txt` accesible: `https://tudominio.com/robots.txt`
- [ ] `sitemap.xml` accesible: `https://tudominio.com/sitemap.xml`
- [ ] Meta tags presentes (Open Graph, Twitter)
- [ ] Favicon se muestra
- [ ] Velocidad de página > 80 en PageSpeed Insights

---

## 📊 MONITOREO

### Herramientas Recomendadas

**Error Tracking:**
```bash
# Sentry
composer require sentry/sentry-laravel

# .env
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
```

**Uptime Monitoring:**
- [ ] UptimeRobot (gratis, checks cada 5 min)
- [ ] Pingdom
- [ ] Configurar alertas por email/SMS

**Performance:**
- [ ] Google PageSpeed Insights
- [ ] GTmetrix
- [ ] WebPageTest

**Analytics:**
- [ ] Google Analytics (ya configurado)
- [ ] Hotjar (heatmaps) - opcional
- [ ] Mixpanel (eventos) - opcional

---

## 🚨 ROLLBACK PLAN

### Si algo sale mal

```bash
# 1. Volver a versión anterior
git checkout <commit-hash-anterior>

# 2. Restaurar base de datos
mysql -u usuario -p database < backup.sql

# 3. Limpiar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Re-deploy
git push production main
```

---

## 📞 CONTACTOS CRÍTICOS

**Soporte técnico:**
- **Hosting:** [Email/Teléfono del proveedor]
- **DNS/Dominio:** [Email de Namecheap/Porkbun]
- **Stripe:** support@stripe.com
- **Desarrollador:** [Tu email]

---

## 📝 NOTAS ADICIONALES

### Costos Mensuales Estimados (Producción)

```
Dominio: ~$15/año ($1.25/mes)
Hosting (DigitalOcean Droplet 1GB): $6/mes
SSL (Let's Encrypt): GRATIS
Email (SendGrid): GRATIS (hasta 100/día)
Stripe: 3% + $0.30 por transacción
Google Analytics: GRATIS
Total base: ~$7-10/mes
```

### Timeline de Deployment

```
1. Comprar dominio: 10 min
2. Configurar DNS: 5 min (propagación: 1-24 hrs)
3. Setup hosting: 30 min
4. Actualizar archivos: 15 min
5. Configurar .env: 20 min
6. Deploy código: 10 min
7. Configurar servicios: 30 min
8. Testing: 1 hora
---
Total: ~3-4 horas (+ espera de DNS)
```

---

## ✅ CHECKLIST FINAL

Antes de marcar como "DEPLOYED":

- [ ] Dominio apunta correctamente
- [ ] SSL/HTTPS funciona
- [ ] `.env` actualizado completamente
- [ ] `robots.txt` actualizado
- [ ] `sitemap.xml` actualizado
- [ ] Google Analytics funciona
- [ ] Stripe en modo LIVE
- [ ] Email transaccional funciona
- [ ] Todos los tests pasan
- [ ] Monitoreo configurado
- [ ] Backups automáticos activos
- [ ] Google Search Console registrado
- [ ] Logs sin errores
- [ ] Performance aceptable (>80 PageSpeed)

---

**🎉 ¡Listo para producción!**

---

**Última revisión:** 15 Nov 2025
**Próxima revisión:** Cuando hagas deployment
**Responsable:** José Luis
