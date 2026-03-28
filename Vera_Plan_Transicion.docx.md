

| PLAN DE TRANSICION Proyecto Vera — Del email al agendado Que eliminar, conservar, adaptar y construir |
| :---: |

| Objetivo: Transformar el proyecto actual (secretaria de email) en el nuevo Vera (secretaria de agenda pura). Este documento cruza la realidad del codigo con el nuevo enfoque del Plan Maestro. |
| :---- |

| ELIMINAR | CONSERVAR | ADAPTAR | CREAR |
| :---: | :---: | :---: | :---: |

Syntra AI  |  Uso interno  |  2025

# **1\. Resumen ejecutivo**

| El proyecto actual vs el nuevo Vera La brecha que hay que cerrar en 8 semanas. |
| :---- |

| Proyecto actual (parajose.md) | Nuevo Vera (Plan Maestro) |
| :---- | :---- |
| Secretaria de EMAIL — descarga, analiza y responde emails | Secretaria de AGENDA — convierte mensajes en reuniones confirmadas |
| Canal unico: Gmail \+ Outlook | Canales: WhatsApp, Telegram, Gmail,outlook |
| RAG con MySQL FULLTEXT (sin embeddings reales) | RAG con embeddings semanticos reales (ChromaDB/Pinecone) |
| Google Calendar: ruta en UI pero sin backend | Google Calendar: core del producto, obligatorio |
| LLM: Groq (llama-3.3-70b) \+ Gemini como fallback | LLM: Groq/Gemini conservados, nuevo system prompt de agenda |
| 50,000+ lineas PHP, 87 migraciones, 47 modelos | \~15,000 lineas objetivo, 10-12 modelos core |
| Sin Python, todo PHP | ai-service/ en Python (FastAPI) — territorio de Roque |
| Billing Stripe por emails/mes | Billing Stripe por agentes/mes (adaptar limites) |

| Datos clave del analisis: El proyecto tiene \~50,000 LOC. Se estima que el 24% es eliminable directamente (email puro). El 40% adicional necesita adaptacion o reescritura. Solo el 36% se conserva directamente. |
| :---- |

# **2\. Que hay que ELIMINAR**

Todo esto sale del proyecto. No se comenta, no se mueve a legacy/, se borra. Son modulos que no tienen ninguna relacion con convertir mensajes en reuniones.

## **2.1 Jobs — Fuera todos**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| ProcessEmailWithVera | **ELIMINAR** | Procesa emails con IA. No existe email en el nuevo Vera. | **TU** |
| SyncEmailAccount | **ELIMINAR** | Sincroniza cuentas Gmail/Outlook. Ya no gestionamos email. | **TU** |
| IncrementalSyncEmailsJob | **ELIMINAR** | Sync incremental de emails. No aplica. | **TU** |
| InitialSyncEmailsJob | **ELIMINAR** | Sync inicial de bandeja. No aplica. | **TU** |
| AnalyzeUserEmailsJob | **ELIMINAR** | Analiza emails con LLM. No aplica. | **TU** |
| RefreshEmailAccountToken | **ELIMINAR** | Refresca tokens OAuth de email. Solo queda Calendar OAuth. | **TU** |

## **2.2 Servicios de email — Fuera todos**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| GmailSyncService | **ELIMINAR** | Descarga emails de Gmail. El nuevo Vera usa Gmail solo para enviar respuestas de agenda. | **TU** |
| OutlookSyncService | **ELIMINAR** | Descarga emails de Outlook. Canal eliminado. | **TU** |
| VeraService | **ELIMINAR** | Orquestador principal del flujo de email. Reemplazado por VeraAgendaService. | **TU** |
| VeraResponseGenerator | **ELIMINAR** | Genera respuestas de email. El system prompt de email no sirve. | **TU** |
| AutoResponderService | **ELIMINAR** | Auto-respuestas de email configurables. No aplica. | **TU** |
| EscalationService | **ELIMINAR** | Escalado de emails urgentes. La logica de escalado se rehace para agenda. | **TU** |
| FollowUpService | **ELIMINAR** | Follow-ups de email. No aplica al flujo de agenda. | **TU** |
| ScheduledEmailService | **ELIMINAR** | Emails programados. No aplica. | **TU** |
| EmailTemplateService | **ELIMINAR** | Plantillas de email. No aplica. | **TU** |
| EmailCategorizationService | **ELIMINAR** | Categoriza emails entrantes. No aplica. | **TU** |

## **2.3 Modelos Eloquent — Fuera todos los de email**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| Email.php | **ELIMINAR** | Modelo principal de email. No existe email en nuevo Vera. | **TU** |
| EmailAccount.php | **ELIMINAR** | Cuenta Gmail/Outlook conectada. Canal eliminado. | **TU** |
| EmailCategory.php | **ELIMINAR** | Categorias de email. No aplica. | **TU** |
| EmailFolder.php | **ELIMINAR** | Carpetas de email. No aplica. | **TU** |
| EmailAttachment.php | **ELIMINAR** | Adjuntos de email. No aplica. | **TU** |
| EmailRule.php | **ELIMINAR** | Reglas de procesamiento de email. No aplica. | **TU** |
| EmailTemplate.php | **ELIMINAR** | Plantillas de respuesta. No aplica. | **TU** |
| AutoResponder.php | **ELIMINAR** | Auto-responder configurado. No aplica. | **TU** |
| ScheduledEmail.php | **ELIMINAR** | Emails programados. No aplica. | **TU** |
| EscalationRule.php | **ELIMINAR** | Reglas de escalado de email. No aplica. | **TU** |
| FollowUpReminder.php | **ELIMINAR** | Recordatorios de follow-up email. No aplica. | **TU** |

## **2.4 Migraciones — Borrar en este orden exacto**

| Atencion: El orden importa por las foreign keys. Borrar primero las tablas hijo antes que las padre. Crear una migracion maestra que elimine todo es mas seguro que borrar archivos individuales. |
| :---- |

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| email\_attachments | **ELIMINAR** | Primero: tabla hijo sin dependencias | **TU** |
| email\_rules | **ELIMINAR** | Primero: tabla hijo sin dependencias | **TU** |
| email\_templates | **ELIMINAR** | Sin dependencias externas | **TU** |
| auto\_responders | **ELIMINAR** | Sin dependencias externas | **TU** |
| scheduled\_emails | **ELIMINAR** | Sin dependencias externas | **TU** |
| escalation\_rules | **ELIMINAR** | Sin dependencias externas | **TU** |
| follow\_up\_reminders | **ELIMINAR** | Sin dependencias externas | **TU** |
| email\_categories | **ELIMINAR** | Antes que emails | **TU** |
| email\_folders | **ELIMINAR** | Antes que emails | **TU** |
| emails | **ELIMINAR** | Despues de borrar sus tablas hijo | **TU** |
| email\_accounts | **ELIMINAR** | Al final: referenciada por emails | **TU** |

## **2.5 Controladores — Fuera los de email**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| EmailController | **ELIMINAR** | CRUD de emails. No aplica. | **TU** |
| EmailAccountController | **ELIMINAR** | Gestion de cuentas email. No aplica. | **TU** |
| EmailSyncController | **ELIMINAR** | Trigger de sincronizacion. No aplica. | **TU** |
| AutoResponderController | **ELIMINAR** | UI de auto-responders. No aplica. | **TU** |
| EmailTemplateController | **ELIMINAR** | UI de plantillas. No aplica. | **TU** |
| EmailCategoryController | **ELIMINAR** | UI de categorias. No aplica. | **TU** |

## **2.6 Frontend React — Reescritura completa**

| Nota importante: Todo el frontend de email (bandeja de entrada, carpetas, categorias, reglas) hay que reescribirlo. No hay atajo. El nuevo frontend es dashboard de reuniones \+ configuracion de Vera. Es una reescritura esperada. |  |  |  |
| :---- | ----- | :---- | ----- |
| **Archivo / Modulo** | **Accion** | **Detalle** | **Responsable** |
| resources/js/Pages/Email/\* | **ELIMINAR** | Todas las paginas de email: bandeja, detalle, carpetas | **TU** |
| resources/js/Components/Email/\* | **ELIMINAR** | Componentes de lista de emails, preview, etc. | **TU** |
| resources/js/Pages/Calendar/\* | **ADAPTAR** | Existe la ruta pero no tiene backend. Base para el nuevo dashboard. | **TU** |

# **3\. Que hay que CONSERVAR**

Estas piezas se quedan exactamente como estan. No se tocan, no se adaptan. Son el nucleo reutilizable que justifica no empezar de cero.

## **3.1 Infraestructura base — Intocable**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| Auth (Breeze \+ 2FA) | **CONSERVAR** | Login, registro, 2FA con Google Authenticator. Solido, funciona. \~2000 LOC. | **TU** |
| Spatie Permissions | **CONSERVAR** | Sistema de roles y permisos. Se reutiliza para admin/usuario. | **TU** |
| Laravel Horizon | **CONSERVAR** | Gestion de colas con Redis. El nuevo Vera lo necesita para procesar mensajes async. | **TU** |
| Docker Compose | **CONSERVAR** | MySQL \+ PHP \+ Redis. Se anade el contenedor de ai-service cuando llegue Roque. | **TU** |
| Laravel Cashier (Stripe) | **CONSERVAR** | Billing funciona. Solo hay que cambiar los limites de emails/mes por reuniones/mes. | **TU** |
| VeraTokensConsumed event | **CONSERVAR** | Tracking de uso de tokens. Se reutiliza con el nuevo flujo. | **TU** |

## **3.2 Providers de LLM — El oro del proyecto**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| GroqProvider.php | **CONSERVAR** | Llama a Groq (llama-3.3-70b). Solo cambia el system prompt, el cliente no. | **TU** |
| GeminiProvider.php | **CONSERVAR** | Fallback con Gemini. Se conserva igual. | **TU** |
| AIGatewayService.php | **CONSERVAR** | Enrutador entre Groq y Gemini. Logica reutilizable. | **TU** |
| config/vera.php | **CONSERVAR** | Configuracion central de Vera. Se amplia con campos de agenda. | **TU** |

## **3.3 Sistema RAG base — Se conserva la interfaz, no la implementacion**

| Matiz importante: VeraRAGService.php se conserva como interfaz pero su implementacion interna cambia completamente. Roque reescribe el interior (embeddings reales) manteniendo los mismos metodos publicos para no romper lo que llama a este servicio. |
| :---- |

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| VeraRAGService.php | **CONSERVAR\*** | Interfaz publica conservada. Interior reescrito por Roque con embeddings reales. | **ROQUE** |
| VeraMemoryService.php | **CONSERVAR** | Gestion de memoria de usuario. Reutilizable directamente. | **ROQUE** |
| vera\_memory\_chunks (tabla) | **CONSERVAR** | Estructura de la tabla es correcta. Se anade columna embedding (vector). | **TU/ROQUE** |
| VeraConversation (modelo) | **CONSERVAR** | Historial de conversacion existe. Es exactamente lo que necesita agenda. | **TU** |
| VeraConversationMessage | **CONSERVAR** | Mensajes del historial. Se usa para los ultimos 10 mensajes del prompt. | **TU** |
| VeraConfig (modelo) | **CONSERVAR** | Configuracion por usuario. Tiene casi todos los campos del nuevo cerebro de Vera. | **TU** |

# **4\. Que hay que ADAPTAR**

Estas piezas existen y funcionan pero necesitan modificaciones para encajar con el nuevo enfoque. No se reescriben desde cero, se modifican quirurgicamente.

## **4.1 Modelo User — Limpiar campos de email**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| users (tabla) | **ADAPTAR** | Quitar: email\_accounts\_count, monthly\_email\_limit, email\_processed\_this\_month. Anadir: meeting\_limit\_month, meetings\_this\_month, working\_hours (JSON), default\_meeting\_duration, buffer\_minutes. | **TU** |
| User.php | **ADAPTAR** | Actualizar fillable, casts y relaciones para reflejar los nuevos campos. | **TU** |

## **4.2 VeraConfig — Anadir campos del nuevo cerebro**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| vera\_configs (tabla) | **ADAPTAR** | Anadir: tone (enum), working\_hours (JSON), meeting\_duration (int), buffer\_minutes (int), unknown\_response (text), escalation\_triggers (JSON), mode (enum supervised/autonomous), vera\_signature (string). | **TU** |
| VeraConfig.php | **ADAPTAR** | Actualizar modelo con los nuevos campos del Plan Maestro. | **TU** |

## **4.3 Billing — Cambiar limites**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| Stripe Plans | **ADAPTAR** | Cambiar limite de emails/mes por reuniones/mes. Tier 1: 15-29 EUR, Tier 2: 49-79 EUR, Tier 3: 149+ EUR. | **TU** |
| UsageLimitMiddleware | **ADAPTAR** | Cambiar la logica de limite de emails por limite de reuniones agendadas. | **TU** |
| VeraTokensConsumed | **ADAPTAR** | Anadir tracking de reuniones confirmadas ademas de tokens. | **TU** |

## **4.4 Sistema RAG — Reescritura del interior**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| VeraRAGService.php | **ADAPTAR** | Mantener interfaz publica. Reescribir: findRelevant() con embeddings reales en lugar de MySQL FULLTEXT. Anadir: generateEmbedding(), vectorSearch() con score minimo 0.75. | **ROQUE** |
| vera\_memory\_chunks | **ADAPTAR** | Anadir columna embedding VECTOR(1536) para pgvector o campo JSON si se usa MySQL. Anadir indice vectorial. | **TU/ROQUE** |
| VeraMemoryChunk.php | **ADAPTAR** | Actualizar modelo con campo embedding y nuevo metodo findRelevant() semantico. | **ROQUE** |

## **4.5 System Prompt — Reescritura completa del prompt**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| System prompt actual | **ADAPTAR** | El prompt actual analiza emails. El nuevo prompt define a Vera como secretaria de agenda con los 3 modos: Respuesta, Agenda y Transicion. Reescritura total del contenido, mismo mecanismo de inyeccion de variables. | **ROQUE** |
| VeraResponseGenerator | **ADAPTAR** | Renombrar a VeraAgendaService. Cambiar la logica de generacion de respuesta de email a logica de agenda. | **TU/ROQUE** |

## **4.6 OAuth Google — Anadir scope de Calendar**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| Google OAuth config | **ADAPTAR** | El OAuth actual tiene scope de Gmail. Anadir scope: calendar y calendar.events para poder crear eventos. | **TU** |
| SocialiteController | **ADAPTAR** | Actualizar los scopes solicitados al hacer login con Google. | **TU** |

## **4.7 Docker Compose — Anadir contenedor ai-service**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| docker-compose.yml | **ADAPTAR** | Cuando Roque llegue: anadir servicio ai-service (Python FastAPI en puerto 8001). Anadir variable AI\_SERVICE\_URL al .env. | **TU/ROQUE** |

# **5\. Que hay que CREAR desde cero**

Estas funcionalidades no existen en absoluto en el proyecto actual. Son el nucleo del nuevo Vera y lo que lo diferencia de un gestor de email.

## **5.1 Tu responsabilidad — Laravel**

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| GoogleCalendarService.php | **CREAR** | El core del producto. OAuth con scope calendar. Metodos: getAvailableSlots(), createEvent(), updateEvent(), cancelEvent(), checkConflicts(). Usa google/apiclient que ya esta en composer.json. | **TU** |
| meetings (tabla \+ modelo) | **CREAR** | id, user\_id, contact\_name, contact\_email/phone, datetime, duration, status (pending/confirmed/cancelled), notes, source\_channel, google\_event\_id. | **TU** |
| meeting\_requests (tabla) | **CREAR** | Conversaciones en curso antes de confirmar reunion. id, user\_id, channel, contact\_id, state, context\_json, created\_at. | **TU** |
| availability\_slots (tabla) | **CREAR** | Horarios disponibles configurados por el usuario. Alternativa a depender solo de Google Calendar. | **TU** |
| WhatsAppWebhookController | **CREAR** | Recibe mensajes de WhatsApp Business API (Twilio o Meta directo). Verifica firma. Crea Job en cola. | **TU** |
| TelegramWebhookController | **CREAR** | Recibe updates de Telegram Bot API. Verifica token. Crea Job en cola. | **TU** |
| ProcessIncomingMessage (Job) | **CREAR** | Job asincrono que orquesta el flujo completo: llama a ai-service, gestiona Calendar, envia respuesta. | **TU** |
| MeetingDashboard (React) | **CREAR** | UI del nuevo Vera: reuniones pendientes, confirmadas, canceladas. Configuracion de horario y tono. | **TU** |
| Onboarding (React) | **CREAR** | Los 4 pasos del onboarding: negocio, horario, conocimiento de Vera, conexion de canales. | **TU** |

## **5.2 Responsabilidad de Roque — ai-service/**

| Nota: Todo esto vive en la carpeta ai-service/ que se crea cuando Roque se incorpora. Es Python (FastAPI), completamente separado de Laravel. Laravel lo llama via HTTP POST. |
| :---- |

| Archivo / Modulo | Accion | Detalle | Responsable |
| :---- | ----- | :---- | ----- |
| ai-service/main.py | **CREAR** | Servidor FastAPI. Expone el endpoint POST /process que Laravel llama para cada mensaje entrante. | **ROQUE** |
| ai-service/rag/embeddings.py | **CREAR** | Genera embeddings con text-embedding-3-small (OpenAI) o equivalente. Reemplaza el FULLTEXT actual. | **ROQUE** |
| ai-service/rag/chunker.py | **CREAR** | Divide documentos en chunks de \~500 caracteres con overlap de 50\. Alimenta la BD vectorial. | **ROQUE** |
| ai-service/rag/search.py | **CREAR** | Busqueda semantica con score minimo 0.75. Filtra SIEMPRE por user\_id. Max 3 fragmentos. | **ROQUE** |
| ai-service/llm/prompt\_builder.py | **CREAR** | Construye el system prompt completo: contexto fijo \+ fragmentos RAG \+ historial. Valida que no haya variables vacias. | **ROQUE** |
| ai-service/llm/client.py | **CREAR** | Cliente HTTP para Groq y Gemini desde Python. Gestiona fallback y rate limiting. | **ROQUE** |
| ai-service/modes/detector.py | **CREAR** | Detecta el modo de conversacion: Respuesta, Agenda o Transicion. Logica de deteccion de intenciones. | **ROQUE** |
| ai-service/modes/escalation.py | **CREAR** | Detecta triggers de escalado configurados por el usuario. Para el flujo y notifica. | **ROQUE** |
| ai-service/requirements.txt | **CREAR** | fastapi, uvicorn, openai, httpx, pydantic. Dependencias del servicio. | **ROQUE** |
| ai-service/README.md | **CREAR** | Como arrancar el servicio, variables de entorno necesarias, descripcion del endpoint. | **ROQUE** |

# **6\. El contrato del endpoint**

Esta es la unica pieza que teneis que definir y acordar juntos antes de que Roque empiece a trabajar. Una vez acordado, cada uno trabaja independientemente.

## **Lo que tu envias a ai-service (POST /process)**

| POST http://localhost:8001/process Content-Type: application/json {   "user\_id": "tenant\_123",   "message": "Hola, quiero una reunion el martes",   "channel": "whatsapp",   "history": \[     {"role": "user", "content": "Hola, quiero informacion"},     {"role": "assistant", "content": "Claro, en que puedo ayudarte?"}   \],   "brain": {     "business\_name": "Clinica Dr. Garcia",     "owner\_name": "Carlos Garcia",     "tone": "formal",     "working\_hours": {"lun-vie": "9-18", "sab": null, "dom": null},     "meeting\_duration": 45,     "buffer\_minutes": 15,     "unknown\_response": "Dejame consultarlo y te respondo en breve.",     "escalation\_triggers": \["urgente", "queja", "emergencia"\],     "mode": "autonomous"   } } |
| :---- |

## **Lo que Roque te devuelve**

| {   "response": "Perfecto, tengo disponibilidad el martes a las 10:00 o a las 16:00. Cual prefieres?",   "mode": "agenda",   "escalate": false,   "confidence": 0.89,   "suggested\_slots": \["2025-01-14T10:00:00", "2025-01-14T16:00:00"\],   "tokens\_used": 450 } // Si hay escalado: {   "response": null,   "mode": "escalation",   "escalate": true,   "escalation\_reason": "queja detectada",   "confidence": 0.0,   "tokens\_used": 120 } |
| :---- |

| Regla del contrato: Mientras este contrato se respete, los dos podeis trabajar en paralelo sin interferiros. Tu no entras en ai-service/. Roque no toca las rutas de Laravel ni las integraciones de calendario. |
| :---- |

# **7\. Plan de sprints — 8 semanas**

| Sprint | Tarea | Detalle | Quien |
| ----- | :---- | :---- | ----- |
| **S1** | **Crear rama y limpiar Jobs** | git checkout \-b feature/agenda-only. Borrar los 6 Jobs de email. | **TU** |
| **S1** | **Eliminar Servicios de email** | Borrar los 10 servicios de email listados en seccion 2.2. | **TU** |
| **S1** | **Eliminar Modelos de email** | Borrar los 11 modelos Eloquent de email. | **TU** |
| **S1** | **Eliminar migraciones (orden)** | Borrar migraciones en el orden exacto de seccion 2.4. | **TU** |
| **S1** | **Auditoria post-limpieza** | php artisan migrate:fresh. Verificar que el proyecto arranca sin errores. | **TU** |
| **S2** | **Adaptar tabla users** | Quitar campos email, anadir campos agenda (working\_hours, meeting\_duration, etc.). | **TU** |
| **S2** | **Adaptar VeraConfig** | Anadir los 8 campos nuevos del cerebro de Vera (tone, mode, escalation\_triggers, etc.). | **TU** |
| **S2** | **Crear migraciones de agenda** | meetings, meeting\_requests, availability\_slots. | **TU** |
| **S2** | **Adaptar OAuth Google** | Anadir scopes de Calendar al flujo de login con Google. | **TU** |
| **S2** | **Roque: setup ai-service/** | Crear estructura de carpetas, FastAPI basico, requirements.txt, README. | **ROQUE** |
| **S3** | **Crear GoogleCalendarService** | getAvailableSlots(), createEvent(), updateEvent(), cancelEvent(). | **TU** |
| **S3** | **Crear WhatsApp webhook** | Controller \+ verificacion de firma \+ Job en cola. | **TU** |
| **S3** | **Crear ProcessIncomingMessage** | Job que orquesta: llama ai-service, gestiona Calendar, envia respuesta. | **TU** |
| **S3** | **Roque: embeddings reales** | Reescribir VeraRAGService con text-embedding-3-small. Migrar de FULLTEXT. | **ROQUE** |
| **S3** | **Roque: search.py** | Busqueda semantica con filtro user\_id y score minimo 0.75. | **ROQUE** |
| **S4** | **Roque: prompt\_builder.py** | System prompt completo con los 3 modos. Validacion de variables vacias. | **ROQUE** |
| **S4** | **Roque: detector de modos** | Detectar Respuesta, Agenda o Transicion en cada mensaje. | **ROQUE** |
| **S4** | **Flujo completo end-to-end** | Mensaje WhatsApp \-\> ai-service \-\> Calendar \-\> respuesta. Sin intervencion humana. | **TU/ROQUE** |
| **S4** | **Frontend: dashboard reuniones** | UI nueva: reuniones pendientes, confirmadas, canceladas. | **TU** |
| **S4** | **Onboarding 4 pasos** | Los 4 pasos del Plan Maestro: negocio, horario, conocimiento, canales. | **TU** |

| Criterio de salida semana 4: Vera completa un ciclo end-to-end sin intervencion humana: recibe WhatsApp, consulta disponibilidad real en Google Calendar, propone slot, confirma y crea el evento. |
| :---- |

# **8\. Riesgos criticos a vigilar**

| \# | Riesgo | Impacto | Solucion |
| ----- | :---- | :---- | :---- |
| **R1** | **RAG sin embeddings reales** | Sin semantica, 'reunion martes' no encuentra 'disponible lunes/miercoles'. Vera falla en su trabajo core. | Primera tarea de Roque. Bloquea todo lo demas. No avanzar sin esto. |
| **R2** | **Google Calendar no existe** | Sin Calendar no hay producto. Es el destino de cada conversacion. | Sprint 3 completo dedicado a GoogleCalendarService. |
| **R3** | **Historial conversacional incompleto** | Vera pierde contexto: 'mejor el jueves' sin saber a que reunion se refiere. | Usar VeraConversation \+ VeraConversationMessage que ya existen. |
| **R4** | **87 migraciones con foreign keys** | Borrar en orden incorrecto rompe php artisan migrate:fresh. | Seguir el orden exacto de la seccion 2.4. Hacer fresh en rama separada. |
| **R5** | **Rate limit Groq (28 RPM gratis)** | Con varios usuarios simultaneos, sleep() bloquea el worker de cola. | Pasar a plan de pago de Groq antes del lanzamiento publico. |

Syntra AI  |  Plan de Transicion  |  Confidencial  |  2025