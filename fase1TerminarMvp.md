# 🎯 PLAN FASE 1.0 - MVP FINAL
## Virtual Employee (Vera AI)

**Objetivo:** Revisar, testear, mejorar y completar TODO lo implementado + añadir 2FA

**Duración estimada:** 2-3 semanas

---

## 📊 ESTRUCTURA DEL PLAN

### ✅ PARTE 1: REVISIÓN Y TESTING (1 semana)
Revisar cada funcionalidad implementada, testearla y documentar bugs/mejoras

### 🔧 PARTE 2: MEJORAS Y PULIDO (1 semana)
Arreglar bugs, optimizar frontend/backend, mejorar UX/UI

### 🔐 PARTE 3: IMPLEMENTAR 2FA (3-5 días)
Completar la única funcionalidad crítica que falta

### ✅ PARTE 4: TESTING FINAL (2-3 días)
Verificar que todo funciona al 100%

---

## 🔍 PARTE 1: REVISIÓN Y TESTING

### **DÍA 1-2: SUB-FASE 1A - GESTIÓN BÁSICA**

#### ✅ Funcionalidades a revisar:

**1. Workflow States (Estados de emails)**
- [x] Verificar que los estados funcionan: unread, pending_validation, pending_response, in_progress, in_review, completed, archived, no_action_needed
- [x] Testear cambio de estados desde el dropdown
- [x] Verificar que los badges de colores se ven correctamente
- [x] Comprobar que los filtros por estado funcionan

**2. Carpetas Jerárquicas**
- [x] Crear carpetas (OK - ya funciona)
- [x] Subcarpetas recursivas (OK - arreglado hoy)
- [x] Mover emails entre carpetas
- [x] Eliminar carpetas
- [x] Eliminar carpetas
- [x] Eliminar carpetas
- [x] Eliminar carpetas
- [x] Eliminar carpetas
- [x] Editar carpetas (nombre, color, icono)
- [x] Verificar contador de emails por carpeta

**3. Búsqueda Avanzada**
- [x] Buscar por asunto
- [x] Buscar por remitente
- [x] Buscar por contenido
- [x] Filtros combinados (fecha + categoría + estado)
- [x] Verificar que la paginación funciona**

**4. Borradores y Papelera** ✅ 100% COMPLETADO
- [x] Mover a papelera (individual y bulk)
- [x] Restaurar de papelera
- [x] Eliminar permanentemente
- [x] Vista de papelera con sidebar sincronizado
- [x] Crear y guardar borradores (modal funcional + AJAX)
- [x] Vista de borradores con sidebar sincronizado

**5. Due Dates y Snooze** ✅ 95% COMPLETADO
- [x] Asignar fecha límite a un email
- [x] Posponer email (snooze)
- [x] Alertas de emails vencidos
- [x] Mostrar emails con due date próximo
- [x] Notificaciones dinámicas automáticas
- [x] Sistema de polling cada 2 minutos
- [x] Todo funciona sin reload (100% AJAX)
- [ ] Testing completo final (pendiente verificación)

**Bugs/Mejoras a documentar:**
- Frontend: ¿Los estilos son consistentes?
- Backend: ¿Las queries son eficientes?
- UX: ¿Es intuitivo para el usuario?

---

### **DÍA 3-4: SUB-FASE 1B - RESPUESTAS Y AUTOMATIZACIÓN**

#### ✅ Funcionalidades a revisar:

**1. Templates de Respuesta**
- [ ] Crear template con placeholders ({nombre}, {fecha}, etc.)
- [ ] Editar templates
- [ ] Eliminar templates
- [ ] Usar template para responder email
- [ ] Preview del template antes de enviar
- [ ] Verificar que los placeholders se reemplazan correctamente

**2. Emails Programados (Scheduled Emails)**
- [ ] Programar envío de email para fecha futura
- [ ] Ver lista de emails programados
- [ ] Editar email programado antes de que se envíe
- [ ] Cancelar email programado
- [ ] Verificar que el Command ejecuta cada minuto
- [ ] Confirmar que se envían a la hora correcta

**3. Auto-responders**
- [ ] Configurar respuesta automática (fuera de oficina)
- [ ] Activar/desactivar auto-responder
- [ ] Personalizar mensaje de auto-respuesta
- [ ] Verificar que solo responde una vez por remitente
- [ ] Fecha de inicio/fin de auto-responder

**4. Integración con Vera AI**
- [ ] Generar sugerencia de respuesta con IA
- [ ] Analizar sentimiento del email
- [ ] Clasificación automática de emails
- [ ] Extracción de información clave

**Bugs/Mejoras a documentar:**
- ¿El editor WYSIWYG funciona bien?
- ¿Los placeholders son suficientes?
- ¿Falta algún tipo de automatización común?

---

### **DÍA 5-6: SUB-FASE 1C - ALERTAS Y SEGUIMIENTO**

#### ✅ Funcionalidades a revisar:

**1. Contactos Auto-sync**
- [ ] Crear contactos automáticamente desde emails
- [ ] Ver lista de contactos
- [ ] Ver historial de emails con cada contacto
- [ ] Editar información de contacto
- [ ] Estadísticas por contacto (emails enviados/recibidos)

**2. Sistema de Alertas**
- [ ] Alerta de email sin responder > 24h
- [ ] Alerta de deadline próximo
- [ ] Alerta de email prioritario sin leer
- [ ] Centro de notificaciones/alertas
- [ ] Marcar alerta como vista

**3. Escalados Automáticos**
- [ ] Configurar reglas de escalado
- [ ] Escalar email si no se responde en X tiempo
- [ ] Notificar a supervisor/manager
- [ ] Historial de escalados

**4. Follow-up Reminders**
- [ ] Crear recordatorio de seguimiento
- [ ] Recibir notificación de follow-up
- [ ] Marcar follow-up como completado
- [ ] Lista de follow-ups pendientes

**Bugs/Mejoras a documentar:**
- ¿Las notificaciones son molestas o útiles?
- ¿Frecuencia correcta de alertas?
- ¿Falta algún tipo de alerta importante?

---

### **DÍA 7: SUB-FASE 1D - MULTI-TENANCY Y ROLES**

#### ✅ Funcionalidades a revisar:

**1. Organizaciones**
- [ ] Crear organización
- [ ] Invitar usuarios a organización
- [ ] Aceptar invitación
- [ ] Ver miembros de la organización
- [ ] Salir de organización

**2. Roles y Permisos (Spatie Permission)**
- [ ] Verificar roles: owner, admin, manager, secretary, administrative, employee
- [ ] Probar permisos de cada rol:
  - Owner: puede todo
  - Admin: gestión de organización
  - Manager: gestión de equipo
  - Secretary: gestión de emails del jefe
  - Administrative: operativo limitado
  - Employee: solo emails propios
- [ ] Compartir email con otro usuario
- [ ] Compartir carpeta con permisos (lectura/escritura)

**3. Políticas (Policies)**
- [ ] EmailPolicy: solo puede ver/editar emails propios o compartidos
- [ ] FolderPolicy: solo puede gestionar carpetas propias
- [ ] OrganizationPolicy: solo owner/admin puede gestionar

**Bugs/Mejoras a documentar:**
- ¿Los permisos son muy restrictivos o muy permisivos?
- ¿Falta algún rol común en empresas?
- ¿La UI deja claro quién puede hacer qué?

---

### **DÍA 8: SUB-FASE 1E - SEGURIDAD (EXCEPTO 2FA)**

#### ✅ Funcionalidades a revisar:

**1. Gestión de Sesiones**
- [ ] Ver sesiones activas
- [ ] Cerrar sesión remota
- [ ] Detectar dispositivo y ubicación
- [ ] Historial de logins

**2. Políticas de Contraseña**
- [ ] Forzar contraseña fuerte
- [ ] Expiración de contraseña (cada X días)
- [ ] Prevenir reutilización de contraseñas
- [ ] Historial de contraseñas

**3. RGPD Compliance**
- [ ] Exportar todos los datos del usuario (portabilidad)
- [ ] Eliminar cuenta y TODOS los datos (derecho al olvido)
- [ ] Consentimientos explícitos guardados
- [ ] Política de retención de datos configurable

**4. Auditoría y Logs**
- [ ] Log de todas las acciones importantes
- [ ] Ver historial de actividad del usuario
- [ ] Filtrar logs por tipo de acción
- [ ] Exportar logs para análisis

**Bugs/Mejoras a documentar:**
- ¿La gestión de sesiones es clara?
- ¿Las políticas de contraseña son muy estrictas?
- ¿Cumple RGPD al 100%?

---

## 🔧 PARTE 2: MEJORAS Y PULIDO (Semana 2)

### **DÍA 9-11: MEJORAS FRONTEND**

**Prioridades:**

1. **UX/UI Consistency**
   - [ ] Revisar que todos los botones tengan el mismo estilo
   - [ ] Verificar que los colores sean consistentes (purple para acciones principales)
   - [ ] Espaciados uniformes
   - [ ] Iconos coherentes

2. **Responsive Design**
   - [ ] Testear en móvil (sidebar, emails, modales)
   - [ ] Testear en tablet
   - [ ] Testear en desktop grande

3. **Performance Frontend**
   - [ ] Optimizar imágenes pesadas
   - [ ] Lazy loading de emails
   - [ ] Reducir re-renders innecesarios de Alpine.js
   - [ ] Eliminar CSS/JS no usado

4. **Accesibilidad**
   - [ ] Navegación por teclado funciona
   - [ ] Focus visible en todos los elementos
   - [ ] Alt text en imágenes
   - [ ] Contraste de colores adecuado

5. **Mejoras Visuales**
   - [ ] Animaciones suaves (transiciones)
   - [ ] Estados de loading claros
   - [ ] Estados vacíos bien diseñados
   - [ ] Mensajes de error amigables

**Bugs conocidos a arreglar:**
- [x] Emails con HTML rompen diseño → RESUELTO hoy
- [ ] (Añadir los que encontremos en el testing)

---

### **DÍA 12-14: MEJORAS BACKEND**

**Prioridades:**

1. **Optimización de Queries**
   - [ ] Revisar N+1 queries (usar eager loading)
   - [ ] Añadir índices donde falten
   - [ ] Optimizar búsquedas con fulltext search
   - [ ] Cachear datos que no cambian frecuentemente

2. **Validación de Datos**
   - [ ] Validar TODOS los inputs del usuario
   - [ ] Sanitizar HTML de emails
   - [ ] Prevenir inyección SQL
   - [ ] Prevenir XSS (ya hecho con iframes)

3. **Jobs y Queues**
   - [ ] Verificar que todos los Commands están registrados en Kernel
   - [ ] Testear que los cron jobs se ejecutan correctamente
   - [ ] Añadir manejo de errores en jobs
   - [ ] Logs cuando falla un job

4. **API Response Consistency**
   - [ ] Todas las respuestas JSON tienen el mismo formato
   - [ ] Códigos HTTP correctos (200, 201, 400, 404, 500)
   - [ ] Mensajes de error consistentes

5. **Testing Automatizado**
   - [ ] Unit tests para modelos
   - [ ] Feature tests para endpoints críticos
   - [ ] Tests de integración para workflows completos

---

## 🔐 PARTE 3: IMPLEMENTAR 2FA (DÍA 15-17)

### ❌ **LO MÁS CRÍTICO QUE FALTA**

**Estado actual:**
- ✅ Campos en BD creados (tabla `two_factor_authentication`)
- ❌ TODA la implementación falta

---

### **DÍA 15: Backend 2FA**

**Tareas:**

1. **Instalar Packages**
   ```bash
   composer require pragmarx/google2fa-laravel
   composer require bacon/bacon-qr-code
   ```

2. **TwoFactorAuthService**
   - [ ] Generar secret key para Google Authenticator
   - [ ] Generar QR code
   - [ ] Verificar código OTP (6 dígitos)
   - [ ] Generar recovery codes (8 códigos de backup)
   - [ ] Enviar código por SMS (Twilio)
   - [ ] Enviar código por Email

3. **Modificar Login Flow**
   - [ ] Después de validar email/password, verificar si tiene 2FA activo
   - [ ] Si tiene 2FA, redirigir a pantalla de verificación
   - [ ] Validar código OTP/SMS/Email
   - [ ] Permitir usar recovery code
   - [ ] Solo dar acceso completo después de 2FA

4. **API Endpoints**
   ```
   POST /2fa/enable          - Activar 2FA
   POST /2fa/verify          - Verificar código setup inicial
   POST /2fa/disable         - Desactivar 2FA
   GET  /2fa/qr-code         - Obtener QR code
   GET  /2fa/recovery-codes  - Ver recovery codes
   POST /2fa/regenerate-recovery - Regenerar recovery codes
   ```

---

### **DÍA 16: Frontend 2FA**

**Tareas:**

1. **Configuración de 2FA (Settings Modal)**
   - [ ] Toggle para activar/desactivar 2FA
   - [ ] Selector de método: App / SMS / Email
   - [ ] Mostrar QR code para Google Authenticator
   - [ ] Input para número de teléfono (si usa SMS)
   - [ ] Preview de recovery codes al activar
   - [ ] Botón para descargar recovery codes

2. **Pantalla de Verificación 2FA (Login)**
   - [ ] Input para código de 6 dígitos
   - [ ] Opción "Usar recovery code"
   - [ ] Botón "Reenviar código" (si es SMS/Email)
   - [ ] Temporizador de expiración
   - [ ] Link "¿Perdiste acceso?"

3. **Gestión de Dispositivos Confiables**
   - [ ] Checkbox "Confiar en este dispositivo por 30 días"
   - [ ] Lista de dispositivos confiables
   - [ ] Revocar confianza de dispositivo

---

### **DÍA 17: Testing y Pulido 2FA**

**Escenarios a testear:**

- [ ] **Setup inicial:** Activar 2FA con Google Authenticator
- [ ] **Login normal:** Email + Password + Código OTP
- [ ] **Login con SMS:** Recibir código por SMS y verificar
- [ ] **Login con Email:** Recibir código por email y verificar
- [ ] **Recovery codes:** Usar recovery code cuando pierdes el móvil
- [ ] **Dispositivo confiable:** No pedir 2FA en dispositivo confiado
- [ ] **Desactivar 2FA:** Desactivar requiere verificación actual
- [ ] **Regenerar recovery codes:** Códigos viejos dejan de funcionar

**Bugs a arreglar:**
- [ ] ¿Qué pasa si el código expira?
- [ ] ¿Qué pasa si usas un recovery code ya usado?
- [ ] ¿Se puede hacer bypass de 2FA?

---

## ✅ PARTE 4: TESTING FINAL (DÍA 18-20)

### **Checklist Final del MVP**

**Funcionalidad Core:**
- [ ] Login con email/password funciona
- [ ] Login con 2FA funciona (3 métodos)
- [ ] Sincronización de Gmail funciona
- [ ] Lectura de emails funciona
- [ ] Envío de emails funciona
- [ ] Vera AI genera respuestas sugeridas
- [ ] Sistema de carpetas funciona
- [ ] Búsqueda de emails funciona
- [ ] Filtros y categorías funcionan
- [ ] Workflow states funcionan
- [ ] Templates de respuesta funcionan
- [ ] Emails programados se envían
- [ ] Alertas se generan correctamente
- [ ] Multi-tenancy funciona (organizaciones)
- [ ] Roles y permisos funcionan
- [ ] RGPD compliance (exportar/eliminar datos)

**Performance:**
- [ ] Carga inicial < 3 segundos
- [ ] Búsqueda de emails < 1 segundo
- [ ] Generación de respuesta IA < 5 segundos
- [ ] Sin memory leaks (Alpine.js bien limpiado)

**Seguridad:**
- [ ] No hay vulnerabilidades XSS
- [ ] No hay inyección SQL
- [ ] Contraseñas hasheadas con bcrypt
- [ ] CSRF protection activo
- [ ] 2FA funciona al 100%
- [ ] Sesiones seguras (httpOnly cookies)

**UX/UI:**
- [ ] Responsive en móvil, tablet, desktop
- [ ] Sin errores en consola
- [ ] Navegación intuitiva
- [ ] Estados de loading claros
- [ ] Mensajes de error útiles

---

## 🎯 CRITERIOS DE ÉXITO MVP

El MVP está listo cuando:

1. ✅ **TODAS** las funcionalidades de la Fase 1 funcionan sin bugs críticos
2. ✅ **2FA implementado** y funcionando con los 3 métodos
3. ✅ **Testing completo** sin errores mayores
4. ✅ **Frontend pulido** y responsive
5. ✅ **Backend optimizado** (queries rápidas, sin N+1)
6. ✅ **Seguridad al 100%** (RGPD + 2FA + validaciones)
7. ✅ **UX fluida** (sin confusiones, todo intuitivo)

---

## 📝 ENTREGABLES FINALES

Al terminar la Fase 1.0, tendremos:

1. **Aplicación funcionando al 100%**
2. **Documento de bugs encontrados y resueltos**
3. **Lista de mejoras implementadas**
4. **Testing report completo**
5. **Base para crear los manuales** (siguiente fase)

---

## 🚀 PRÓXIMOS PASOS DESPUÉS DEL MVP

1. Manual de Usuario
2. Manual Técnico Fase 1
3. Manual Global del Sistema
4. Fase 2: [Lo que decidas después]

---

**Estado del proyecto hoy (19/01/2026):**
- ✅ Bug de visualización de emails (HTML escapando) RESUELTO
- ✅ Sidebar de carpetas funcionando
- ⏳ Pendiente: Empezar revisión sistemática desde DÍA 1-2
