# 🚀 PLAN DE LANZAMIENTO — Vera AI
## Hoja de ruta completa hasta el lanzamiento

**Creado:** 12/02/2026
**Estado:** En progreso
**Objetivo:** MVP funcional → Landing → Contenido → Lanzamiento

---

## 📊 RESUMEN DE FASES

| Fase | Descripción | Estado |
|------|-------------|--------|
| FASE A | Testing completo del flujo real (grabación) | ⏳ Pendiente |
| FASE B | Landing page operativa y creativa | ⏳ Pendiente |
| FASE C | Creación de contenido (Heygen + RRSS) | ⏳ Pendiente |
| FASE D | Diseño e implementación del sistema de tokens | ⏳ Pendiente |
| FASE E | Auditoría de seguridad (checklist Google Drive) | ⏳ Pendiente |
| FASE F | Fixes críticos + mejoras UX | ⏳ Pendiente |
| FASE G | Testing final al 100% | ⏳ Pendiente |

---

## 🅐 FASE A — TESTING COMPLETO DEL FLUJO (Para grabación)

> **Objetivo:** Probar TODA la funcionalidad como si fuéramos un usuario nuevo.
> Este flujo se **grabará en vídeo** para:
> - Redes sociales
> - Vídeos de demostración
> - Imágenes para la landing page

### Checklist del flujo completo

#### 1. Onboarding — Usuario nuevo
- [ ] Registro de cuenta nueva
- [ ] Verificación de email
- [ ] Primera pantalla / bienvenida
- [ ] Conectar cuenta de Gmail (OAuth2)
- [ ] Sincronización inicial de emails

#### 2. Configuración de Vera AI
- [ ] Cuestionario inicial de Vera (perfil de empresa, tono, contexto)
- [ ] Configurar nivel de autonomía (sugerir / preguntar / auto-responder)
- [ ] Configurar estilo de escritura (formal / profesional / casual)
- [ ] Configurar horario de disponibilidad
- [ ] Configurar remitentes bloqueados / prioritarios
- [ ] Configurar categorías permitidas/bloqueadas

#### 3. Gestión de Emails — Funcionalidad Core
- [ ] Ver lista de emails sincronizados
- [ ] Abrir email y ver análisis de Vera
- [ ] Vera sugiere respuesta → usuario la edita y envía
- [ ] Vera auto-responde un email (modo automático)
- [ ] Cambiar workflow state de un email (estados: unread → in_progress → completed)
- [ ] Categorización automática de emails por Vera
- [ ] Filtrar emails por categoría / estado / fecha

#### 4. Respuestas según autoridad y tono
- [ ] Email formal → Vera responde en tono formal
- [ ] Email casual → Vera responde en tono casual
- [ ] Email con remitente prioritario → Vera responde siempre
- [ ] Email con remitente bloqueado → Vera NO responde
- [ ] Email fuera del horario → Vera NO responde
- [ ] Email de newsletter → Vera NO responde (no es persona real)

#### 5. Gestión avanzada
- [ ] Crear borrador manualmente
- [ ] Ver lista de borradores
- [ ] Programar envío de email para una fecha futura
- [ ] Ver emails programados pendientes
- [ ] Cancelar email programado

#### 6. Sistema de carpetas
- [ ] Crear carpeta nueva
- [ ] Crear subcarpeta
- [ ] Mover email a carpeta
- [ ] Ver emails filtrados por carpeta

#### 7. Templates de respuesta
- [ ] Crear template con placeholders ({{nombre}}, {{fecha}})
- [ ] Usar template para responder un email
- [ ] Ver preview del template con datos reales

#### 8. Alertas y seguimiento
- [ ] Ver centro de alertas (`/email-alerts`)
- [ ] Asignar due date a un email
- [ ] Email con due date vencido → aparece alerta
- [ ] Posponer email (snooze) — despertar a la hora indicada
- [ ] Crear recordatorio de seguimiento (follow-up reminder)

#### 9. Seguridad
- [ ] Ver sesiones activas (`/security/sessions`)
- [ ] Ver dashboard de seguridad (`/security/dashboard`)
- [ ] Activar 2FA con Google Authenticator
- [ ] Cerrar sesión y volver a entrar con 2FA activo
- [ ] Ver logs de auditoría (`/security/audit`)

#### 10. Auto-responder
- [ ] Crear auto-responder "Fuera de oficina"
- [ ] Recibir email mientras está activo → se envía auto-respuesta
- [ ] Verificar que solo responde una vez por remitente

### 📝 Notas para la grabación
- Usar cuenta real de Gmail con emails reales (o preparar emails de prueba)
- Grabar en resolución 1920x1080 mínimo
- Preparar escenario: emails variados (urgentes, newsletters, formales, casuales)
- Tener Google Authenticator en el móvil para demostrar 2FA
- **Aprovechar imágenes/screenshots para la landing page**

---

## 🅑 FASE B — LANDING PAGE OPERATIVA

> **Objetivo:** Landing page creativa, profesional y con contenido real.
> Referencia visual: ver libreta de apuntes + referencias guardadas.

### Estructura de la landing

#### Secciones obligatorias
- [ ] **Hero section** — Titular impactante + CTA + vídeo/animación de Vera en acción
- [ ] **Problema → Solución** — El dolor del usuario, cómo Vera lo resuelve
- [ ] **Funcionalidades clave** — 4-6 features con iconos y screenshots reales
- [ ] **Cómo funciona** — Paso a paso (Conecta → Configura → Delega → Resultado)
- [ ] **Demostración** — Vídeo de demostración (grabado en Fase A)
- [ ] **Planes y precios** — Basic, Starter, Pro, Enterprise
- [ ] **Testimonios/Social proof** — Placeholder hasta tener usuarios reales
- [ ] **FAQ** — Preguntas frecuentes
- [ ] **CTA final** — Registro + waitlist

#### Recursos necesarios
- [ ] Screenshots reales del producto (de la grabación Fase A)
- [ ] Vídeo de demostración (de la grabación Fase A)
- [ ] Identidad visual de Vera (colores, fuentes, logo)
- [ ] Copys / textos definitivos
- [ ] **Referencia de landing que guste** → anotar aquí: _________________

#### Integraciones técnicas
- [ ] Formulario de waitlist / registro anticipado funcional
- [ ] Analytics (Google Analytics o similar)
- [ ] Meta pixel para remarketing
- [ ] SEO básico (meta tags, OG tags)

---

## 🅒 FASE C — CREACIÓN DE CONTENIDO (Vender antes de tener el producto)

> **Objetivo:** Generar audiencia y leads antes del lanzamiento oficial.
> Herramienta principal: **Heygen** (vídeos con avatar de Vera)
> Identidad: Mantener siempre la identidad y voz de **Vera AI**

### Contenido a crear

#### Vídeos con Heygen
- [ ] Vídeo de presentación de Vera (60 segundos)
- [ ] Vídeo explicando el problema que resuelve (30 segundos)
- [ ] Vídeo mostrando una funcionalidad específica (serie)
- [ ] Vídeo testimonial / caso de uso
- [ ] Shorts / Reels (15 segundos) para TikTok/Instagram

#### Contenido para RRSS
- [ ] Posts de LinkedIn (target: empresas, CEOs, VAs)
- [ ] Posts de Instagram (visual, antes/después)
- [ ] Threads en X/Twitter (educación + producto)
- [ ] Carruseles explicativos

#### Assets necesarios
- [ ] Screenshots del producto (de Fase A)
- [ ] GIFs de funcionalidades clave
- [ ] Plantillas Canva para posts
- [ ] Guión para vídeos Heygen

#### Calendario de contenido
- [ ] Semana 1: Presentación del problema
- [ ] Semana 2: Presentación de la solución (Vera)
- [ ] Semana 3: Funcionalidades destacadas
- [ ] Semana 4: CTA a waitlist / registro

---

## 🅓 FASE D — SISTEMA DE TOKENS

> **Objetivo:** Implementar el diseño de tokens definido en Google Drive.
> **Referencia:** Carpeta Google Drive → diseño de tokens

### Pendiente revisar en Google Drive
- [ ] Revisar documento de diseño de tokens
- [ ] Entender la estructura: ¿qué son los tokens en Vera? ¿créditos de IA?
- [ ] ¿Cómo se consumen? ¿Por email procesado? ¿Por respuesta generada?
- [ ] ¿Cómo se recargan? ¿Compra, suscripción, plan?

### Implementación (una vez revisado el diseño)
- [ ] Modelo de datos para tokens
- [ ] Sistema de consumo automático
- [ ] Dashboard de tokens para el usuario
- [ ] Alertas cuando quedan pocos tokens
- [ ] Sistema de recarga / compra de tokens

---

## 🅔 FASE E — AUDITORÍA DE SEGURIDAD

> **Objetivo:** Verificar que TODO lo que pone en el documento de seguridad
> de Google Drive está implementado y funcional.
> **Referencia:** Google Drive → carpeta seguridad

### Puntos a verificar (completar tras revisar el documento de Drive)
- [ ] Revisar checklist de seguridad del documento de Drive
- [ ] Verificar punto por punto vs. implementación actual
- [ ] Documentar lo que falta
- [ ] Implementar lo que falte

### Lo que YA está implementado (para referencia)
- ✅ 2FA (Google Authenticator + recovery codes)
- ✅ Gestión de sesiones activas
- ✅ Detección de dispositivos/ubicación en sesiones
- ✅ Política de contraseñas con historial
- ✅ RGPD (exportar/eliminar datos)
- ✅ Auditoría y logs con filtros + export CSV
- ✅ Dashboard de seguridad unificado
- ✅ CSRF protection
- ✅ Passwords hasheadas con bcrypt

---

## 🅕 FASE F — FIXES CRÍTICOS + MEJORAS UX

> **Objetivo:** Corregir los bugs reales encontrados en el diagnóstico.
> **IMPORTANTE:** Solo hacer esto tras las fases anteriores.

### 🔴 Bugs críticos (backend)

**F1. `VeraResetMonthlyLimits` no está en el Kernel**
- Consecuencia: límites mensuales de Vera NUNCA se resetean
- Fix: añadir al Kernel el día 1 de cada mes
- Estado: ⏳ Pendiente

**F2. `ProcessGDPRRequests` no está en el Kernel**
- Consecuencia: solicitudes RGPD nunca se procesan automáticamente
- Fix: programar diariamente
- Estado: ⏳ Pendiente

**F3. `CheckPasswordExpiry` no está en el Kernel**
- Consecuencia: política de contraseñas nunca verifica vencimientos
- Fix: programar diariamente
- Estado: ⏳ Pendiente

### 🟡 Mejoras UX

**F4. `showNotification('info')` muestra en ROJO**
- "Enviando email..." aparece en rojo → sensación de error
- Fix: añadir colores info(azul) y warning(amarillo)
- Estado: ⏳ Pendiente

**F5. 20 `alert()` en `emails/index.blade.php`**
- Usar `showNotification()` que ya existe en esa vista
- Fix: reemplazar los 20 alert() por showNotification()
- Estado: ⏳ Pendiente

**F6. `contacts/index` y `organizations/show` usan solo `alert()`**
- 8 alerts en contacts, 14 en organizations
- Fix: añadir showNotification() y reemplazar
- Estado: ⏳ Pendiente

---

## 🅖 FASE G — TESTING FINAL AL 100%

> **Objetivo:** Verificar que absolutamente todo funciona antes del lanzamiento.

### Checklist final

#### Funcionalidad core
- [ ] Login normal funciona
- [ ] Login con 2FA funciona
- [ ] Sincronización de Gmail funciona
- [ ] Lectura de emails funciona
- [ ] Envío de emails funciona
- [ ] Vera AI genera respuestas sugeridas
- [ ] Sistema de carpetas funciona
- [ ] Búsqueda de emails funciona
- [ ] Filtros y categorías funcionan
- [ ] Workflow states funcionan
- [ ] Templates de respuesta funcionan
- [ ] Emails programados se envían en la hora correcta
- [ ] Alertas se generan correctamente
- [ ] Auto-responders funcionan
- [ ] Follow-up reminders funcionan
- [ ] Multi-tenancy funciona (organizaciones)
- [ ] Roles y permisos funcionan
- [ ] RGPD compliance (exportar/eliminar datos)

#### Scheduler (cron jobs)
- [ ] `emails:send-scheduled` — cada minuto ✅ en Kernel
- [ ] `vera:process-emails` — cada 5 min ✅ en Kernel
- [ ] `emails:wakeup-snoozed` — cada 5 min ✅ en Kernel
- [ ] `emails:process-auto-responders` — cada 5 min ✅ en Kernel
- [ ] `emails:check-alerts` — cada hora ✅ en Kernel
- [ ] `emails:process-escalations` — cada 30 min ✅ en Kernel
- [ ] `emails:send-follow-up-reminders` — cada 15 min ✅ en Kernel
- [ ] `emails:clean-old-trash` — diario 3AM ✅ en Kernel
- [ ] `sessions:clean-expired` — diario 4AM ✅ en Kernel
- [ ] `vera:weekly-reanalysis` — semanal domingos 2AM ✅ en Kernel
- [ ] `vera:reset-monthly-limits` — día 1 cada mes ⏳ (Fase F)
- [ ] `gdpr:process-requests` — diario ⏳ (Fase F)
- [ ] `security:check-password-expiry` — diario ⏳ (Fase F)

#### Seguridad
- [ ] No hay vulnerabilidades XSS
- [ ] No hay inyección SQL
- [ ] CSRF protection activo
- [ ] 2FA funciona al 100%
- [ ] Sesiones seguras
- [ ] Todos los commands críticos en Kernel

#### UX/UI
- [ ] Sin errores en consola del navegador
- [ ] Notificaciones toast (sin alert() del navegador)
- [ ] Estados de loading claros en todas las vistas
- [ ] Mensajes de error útiles y consistentes

---

## 📓 PENDIENTE — Revisar libreta de apuntes

> **IMPORTANTE:** Antes de dar el plan por completo, revisar la libreta de apuntes
> por si hay funcionalidades o ideas que no están recogidas en este plan.

- [ ] Revisar libreta de apuntes completa
- [ ] Añadir al plan lo que falte
- [ ] Marcar como revisado

---

## 📁 REFERENCIAS GOOGLE DRIVE

- [ ] **Documento de redacto/plan general** → revisar y alinear con este plan
- [ ] **Carpeta de diseño de tokens** → necesario para Fase D
- [ ] **Documento de seguridad** → necesario para Fase E
- [ ] **Identidad de Vera** → necesario para Fase B y C

---

## 📊 PROGRESO GENERAL

| Fase | Progreso |
|------|----------|
| A — Testing + grabación | 0% ░░░░░░░░░░ |
| B — Landing page | 0% ░░░░░░░░░░ |
| C — Contenido | 0% ░░░░░░░░░░ |
| D — Tokens | 0% ░░░░░░░░░░ |
| E — Auditoría seguridad | 0% ░░░░░░░░░░ |
| F — Fixes + mejoras UX | 0% ░░░░░░░░░░ |
| G — Testing final | 0% ░░░░░░░░░░ |

**Software MVP actual: 90%** ██████████████████░░

---

*Última actualización: 12/02/2026*
