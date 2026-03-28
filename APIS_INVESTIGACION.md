# Investigación de APIs para Vera - Marzo 2026

> **IMPORTANTE:** WhatsApp y Gmail OAuth requieren procesos de 2-6 semanas. Iniciar cuanto antes.

---

## Resumen Ejecutivo de Prioridades

| Prioridad | API | Tiempo acceso | Acción inmediata |
|---|---|---|---|
| 1 | **Telegram Bot** | <2 minutos | Crear bot hoy en @BotFather |
| 2 | **Llamadas (Twilio)** | Inmediato | Crear cuenta trial twilio.com |
| 3 | **WhatsApp Cloud API** | 5-15 días | Verificar Facebook Business Manager |
| 4 | **Microsoft Outlook** | 1-5 días | Registrarse en partner.microsoft.com |
| 5 | **Gmail OAuth** | 2-4 meses | Iniciar verificación Google ya — es la más urgente por lo que tarda |
| 6 | **Google Calendar** | Ligado a Gmail | Se añade al mismo formulario OAuth de Gmail |

---

## 1. WhatsApp Business Cloud API (Meta)

### Estado 2025
- On-Premises API **eliminado** el 23 oct 2025. Solo existe **Cloud API**.
- URL oficial: https://developers.facebook.com/docs/whatsapp/overview

### Cómo solicitar acceso
**Ruta directa (5-15 días):**
1. Crear/verificar cuenta en **Facebook Business Manager**
2. Verificar empresa con documentos oficiales (registro mercantil, web)
3. Añadir número de teléfono dedicado (que NO esté activo en WhatsApp)
4. Crear cuenta WhatsApp Business desde Meta Developers
5. Crear templates de mensajes (aprobación 24-48h cada uno)

**Ruta via BSP - Business Solution Provider (24-72 horas):**
- Providers recomendados: **360dialog**, Wati, Interakt
- Más rápido pero con coste mensual adicional del BSP
- 360dialog es el más usado con Laravel/API directa

### Requisitos obligatorios
- Cuenta Facebook Business Manager **verificada**
- Número de teléfono dedicado (móvil o fijo, inactivo en WhatsApp)
- Dominio web del negocio
- Para más de 1.000 conversaciones/día: documentación empresa (CIF, registro mercantil)

### Verificación de empresa — Documentos para autónomos

**Registro Mercantil → NO obligatorio** para autónomos. Meta tiene categoría específica para freelancers/autónomos.

| Documento | Validez para Meta |
|---|---|
| **Modelo 036** (alta censal en Hacienda) | Principal — alta probabilidad de aceptación |
| Factura de suministro (luz/internet) a nombre del negocio | Válido para acreditar nombre + dirección |
| Extracto bancario de cuenta profesional | Válido si aparece el nombre de actividad |

> ⚠️ El **Modelo 037 fue eliminado en febrero de 2025**. El documento relevante ahora es el Modelo 036.

**Dominio + SSL solo → no es suficiente.** El dominio verificado confirma que controlas el sitio, pero Meta necesita además un documento legal. Son dos requisitos independientes.

**Requisitos previos antes de solicitar verificación:**
- Business Manager con varias semanas de antigüedad
- Autenticación en dos pasos activada
- Email corporativo del dominio propio (no @gmail, @hotmail, etc.)
- Dominio verificado en Meta Business Manager

**Proceso paso a paso:**
1. Meta Business Manager → Configuración → Centro de Seguridad → "Iniciar verificación"
2. Nombre legal exactamente igual que en el Modelo 036
3. Subir: Modelo 036 + factura de suministro o extracto bancario
4. Esperar 1-5 días hábiles

### Cómo funciona técnicamente
```
Usuario escribe → Meta → POST a tu webhook → Tu lógica Laravel → Respuesta via API REST
```
- Webhooks HTTPS POST con JSON
- Tu servidor debe responder HTTP 200 en <5 segundos
- SDK PHP oficial disponible para Laravel

### Templates (obligatorios para iniciar conversaciones)
| Categoría | Uso | Coste |
|---|---|---|
| Marketing | Promociones, ofertas | Estándar |
| Utility | Confirmaciones, recordatorios | Descuento/gratis en ventana 24h |
| Authentication | Códigos OTP | Descuento |
| Service | Respuestas a mensajes entrantes | Sin coste de template |

### Precios (desde julio 2025: por mensaje, no por conversación)
- España/LATAM marketing: ~€0.03-0.08 por mensaje
- Utility dentro de ventana 24h de servicio: **gratuito**
- Calculadora oficial: https://business.whatsapp.com/products/platform-pricing

### Límites de capacidad
- Tier 1 inicial: 1.000 conversaciones únicas/día
- Tier 2: 10.000/día (sube automático)
- Tier 3: 100.000/día
- Tier 4: ilimitado (solicitud a Meta)

---

## 2. Telegram Bot API

### Proceso: INMEDIATO, sin aprobación

No hay verificación de empresa. Todo via @BotFather en Telegram.

**Pasos (< 2 minutos):**
1. Abrir Telegram → buscar `@BotFather` (checkmark azul oficial)
2. Enviar `/newbot`
3. Elegir nombre (ej: "Vera Asistente") y username (debe terminar en `bot`)
4. Copiar el API token recibido

- Documentación: https://core.telegram.org/bots/api
- **Coste: 100% gratuito, sin límite de mensajes de pago**

### Webhooks
```bash
# Registrar webhook
https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://tudominio.com/webhook/telegram
```
**Requisitos del servidor:**
- HTTPS obligatorio (SSL válido)
- Solo puertos 443, 80, 88 o 8443
- Respuesta rápida al POST recibido

### Límites gratuitos
| Límite | Valor |
|---|---|
| Mensajes por segundo (global bot) | 30 msg/seg |
| Mismo chat | 1 msg/seg recomendado |
| Tamaño de archivo | 50 MB |
| Texto por mensaje | 4.096 caracteres |

**No existe plan de pago.** Los límites son iguales para todos.

---

## 3. Llamadas Telefónicas

### Google Voice API: NO EXISTE para desarrolladores externos
Google Voice no tiene API pública. No es una opción.

### Google Cloud CCAI: Solo enterprise
- Miles de USD/mes. No viable para inicio.

### ✅ Recomendado: Twilio Voice API

**Por qué Twilio:**
- SDK PHP oficial compatible con Laravel
- Cuenta trial gratuita con ~$15 USD de crédito
- Registro inmediato, sin aprobación
- Números locales españoles disponibles

**Precios orientativos (2025):**
- Llamadas entrantes: ~$0.0085/min
- Llamadas salientes: ~$0.0140/min
- Llamadas vía navegador/app: $0.0040/min

**URL:** https://www.twilio.com/en-us/voice
**Registro:** twilio.com → sin verificación de empresa para empezar

**Alternativas más baratas:**
- **Telnyx**: ~30% más barato que Twilio, compatible con su código
- **Vonage (Nexmo)**: fuerte en Europa

### Para IA de voz avanzada (agentes que hablan)
- **Retell AI + Twilio**: conversaciones con IA en tiempo real, latencia <1 segundo
- **ElevenLabs + Twilio**: voz sintética de alta calidad

---

## 4. Gmail API (ya implementada - scopes verificados)

### Verificación OAuth de Google (EL PROCESO QUE TARDA)

La Gmail API ya está implementada en Vera, pero si se usan scopes restringidos,
**necesita verificación formal de Google**.

### Tipos de scopes y su impacto

| Tipo | Scopes de ejemplo | Verificación |
|---|---|---|
| No sensible | openid, email, profile | Ninguna |
| **Sensible** | gmail.readonly, gmail.send | 2-6 semanas |
| **Restringido** | gmail, gmail.modify | 2-4 meses + CASA audit |

### ¿Qué scopes usa Vera actualmente?
→ **Verificado en `app/Http/Controllers/EmailAccountController.php` línea 67-72**

```php
'https://www.googleapis.com/auth/gmail.readonly',
'https://www.googleapis.com/auth/gmail.send',
'https://www.googleapis.com/auth/gmail.modify',   // ⚠️ RESTRINGIDO
'https://www.googleapis.com/auth/gmail.labels',
```

**Vera usa `gmail.modify` → categoría RESTRINGIDA → proceso de 2-4 meses + CASA audit ($500-4.500 USD)**

> ⚠️ **¿Realmente necesita Vera modificar emails, o puede funcionar solo leyendo y enviando?**
> Si se elimina `gmail.modify`, el proceso baja de 2-4 meses a 2-6 semanas y de hasta $4.500 USD a cero.
> **Esta decisión hay que tomarla antes de iniciar el proceso con Google.**


### Proceso de verificación por fases

**Fase 1 - Brand Verification (2-3 días):**
- Verificar dominio en Google Search Console
- Logo empresa en OAuth Consent Screen
- Enlace a política de privacidad pública

**Fase 2 - Sensitive Scope Review (2-6 semanas):**
- Formulario justificando uso de cada scope
- Video demostrativo del flujo de autorización
- Revisión manual por Google

**Fase 3 - CASA Security Audit (solo scopes restringidos):**
- Tier 2: 1-3 semanas + $500-1.000 USD
- Tier 3 (Workspace Marketplace): 2-4 semanas + ~$4.500 USD
- Renovación anual obligatoria

### Límites de uso Gmail API en producción

| Métrica | Límite |
|---|---|
| Quota units por día (proyecto) | 1.000.000.000 |
| Envíos usuario Gmail gratuito | 500 emails/día |
| Envíos usuario Google Workspace | 2.000 emails/día |
| Tamaño máximo adjunto | 25 MB |


### Estrategia para Vera

- **Corto plazo:** Si todos los usuarios son del mismo Google Workspace → modo "Internal" en OAuth Consent Screen. Sin verificación necesaria.
- **Multi-tenant (usuarios externos):** Modo "Externo" con máximo 100 usuarios mientras se tramita verificación.
- **Evitar scopes restringidos** si es posible → usar solo `gmail.send` + `gmail.readonly`.

### Límite durante proceso de verificación
- Máximo **100 usuarios únicos** que pueden autorizar la app mientras está sin verificar.

---

## 5. Microsoft Outlook / Exchange (ya implementado - scopes verificados)

### Verificación Publisher de Microsoft (mucho más fácil que Google)

Sin verificación, los usuarios ven una **advertencia roja** en la pantalla de consentimiento: "Esta aplicación no está verificada". Genera desconfianza y abandono.

El proceso para eliminarla es gratuito y tarda solo **1-5 días**.

### Qué scopes usa Vera actualmente
→ **Verificado en `app/Http/Controllers/EmailAccountController.php` línea 168-171**

```php
'Mail.Read',
'Mail.Send',
'Mail.ReadWrite',
```

**Buena noticia:** estos scopes en Microsoft NO requieren auditoría externa. Son delegated permissions — el usuario consiente por su cuenta, sin necesitar a su administrador.

### Cómo conseguir el Publisher Verified badge

1. Registrarse en **Microsoft Partner Network (MPN)** → gratuito → https://partner.microsoft.com
2. Verificar el dominio de tu empresa en MPN (1-3 días)
3. En Azure Portal → App Registration → Branding → enlazar el MPN ID
4. El badge aparece automáticamente en la pantalla de consentimiento

**Tiempo: 1-5 días hábiles. Coste: $0**

### Límites de uso Microsoft Graph API

| Métrica | Límite |
|---|---|
| Requests por app por tenant | 10.000 req / 10 minutos |
| Requests por usuario por app | 10.000 req / 10 minutos |
| Si se supera | HTTP 429 con cabecera Retry-After |

### Diferencias clave vs Google OAuth

| Aspecto | Microsoft | Google |
|---|---|---|
| Verificación básica | Gratuita, 1-5 días | Gratuita, 2-6 semanas |
| Auditoría de seguridad | **NO obligatoria** | Obligatoria para scopes sensibles |
| Coste auditoría | $0 | $500-4.500 USD (CASA) |
| Límite sin verificar | Sin límite técnico (solo advertencia) | **100 usuarios máximo** |

### Acción inmediata
- Registrarse en https://partner.microsoft.com (gratuito)
- Obtener MPN ID y verificar dominio de empresa

---

## 6. Google Calendar API (core del producto — sin backend aún)

El código `GoogleCalendarService.php` ya está implementado pero necesita credenciales reales.

- **Estado verificado:** `app/Http/Controllers/VirtualSecretaryController.php` → `$events = []` — sin conexión real
- **Scopes necesarios:** `calendar.events` + `calendar.readonly`
- **Proceso:** Se añade al mismo OAuth Consent Screen que Gmail — no es un proceso separado
- **Prioridad:** CRÍTICA — sin Calendar no hay producto

**Lo que falta:**
1. Credenciales Google Calendar en `config/services.php`
2. Añadir scopes de Calendar al flujo OAuth existente
3. Ejecutar migración `2026_03_25_000001_add_agenda_fields_to_vera_configs_table.php`

---

## Tabla Resumen Final

| API | Tiempo acceso | Coste inicial | Verificación empresa | Complejidad |
|---|---|---|---|---|
| Telegram Bot API | **<2 minutos** | **Gratuito** | No | Baja |
| Twilio Voice | **Inmediato** | Pay-as-you-go | No (inicial) | Media |
| WhatsApp Cloud API (directo) | 5-15 días | Gratis (pago por msg) | **Sí, obligatoria** | Media |
| WhatsApp via BSP (ej: 360dialog) | 24-72 horas | Cuota BSP + msgs | Sí | Baja — ver opción en sección WhatsApp |
| Microsoft Outlook | 1-5 días | **Gratuito** | Sí (MPN) | Baja |
| Gmail API (sensible) | 2-6 semanas | Gratuito | Recomendado | Media |
| Gmail API (restringido) | 2-4 meses | $500-4.500 USD CASA | Sí | Alta |
| Google Calendar | Ligado a Gmail | Ligado a Gmail | Sí | Media |

---

## Acciones Inmediatas (HOY)

**Código ya listo — solo falta configuración:**

1. **Ejecutar migraciones:**
   ```bash
   php artisan migrate
   ```

2. **Telegram:** Crear bot en @BotFather → copiar token al `.env`
3. **Twilio:** Crear cuenta trial en twilio.com → copiar credenciales al `.env`
4. **Gmail + Google Calendar:** Iniciar verificación OAuth en Google Cloud Console — es lo más urgente por tiempo
5. **WhatsApp:** Crear/verificar cuenta en Facebook Business Manager
6. **Microsoft Outlook:** Registrarse en partner.microsoft.com (gratuito)

## Documentación oficial

- WhatsApp: https://developers.facebook.com/docs/whatsapp/overview
- Telegram: https://core.telegram.org/bots/api
- Twilio Voice: https://www.twilio.com/en-us/voice
- Gmail API Quotas: https://developers.google.com/workspace/gmail/api/reference/quota
- Google OAuth Verification: https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification
