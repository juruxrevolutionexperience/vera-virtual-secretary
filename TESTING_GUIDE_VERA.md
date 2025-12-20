# 🧪 GUÍA DE PRUEBAS - VERA RESPUESTAS AUTOMÁTICAS

## ✅ CHECKLIST DE PRUEBAS

### FASE 1: Verificar Configuración

```bash
# 1. Verificar que las migraciones están ejecutadas
./vendor/bin/sail artisan migrate:status | grep vera

# 2. Verificar que Gemini está configurado
grep GEMINI_API_KEY .env

# 3. Verificar tablas en BD
./vendor/bin/sail artisan db:table vera_configs
./vendor/bin/sail artisan db:table vera_email_actions
```

### FASE 2: Conectar Email y Sincronizar

1. **Ir a:** `http://localhost/email-accounts`
2. **Conectar Gmail o Outlook** (si ya está conectado, desconectar y reconectar)
3. **Esperar sincronización inicial** (ver logs en terminal)
4. **Verificar en logs:**
   ```bash
   ./vendor/bin/sail logs -f
   # Deberías ver:
   # - "🚀 Starting INITIAL sync"
   # - "🧠 Dispatching Vera analysis for user"
   # - "🤖 Procesando email con Vera"
   ```

### FASE 3: Verificar Análisis Automático

```bash
# Ver jobs en cola
./vendor/bin/sail artisan queue:work --once

# Ver logs de Vera
./vendor/bin/sail artisan tinker
>>> \App\Models\Email::where('vera_processed', true)->count();
>>> \App\Models\VeraEmailAction::where('action_type', 'replied')->get();
```

### FASE 4: Probar Onboarding Automático

1. **Si NO tienes VeraConfig:**
   ```bash
   # Borrar configuración existente
   ./vendor/bin/sail artisan tinker
   >>> \App\Models\VeraConfig::where('user_id', 1)->delete();
   ```

2. **Refrescar página del dashboard**
3. **Verificar que modal se abre automáticamente** en 1 segundo
4. **Verificar que muestra "Configurador VERA"**
5. **Click "Configurar Vera Ahora"** → Ir a `/vera/onboarding`
6. **Completar onboarding:**
   - Ver reporte de análisis (HTML bonito)
   - Responder preguntas
   - Guardar configuración

### FASE 5: Verificar Generación de Respuestas

1. **Enviar un email de prueba a tu cuenta conectada**
   - Asunto: "Consulta urgente sobre proyecto"
   - Cuerpo: "Hola, necesito ayuda con el proyecto. ¿Puedes responder pronto?"

2. **Esperar sincronización** (cada 5 minutos por defecto)
   ```bash
   # O forzar sincronización manual
   ./vendor/bin/sail artisan tinker
   >>> $account = \App\Models\EmailAccount::first();
   >>> \App\Jobs\IncrementalSyncEmailsJob::dispatch($account);
   ```

3. **Verificar que se procesó:**
   ```bash
   ./vendor/bin/sail artisan tinker
   >>> $email = \App\Models\Email::latest()->first();
   >>> $email->vera_processed; // debe ser true
   >>> $email->vera_action_required; // puede ser 'reply'
   ```

4. **Verificar respuesta generada:**
   ```bash
   >>> $response = \App\Models\VeraEmailAction::where('email_id', $email->id)->first();
   >>> $response->generated_response; // debe tener texto
   >>> $response->confidence_score; // debe ser > 0
   ```

### FASE 6: Probar UI de Respuestas

1. **Ir a bandeja de entrada:** `http://localhost/emails`
2. **Buscar email con badge morado "🤖 Vera Respondió"**
3. **Click en el badge** → Debe ir a `/vera/responses`
4. **Verificar página de respuestas:**
   - Contador de pendientes
   - Lista de respuestas con email original
   - Respuesta generada por Vera
   - Barra de confianza

5. **Probar acciones:**
   - **✅ Aprobar y Enviar:** Debe enviar email y desaparecer de la lista
   - **✏️ Editar:** Debe habilitar edición, luego "Guardar y Enviar"
   - **❌ Rechazar:** Pide razón opcional, desaparece de lista

### FASE 7: Verificar Email Enviado

1. **Revisar tu bandeja de entrada** (la cuenta desde donde enviaste el email de prueba)
2. **Debes recibir la respuesta de Vera**
3. **Verificar:**
   - Asunto tiene "Re: "
   - Contenido es el generado por Vera (o tu edición)
   - Se envió desde tu cuenta conectada

### FASE 8: Verificar BD Final

```bash
./vendor/bin/sail artisan tinker

# Verificar que la respuesta se marcó como enviada
>>> $response = \App\Models\VeraEmailAction::latest()->first();
>>> $response->response_sent; // debe ser true
>>> $response->response_sent_at; // debe tener fecha
>>> $response->user_feedback; // debe ser 'approved' o 'modified'

# Verificar que el email se marcó como respondido
>>> $email = $response->email;
>>> $email->replied_at; // debe tener fecha
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Problema: No se generan respuestas automáticas

**Verificar:**
```bash
# 1. VeraConfig existe y está habilitada
./vendor/bin/sail artisan tinker
>>> $config = \App\Models\VeraConfig::first();
>>> $config->auto_response_enabled; // debe ser true

# 2. Gemini API funciona
>>> $gemini = new \App\Services\AI\GeminiProvider();
>>> $gemini->testConnection();

# 3. El email necesita respuesta
>>> $email = \App\Models\Email::latest()->first();
>>> $email->vera_action_required; // debe ser 'reply'
>>> $email->priority; // debe ser 'high' o 'urgent'
```

### Problema: Error al enviar email

**Verificar:**
```bash
# 1. Token de acceso válido
./vendor/bin/sail artisan tinker
>>> $account = \App\Models\EmailAccount::first();
>>> $account->token_expires_at; // debe ser futuro
>>> $account->is_token_valid; // debe ser true

# 2. Logs de error
./vendor/bin/sail logs -f | grep ERROR
```

### Problema: Modal de onboarding no se abre

**Verificar:**
```bash
# Flag en cache
./vendor/bin/sail artisan tinker
>>> cache()->get('vera_needs_onboarding_1'); // debe ser true

# Forzar flag
>>> cache()->put('vera_needs_onboarding_1', true, now()->addDays(7));
```

---

## 📊 COMANDOS ÚTILES

```bash
# Ver todas las respuestas pendientes (CLI)
./vendor/bin/sail artisan vera:review-responses --user-id=1

# Auto-aprobar respuestas con alta confianza
./vendor/bin/sail artisan vera:review-responses --auto-approve --confidence-threshold=0.85

# Limpiar cache
./vendor/bin/sail artisan cache:clear

# Ver jobs fallidos
./vendor/bin/sail artisan queue:failed

# Procesar jobs manualmente
./vendor/bin/sail artisan queue:work --once

# Ver análisis de Vera (si existe comando)
./vendor/bin/sail artisan vera:analyze --user-id=1
```

---

## ✅ CHECKLIST FINAL

- [ ] Migraciones ejecutadas
- [ ] Email conectado (Gmail/Outlook)
- [ ] Sincronización inicial completada
- [ ] AnalyzeUserEmailsJob ejecutado
- [ ] VeraConfig creado via onboarding
- [ ] Email de prueba recibido
- [ ] ProcessEmailWithVera ejecutado
- [ ] VeraEmailAction creado con respuesta
- [ ] Badge "Vera Respondió" visible en bandeja
- [ ] Página /vera/responses funcional
- [ ] Respuesta aprobada y enviada
- [ ] Email recibido en destinatario
- [ ] BD actualizada correctamente

---

## 🎯 RESULTADO ESPERADO

Al final de todas las pruebas:

1. ✅ Vera analiza emails automáticamente al recibirlos
2. ✅ Genera respuestas inteligentes con IA (Gemini)
3. ✅ Muestra badge en bandeja cuando hay respuesta pendiente
4. ✅ UI web permite revisar/editar/aprobar/rechazar respuestas
5. ✅ Envía emails automáticamente por API (Gmail/Outlook)
6. ✅ Todo queda registrado en BD con timestamps

**¡VERA FUNCIONA AL 100%!** 🚀🎉
