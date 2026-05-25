<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\User;
use App\Models\VeraConfig;
use App\Models\VeraConversation;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\VeraActivityLog;
use App\Jobs\SynthesizeContactInsights;
use App\Services\GmailService;
use App\Services\TelegramService;
use App\Services\VeraMemoryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ProcessIncomingMessage — Sprint 3
 * Antonio esto funciionaaa
 * Job asíncrono que orquesta el flujo completo de Vera:
 *
 * 1. Carga el contexto del usuario (Cerebro de Vera)
 * 2. Carga el historial de la conversación (últimos 10 mensajes)
 * 3. Llama a ai-service (POST /process) — territorio de Roque
 * 4. Interpreta la respuesta del ai-service
 * 5. Si mode=agenda: consulta Google Calendar y confirma la reunión
 * 6. Si mode=escalation: notifica al humano
 * 7. Envía la respuesta al cliente por el canal correspondiente
 *
 * Mientras ai-service no esté listo (Roque), usa respuesta placeholder.
 */
class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // tries=1: este Job hace acciones no idempotentes (enviar email, crear evento en Calendar).
    // Con tries>1, un fallo después de sendResponse reintentaría y mandaría el email duplicado.
    public int $tries   = 1;
    public int $timeout = 60;

    public function __construct(
        public readonly int     $userId,
        public readonly string  $channel,
        public readonly string  $message,
        public readonly string  $contactIdentifier,
        public readonly ?string $contactName = null,
        public readonly array   $metadata    = []
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::error('ProcessIncomingMessage: usuario no encontrado', ['user_id' => $this->userId]);
            return;
        }

        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config || !$config->isBrainReady()) {
            Log::warning('ProcessIncomingMessage: Cerebro de Vera no configurado', ['user_id' => $user->id]);
            $this->sendResponse($user, 'Lo siento, Vera aún no está configurada para gestionar reuniones.');
            return;
        }

        // ── Log: email recibido ───────────────────────────────────────────────
        VeraActivityLog::record([
            'user_id'       => $user->id,
            'activity_type' => 'message_received',
            'title'         => 'Email recibido de ' . ($this->contactName ?? $this->contactIdentifier),
            'description'   => substr($this->message, 0, 200),
            'metadata'      => [
                'channel'          => $this->channel,
                'contact'          => $this->contactIdentifier,
                'subject'          => $this->metadata['subject'] ?? null,
                'gmail_message_id' => $this->metadata['gmail_message_id'] ?? null,
            ],
            'counts_towards_limit' => false,
            'status'        => 'completed',
        ]);

        // ── 1. Crear o recuperar la solicitud de reunión en curso ─────────────
        $meetingRequest = $this->getOrCreateMeetingRequest($user, $config);

        // ── 2. Detectar confirmación de slot ANTES del LLM ───────────────────
        // Si el usuario responde confirmando uno de los slots propuestos, lo agendamos
        // directamente sin gastar tokens en el LLM.
        if ($meetingRequest->state === 'negotiating') {
            $confirmedSlot = $this->detectConfirmedSlot($meetingRequest);
            if ($confirmedSlot) {
                $response = $this->bookSlot($user, $config, $meetingRequest, $confirmedSlot);
                $this->saveToHistory($meetingRequest, $response);
                if ($response) {
                    $this->sendResponse($user, $response, $config);
                }
                $this->dispatchSynthesis($meetingRequest);
                return;
            }
        }

        // ── 3. Cargar historial de la conversación ────────────────────────────
        $history = $this->loadConversationHistory($meetingRequest);

        // ── 3.5 Verificar slot solicitado antes de llamar al LLM ─────────────
        // Si el cliente pidió una hora concreta, comprobamos en Calendar si está libre
        // para que el LLM pueda decir "a las 12:00 no hay disponibilidad" en lugar de
        // ignorar la petición y listar huecos sin contexto.
        $requestedTime          = $this->extractRequestedTime($this->message);
        $requestedSlotAvailable = false;
        if ($requestedTime && $config->hasCalendarConnected()) {
            $requestedSlotAvailable = $this->checkRequestedSlotAvailable($user, $config, $requestedTime);
        }

        // ── 4. Llamar a ai-service ────────────────────────────────────────────
        $aiResponse = $this->callAiService($user, $config, $history, $meetingRequest, $requestedTime, $requestedSlotAvailable);

        // ── 5. Interpretar respuesta ──────────────────────────────────────────
        $mode      = $aiResponse['mode']      ?? 'respuesta';
        $response  = $aiResponse['response']  ?? null;
        $escalate  = $aiResponse['escalate']  ?? false;
        $slots     = $aiResponse['suggested_slots'] ?? [];

        // ── 5.1 Modo irrelevante — no responder y salir ───────────────────────
        // El email queda como no leído en Gmail (no se llamó a markProcessed),
        // de modo que el dueño lo verá en su bandeja como pendiente.
        if ($mode === 'irrelevante') {
            Log::info('📭 Mensaje irrelevante — Vera no responde', [
                'user_id' => $user->id,
                'contact' => $this->contactIdentifier,
                'channel' => $this->channel,
            ]);

            VeraActivityLog::record([
                'user_id'              => $user->id,
                'activity_type'        => 'message_skipped',
                'title'                => 'Mensaje no procesado — ' . ($this->contactName ?? $this->contactIdentifier),
                'description'          => 'Vera detectó que el mensaje no requería respuesta y lo dejó sin leer.',
                'metadata'             => ['channel' => $this->channel, 'contact' => $this->contactIdentifier],
                'counts_towards_limit' => false,
                'status'               => 'skipped',
            ]);

            return;
        }

        // ── 6. Actuar según el modo ───────────────────────────────────────────

        if ($escalate) {
            $this->handleEscalation($user, $meetingRequest, $aiResponse);
            $this->sendResponse($user, $response ?? $config->unknown_response ?? 'Déjame consultarlo y te respondo en breve.', $config);
            $this->dispatchSynthesis($meetingRequest);
            return;
        }

        // Si el slot pedido está libre → reservarlo y confirmar con fecha real (no confiar en el LLM para la fecha).
        if ($requestedSlotAvailable && $requestedTime) {
            $confirmationResponse = null;
            $slotStart = $this->resolveRequestedSlotStart($requestedTime);
            Log::info('🗓 Slot resuelto para booking directo', [
                'requested_time' => $requestedTime,
                'slot_start'     => $slotStart?->toDateTimeString(),
                'message_preview'=> substr($this->message, 0, 100),
            ]);
            if ($config->hasCalendarConnected() && $slotStart) {
                $this->bookConfirmedRequestedSlot($user, $config, $meetingRequest, $requestedTime);
                $label = $slotStart->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i');
                $confirmationResponse = $this->generateConfirmationText($config, $label);
            }
            $this->updateMeetingRequestState($meetingRequest, $mode, $aiResponse);
            $this->saveToHistory($meetingRequest, $confirmationResponse ?? $response);
            $this->sendResponse($user, $confirmationResponse ?? $response, $config);
            $this->dispatchSynthesis($meetingRequest);
            Log::info('✅ Slot directo reservado', [
                'user_id'    => $user->id,
                'slot_start' => $slotStart?->toDateTimeString(),
            ]);
            return;
        }

        if (\in_array($mode, ['agenda', 'transicion']) && !empty($slots)) {
            $this->handleAgendaMode($user, $config, $meetingRequest, $slots);
        }

        // Cuando mode=agenda o transicion, consultar Calendar y añadir slots reales.
        // El LLM aporta el texto conversacional (p.ej. "las 19h están fuera de horario"),
        // pero los horarios concretos los pone siempre el Calendar — nunca el LLM.
        if (\in_array($mode, ['agenda', 'transicion']) && $config->hasCalendarConnected()) {
            $preferredDate = $this->extractPreferredDate($this->message);
            // Máximo 5 slots: suficiente para el cliente sin abrumarlo.
            // Con día preferido buscamos 2 días; sin él, los próximos 5 días.
            // Si el cliente dice "esta semana", limitamos hasta el domingo actual (incl.)
            // para no colar slots de la semana siguiente.
            $daysAhead = $preferredDate ? 2 : 5;
            if (!$preferredDate && $this->messageMentionsThisWeek($this->message)) {
                // getAvailableSlots hace start + daysAhead, así que daysAhead = diffInDays
                // exacto (sin +1) — con hoy miércoles llega justo hasta domingo.
                $daysAhead = max(1, (int) now()->diffInDays(now()->endOfWeek(), false));
            }
            [$calendarText, $calendarSlots] = $this->buildAvailabilityResponse($user, $config, $preferredDate, $daysAhead, 5);
            // Limpiar respuesta del LLM: eliminar slots falsos, firma y frases de cierre
            // que el LLM añade aunque se le diga que no lo haga.
            // También eliminamos cualquier frase introductoria de huecos del LLM
            // (ej. "Aquí tienes los huecos disponibles:") para evitar duplicarla con
            // el bloque de slots reales que Laravel añade justo después.
            if ($response) {
                // Frases introductorias del LLM que Laravel ya duplica con los slots reales.
                $response = preg_replace(
                    '/[,]?\s*(tengo disponibilidad en:|aquí tienes los huecos disponibles:|estamos disponibles los|tenemos disponibilidad los|le ofrecemos disponibilidad|podemos atenderle los)[\s\S]*/i',
                    '',
                    $response
                );
                // Bullets falsos del LLM
                $response = preg_replace('/\n*(•\s+.+(\n|$))+/i', '', $response);
                // Firmas que el LLM mete dentro del cuerpo (las añade GmailService al final)
                $response = preg_replace('/\n+--?\s*\n?.+$/m', '', $response);
                // Cierres tipo "¿cuál te/le viene mejor?" — duplicarían el cierre que añade Laravel
                $response = preg_replace('/\n*¿cuál (de estas opciones )?(te|le) viene mejor\?/i', '', $response);
                $response = trim($response);
            }
            // Pegar explicación del LLM (si la hay) + slots reales del Calendar
            $response = ($response ? $response . "\n\n" : '') . $calendarText;
            // Guardar slots en contexto para detectar confirmación en el siguiente mensaje
            if (!empty($calendarSlots)) {
                $ctx = $meetingRequest->context ?? [];
                $ctx['calendar_slots'] = array_map(
                    fn ($s) => ['start' => $s['start']->toIso8601String(), 'end' => $s['end']->toIso8601String(), 'label' => $s['label']],
                    $calendarSlots
                );
                $meetingRequest->update(['context' => $ctx]);
            }
        }

        // ── 7. Actualizar estado de la solicitud ──────────────────────────────
        $this->updateMeetingRequestState($meetingRequest, $mode, $aiResponse);

        // ── 8. Guardar mensaje en historial ───────────────────────────────────
        $this->saveToHistory($meetingRequest, $response);

        // ── 9. Enviar respuesta al cliente ────────────────────────────────────
        if ($response) {
            $this->sendResponse($user, $response, $config);

            // Registrar como "respuesta enviada". En agenda/transicion no se
            // registra aquí porque ya quedan como slot_proposed en
            // buildAvailabilityResponse() — evita duplicar la actividad.
            if (!\in_array($mode, ['agenda', 'transicion'], true)) {
                VeraActivityLog::record([
                    'user_id'              => $user->id,
                    'activity_type'        => 'response_sent',
                    'title'                => 'Respuesta enviada a ' . ($this->contactName ?? $this->contactIdentifier),
                    'description'          => substr($response, 0, 200),
                    'metadata'             => ['channel' => $this->channel, 'contact' => $this->contactIdentifier],
                    'counts_towards_limit' => true,
                    'status'               => 'completed',
                ]);
            }
        }


        $this->dispatchSynthesis($meetingRequest);

        Log::info('✅ Mensaje procesado', [
            'user_id' => $user->id,
            'channel' => $this->channel,
            'mode'    => $mode,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getOrCreateMeetingRequest(User $user, VeraConfig $config): MeetingRequest
    {
        // Buscar solicitud activa del mismo contacto en este canal
        $existing = MeetingRequest::forUser($user->id)
            ->active()
            ->where('channel', $this->channel)
            ->where('contact_identifier', $this->contactIdentifier)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        // Crear nueva solicitud
        $conversation = VeraConversation::create([
            'user_id'           => $user->id,
            'conversation_type' => 'chat',
            'status'            => 'active',
            'title'             => "Solicitud {$this->channel} de " . ($this->contactName ?? $this->contactIdentifier),
        ]);

        return MeetingRequest::create([
            'user_id'            => $user->id,
            'channel'            => $this->channel,
            'contact_identifier' => $this->contactIdentifier,
            'state'              => 'new',
            'context'            => ['original_message' => $this->message],
            'conversation_id'    => $conversation->id,
        ]);
    }

    private function loadConversationHistory(MeetingRequest $request): array
    {
        if (!$request->conversation_id) {
            return [];
        }

        return $request->conversation
            ?->messages()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray() ?? [];
    }

    /**
     * Carga el perfil del cliente final (paciente, lead, etc.) si existe en la tabla
     * de contactos del dueño. Devuelve null si no hay nombre ni insights.
     *
     * Para email: busca por email. Si no existe, devuelve null (el Contact de email
     * se crea en GmailService al procesar el mensaje entrante).
     *
     * Para Telegram: find-or-create por telegram_id. Así Vera acumula memoria desde
     * el primer mensaje, aunque el dueño no haya creado el contacto manualmente.
     */
    private function getCustomerProfile(User $user): ?array
    {
        $contact = match ($this->channel) {
            'email'    => $this->findContactByEmail($user),
            'telegram' => $this->findOrCreateTelegramContact($user),
            default    => null,
        };

        if (!$contact) {
            return null;
        }

        $insights = $contact->metadata['vera_insights'] ?? null;

        if (!$insights && !$contact->name) {
            return null;
        }

        return [
            'name'                     => $contact->name,
            'preferred_times'          => $insights['preferred_times']          ?? null,
            'communication_style'      => $insights['communication_style']      ?? null,
            'past_topics'              => $insights['past_topics']              ?? null,
            'last_interaction_summary' => $insights['last_interaction_summary'] ?? null,
            'quirks'                   => $insights['quirks']                   ?? null,
        ];
    }

    private function findContactByEmail(User $user): ?Contact
    {
        // Gmail entrega el remitente como "Nombre <email@dominio>"; extraer solo el email.
        $email = preg_match('/<(.+?)>/', $this->contactIdentifier, $m)
            ? strtolower(trim($m[1]))
            : strtolower(trim($this->contactIdentifier));

        return Contact::where('user_id', $user->id)
            ->where('email', $email)
            ->first();
    }

    private function findOrCreateTelegramContact(User $user): ?Contact
    {
        $telegramId = $this->contactIdentifier; // "@username" o "chat_id" numérico

        $contact = Contact::where('user_id', $user->id)
            ->where('telegram_id', $telegramId)
            ->first();

        if (!$contact) {
            $contact = Contact::create([
                'user_id'          => $user->id,
                'email'            => "telegram_{$telegramId}@vera.internal",
                'telegram_id'      => $telegramId,
                'name'             => $this->contactName,
                'first_contact_at' => now(),
                'last_contact_at'  => now(),
            ]);
        } else {
            $contact->touch('last_contact_at');
            // Actualizar nombre si Telegram nos lo da y aún no lo teníamos
            if ($this->contactName && !$contact->name) {
                $contact->update(['name' => $this->contactName]);
            }
        }

        return $contact;
    }

    /**
     * Llama al microservicio Python de IA (ai-service de Roque).
     * Mientras no esté disponible, devuelve respuesta placeholder.
     */
    private function callAiService(User $user, VeraConfig $config, array $history, MeetingRequest $meetingRequest, ?string $requestedTime = null, bool $requestedSlotAvailable = false): array
    {
        $aiServiceUrl = config('services.ai_service.url');

        // Si ai-service no está configurado, respuesta placeholder
        if (!$aiServiceUrl) {
            return $this->placeholderResponse($config);
        }

        try {
            $response = Http::timeout(30)->withHeaders(['X-Service-Token' => config('services.ai_service.secret')])->post("{$aiServiceUrl}/process", [
                'user_id'                  => (string) $user->id,
                'message'                  => $this->message,
                'channel'                  => $this->channel,
                'history'                  => $history,
                'requested_time'           => $requestedTime,
                'requested_slot_available' => $requestedSlotAvailable,
                'meeting_state'            => $meetingRequest->state,
                'shown_slots'              => array_column($meetingRequest->context['calendar_slots'] ?? [], 'label'),
                'customer_profile'         => $this->getCustomerProfile($user),
                'brain'                    => [
                    'business_name'       => $config->business_name,
                    'owner_name'          => $config->owner_name,
                    'tone'                => $config->tone,
                    'working_hours'       => $config->working_hours,
                    'meeting_duration'    => $config->meeting_duration,
                    'buffer_minutes'      => $config->buffer_minutes,
                    'unknown_response'    => $config->unknown_response,
                    'escalation_triggers' => $config->escalation_triggers ?? [],
                    'mode'                => $config->mode,
                    'vera_signature'      => $config->vera_signature,
                    // Personalidad rica (Fase A) — opcionales, null si no están configurados
                    'personality_traits'  => $config->personality_traits,
                    'years_at_business'   => $config->years_at_business,
                    'pov_about_business'  => $config->pov_about_business,
                    'refers_to_owner_as'  => $config->refers_to_owner_as,
                ],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ai-service respondió con error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('ai-service no disponible', ['error' => $e->getMessage()]);
        }

        return $this->placeholderResponse($config);
    }

    /**
     * Respuesta placeholder mientras ai-service (Roque) no está listo.
     */
    /**
     * Respuesta placeholder mientras ai-service (Roque) no está listo.
     * Devuelve mode:'agenda' con slots vacíos para que buildAvailabilityResponse()
     * consulte el Calendar real y proponga huecos disponibles (B5).
     */
    private function placeholderResponse(VeraConfig $config): array
    {
        $greeting = $config->business_name
            ? "Hola, soy Vera, la asistente de {$config->business_name}."
            : 'Hola, soy Vera.';

        return [
            'response'        => "{$greeting} He recibido tu mensaje y me gustaría proponerte algunos horarios para que podamos hablar:",
            'mode'            => 'agenda',
            'escalate'        => false,
            'confidence'      => 0.0,
            'suggested_slots' => [],
            'tokens_used'     => 0,
        ];
    }

    private function handleAgendaMode(User $user, VeraConfig $config, MeetingRequest $request, array $suggestedSlots): void
    {
        // El ai-service sugirió slots — comprobar que siguen libres en Calendar
        if (!$config->hasCalendarConnected()) {
            return;
        }

        $calendarService = new GoogleCalendarService();

        foreach ($suggestedSlots as $slotDatetime) {
            $start = Carbon::parse($slotDatetime);
            $end   = $start->copy()->addMinutes($config->meeting_duration ?? 45);

            if ($calendarService->checkConflicts($user, $start, $end)) {
                Log::info('Slot libre confirmado en Calendar', ['start' => $start->toDateTimeString()]);
                break; // Usar el primer slot libre
            }
        }
    }

    /**
     * Consulta Google Calendar y construye el mensaje con slots disponibles.
     * Devuelve [string $responseText, array $rawSlots] para poder guardar los slots en contexto.
     */
    /**
     * Detecta si el mensaje se refiere explícitamente a "esta semana" — usado
     * para acotar la búsqueda de slots y no proponer fechas de la semana siguiente.
     */
    private function messageMentionsThisWeek(string $message): bool
    {
        $m = strtolower($this->stripQuotedEmailText($message));
        return str_contains($m, 'esta semana')
            || str_contains($m, 'esta misma semana')
            || str_contains($m, 'para esta semana');
    }

    /**
     * Extrae el día preferido del mensaje del usuario (lunes, martes, etc.)
     * Devuelve una fecha ISO o null si no se detecta nada.
     */
    private function extractPreferredDate(string $message): ?string
    {
        // Limpiar texto citado para no detectar fechas de emails anteriores
        $message = strtolower($this->stripQuotedEmailText($message));
        // Normalizar saltos de línea — los emails parten líneas en medio de frases
        // ("de la\nmañana") y rompen str_contains y los regex
        $message = preg_replace('/\s+/', ' ', trim($message));

        // Fechas relativas — antes que los días de la semana para evitar falsos positivos
        if (str_contains($message, 'pasado mañana') || str_contains($message, 'pasado manana')) {
            return now()->addDays(2)->toDateString();
        }
        // "mañana" solo es "tomorrow" si NO va precedido de "de la" o "por la"
        // ("de la mañana" / "por la mañana" significa morning, no tomorrow)
        if (str_contains($message, 'mañana') || str_contains($message, 'manana')) {
            $isMorning = str_contains($message, 'de la mañana')
                || str_contains($message, 'de la manana')
                || str_contains($message, 'por la mañana')
                || str_contains($message, 'por la manana');
            if (!$isMorning) {
                return now()->addDay()->toDateString();
            }
        }

        // Fechas concretas PRIMERO — tienen prioridad sobre días de la semana
        // "viernes 8 de mayo" debe devolver el 8 de mayo, no el próximo viernes
        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
        ];

        foreach ($months as $monthName => $monthNum) {
            if (preg_match('/(?:el\s+)?(\d{1,2})\s+de\s+' . $monthName . '(?:\s+de\s+(\d{4}))?/i', $message, $m)) {
                $day  = (int) $m[1];
                $year = isset($m[2]) && $m[2] ? (int) $m[2] : now()->year;
                try {
                    $date = Carbon::create($year, $monthNum, $day)->startOfDay();
                    // Si la fecha ya pasó este año y no se especificó año, usar el siguiente
                    if ($date->isPast() && !isset($m[2])) {
                        $date->addYear();
                    }
                    return $date->toDateString();
                } catch (\Exception) {
                    // Fecha inválida (ej. 31 de febrero) — ignorar
                }
            }
        }


        // Días de la semana — solo si no se encontró fecha concreta arriba
        $days = [
            'lunes'     => Carbon::MONDAY,
            'martes'    => Carbon::TUESDAY,
            'miércoles' => Carbon::WEDNESDAY,
            'miercoles' => Carbon::WEDNESDAY,
            'jueves'    => Carbon::THURSDAY,
            'viernes'   => Carbon::FRIDAY,
        ];

        foreach ($days as $word => $dayOfWeek) {
            if (str_contains($message, $word)) {
                $date = now()->next($dayOfWeek);
                if (now()->dayOfWeek === $dayOfWeek) {
                    $date = now()->addWeek()->startOfDay();
                }
                return $date->toDateString();
            }
        }

        // Fechas numéricas: "28/04", "28-04", "28/04/2026"
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{4}))?/', $message, $m)) {
            $day   = (int) $m[1];
            $month = (int) $m[2];
            $year  = isset($m[3]) && $m[3] ? (int) $m[3] : now()->year;
            try {
                $date = Carbon::create($year, $month, $day)->startOfDay();
                if ($date->isPast() && !isset($m[3])) {
                    $date->addYear();
                }
                return $date->toDateString();
            } catch (\Exception) {
                // Fecha inválida — ignorar
            }
        }

        return null;
    }

    /**
     * Elimina el texto citado de los hilos de email para evitar que se extraigan
     * horas o fechas de respuestas anteriores en lugar del mensaje nuevo del cliente.
     * Quita líneas que empiezan por ">" y bloques tras separadores de cita habituales.
     */
    private function stripQuotedEmailText(string $message): string
    {
        // Eliminar líneas que empiezan por ">" (texto citado estándar de email)
        $lines = explode("\n", $message);
        $clean = [];
        foreach ($lines as $line) {
            if (!preg_match('/^\s*>/', $line)) {
                $clean[] = $line;
            }
        }
        $text = implode("\n", $clean);

        // Eliminar texto desde separadores de cita habituales en español e inglés
        $text = preg_replace('/\n[-_]{2,}\s*Mensaje original\s*[-_]{2,}[\s\S]*/i', '', $text);
        $text = preg_replace('/\nEl \w+, \d+ .+ escribi[oó]:[\s\S]*/iu', '', $text);
        $text = preg_replace('/\nOn .+wrote:[\s\S]*/i', '', $text);

        return trim($text);
    }

    /**
     * Extrae la hora concreta que el usuario pidió del mensaje (ej. "a las 12:00", "las 3").
     * Limpia el texto citado antes de buscar para evitar capturar timestamps de emails anteriores.
     */
    private function extractRequestedTime(string $message): ?string
    {
        $message = preg_replace('/\s+/', ' ', $this->stripQuotedEmailText($message));

        // "a las 12:00", "las 12:30" (con minutos)
        if (preg_match('/(?:a\s+)?las?\s+(\d{1,2}:\d{2})/i', $message, $m)) {
            return $m[0];
        }
        // "a las 12", "las 3" (sin minutos)
        if (preg_match('/(?:a\s+)?las?\s+(\d{1,2})(?!\d|:\d)/i', $message, $m)) {
            return $m[0];
        }
        return null;
    }

    /**
     * Convierte el texto de hora pedido ("a las 12", "las 15:30") en un Carbon
     * con la fecha correcta (hoy o el día preferido del mensaje).
     * Devuelve null si no se puede parsear.
     */
    private function resolveRequestedSlotStart(string $requestedTime): ?Carbon
    {
        if (!preg_match('/(\d{1,2})(?::(\d{2}))?/', $requestedTime, $m)) {
            return null;
        }

        $hour   = (int) $m[1];
        $minute = isset($m[2]) ? (int) $m[2] : 0;

        // Detectar AM/PM desde el mensaje original
        $msg  = strtolower(preg_replace('/\s+/', ' ', $this->stripQuotedEmailText($this->message)));
        $isPm = (bool) preg_match('/de la tarde|de la noche|por la tarde|por la noche|\bpm\b/i', $msg);
        $isAm = (bool) preg_match('/de la ma[ñn]ana|por la ma[ñn]ana|\bam\b/i', $msg);

        if ($isPm && $hour < 12) {
            $hour += 12;  // "7 de la tarde" → 19h
        } elseif ($isAm && $hour === 12) {
            $hour = 0;    // "12 de la mañana" → 00h (medianoche)
        } elseif (!$isAm && !$isPm && $hour >= 1 && $hour <= 7) {
            $hour += 12;  // sin contexto, 1-7h es casi siempre tarde en horario laboral
        }

        $preferredDate = $this->extractPreferredDate($this->message);
        $base          = $preferredDate ? Carbon::parse($preferredDate) : now();
        $slotStart     = $base->copy()->setHour($hour)->setMinute($minute)->setSecond(0);

        // Si la hora ya pasó, adelantar un día
        if ($slotStart->isPast()) {
            $slotStart->addDay();
        }

        // Log de depuración para detectar bugs de día equivocado en producción
        Log::info('🕒 resolveRequestedSlotStart', [
            'requested_time'  => $requestedTime,
            'preferred_date'  => $preferredDate,
            'message_preview' => substr($this->message, 0, 200),
            'slot_start'      => $slotStart->toDateTimeString(),
            'slot_dayofweek'  => $slotStart->locale('es')->translatedFormat('l'),
        ]);

        return $slotStart;
    }

    /**
     * Comprueba en Google Calendar si el slot pedido por el usuario está libre.
     * Devuelve true si está libre Y dentro del horario laboral.
     */
    private function checkRequestedSlotAvailable(User $user, VeraConfig $config, string $requestedTime): bool
    {
        try {
            $slotStart = $this->resolveRequestedSlotStart($requestedTime);
            if (!$slotStart) {
                return false;
            }

            if (!$this->isSlotWithinWorkingHours($slotStart, $config)) {
                Log::info('Slot pedido fuera del horario laboral', ['slot' => $slotStart->toDateTimeString()]);
                return false;
            }

            $slotEnd = $slotStart->copy()->addMinutes($config->meeting_duration ?? 45);

            $calendarService = new GoogleCalendarService();
            return $calendarService->checkConflicts($user, $slotStart, $slotEnd);

        } catch (\Exception $e) {
            Log::warning('No se pudo verificar disponibilidad del slot solicitado', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Devuelve true si el slot está dentro del horario laboral configurado para ese día.
     */
    private function isSlotWithinWorkingHours(Carbon $slotStart, VeraConfig $config): bool
    {
        $dayMap = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];
        $dayKey    = $dayMap[$slotStart->dayOfWeek];
        $dayConfig = ($config->working_hours ?? [])[$dayKey] ?? null;

        if (empty($dayConfig) || !($dayConfig['enabled'] ?? false)) {
            return false;
        }

        $dayStart = $slotStart->copy()->setTimeFromTimeString(($dayConfig['start'] ?? '09:00') . ':00');
        $dayEnd   = $slotStart->copy()->setTimeFromTimeString(($dayConfig['end']   ?? '18:00') . ':00');

        return $slotStart->greaterThanOrEqualTo($dayStart) && $slotStart->lessThan($dayEnd);
    }

    /**
     * Crea el Meeting en BD y el evento en Google Calendar cuando el LLM ya confirmó
     * un slot concreto que estaba libre (requested_slot_available=true).
     * Complementa el flujo de bookSlot(), que solo cubre confirmaciones desde listas propuestas.
     */
    private function bookConfirmedRequestedSlot(User $user, VeraConfig $config, MeetingRequest $request, string $requestedTime): bool
    {
        try {
            $slotStart = $this->resolveRequestedSlotStart($requestedTime);
            if (!$slotStart) {
                return false;
            }

            $timezone = ($user->timezone && $user->timezone !== 'UTC') ? $user->timezone : config('app.timezone', 'Europe/Madrid');

            $meeting = Meeting::create([
                'user_id'            => $user->id,
                'contact_name'       => $this->contactName ?? $this->contactIdentifier,
                'contact_email'      => $this->channel === 'email' ? $this->contactIdentifier : null,
                'scheduled_at'       => $slotStart,
                'duration_minutes'   => $config->meeting_duration ?? 45,
                'timezone'           => $timezone,
                'status'             => 'pending',
                'source_channel'     => $this->channel,
                'meeting_request_id' => $request->id,
            ]);

            $calendarService = new GoogleCalendarService();
            $calendarService->createEvent($user, $meeting);

            $request->update(['state' => 'confirmed', 'meeting_id' => $meeting->id]);

            VeraActivityLog::record([
                'user_id'       => $user->id,
                'activity_type' => 'meeting_confirmed',
                'title'         => 'Reunión confirmada con ' . ($this->contactName ?? $this->contactIdentifier),
                'description'   => 'Reunión agendada para ' . $slotStart->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i'),
                'metadata'      => ['channel' => $this->channel, 'contact' => $this->contactIdentifier],
                'counts_towards_limit' => true,
                'status'        => 'completed',
            ]);

            try {
                (new VeraMemoryService())->savePastResponse(
                    $user->id,
                    $this->message,
                    'Reunión confirmada para ' . $slotStart->format('d/m/Y H:i'),
                    'meeting_confirmed'
                );
            } catch (\Exception) {}

            return true;

        } catch (\Exception $e) {
            Log::error('Error al reservar slot confirmado por LLM', [
                'error'          => $e->getMessage(),
                'requested_time' => $requestedTime,
            ]);
            return false;
        }
    }

    private function buildAvailabilityResponse(User $user, VeraConfig $config, ?string $preferredDate = null, int $daysAhead = 5, int $maxSlots = 9): array
    {
        try {
            $calendarService = new GoogleCalendarService();
            $slots = $calendarService->getAvailableSlots($user, daysAhead: $daysAhead, maxSlots: $maxSlots, preferredDate: $preferredDate);

            if (empty($slots)) {
                return ['No tengo disponibilidad en los próximos días. Te confirmo en cuanto se libere algún hueco.', []];
            }

            $slotList = implode("\n", array_map(fn ($s) => "• {$s['label']}", $slots));

            VeraActivityLog::record([
                'user_id'       => $user->id,
                'activity_type' => 'slot_proposed',
                'title'         => 'Slots propuestos a ' . ($this->contactName ?? $this->contactIdentifier),
                'description'   => implode(', ', array_column($slots, 'label')),
                'metadata'      => [
                    'channel' => $this->channel,
                    'contact' => $this->contactIdentifier,
                    'slots'   => array_column($slots, 'label'),
                ],
                'counts_towards_limit' => true,
                'status'        => 'completed',
            ]);

            return ["{$slotList}\n\n{$this->slotClosingPhrase($config)}", $slots];

        } catch (\Exception $e) {
            Log::warning('No se pudo consultar disponibilidad en Calendar', ['error' => $e->getMessage()]);
            return [$config->unknown_response ?? 'Déjame consultarlo y te respondo en breve.', []];
        }
    }

    /**
     * Detecta si el mensaje del usuario confirma uno de los slots propuestos.
     * Dos pasadas: primero busca hora exacta (11:00), luego día+número (lunes 18).
     * Sin dos pasadas, "lunes 18 a las 11:00" matchea el primer slot del lunes en vez del de las 11h.
     */
    private function detectConfirmedSlot(MeetingRequest $request): ?array
    {
        $calendarSlots = $request->context['calendar_slots'] ?? [];
        if (empty($calendarSlots)) {
            return null;
        }

        $message = strtolower(strip_tags($this->message));

        // Normalizar referencias de hora sin minutos para que la pasada 1 las pille.
        // "el de las 10 me viene bien" → enriquecemos con "10:00" buscable.
        // El lookahead (?!\d|:\d) evita pisar "las 10:00" o "las 100".
        if (preg_match_all('/(?:a\s+)?las?\s+(\d{1,2})(?!\d|:\d)/i', $message, $matches)) {
            foreach ($matches[1] as $h) {
                $hour = (int) $h;
                if ($hour >= 0 && $hour <= 23) {
                    $message .= ' ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
                }
            }
        }

        // Pasada 1 — hora exacta: el más específico, tiene prioridad
        foreach ($calendarSlots as $slot) {
            $time = Carbon::parse($slot['start'])->format('H:i');
            if (str_contains($message, $time)) {
                return $slot;
            }
        }

        // Pasada 2 — día + número como fallback (cuando el cliente no mencionó hora)
        foreach ($calendarSlots as $slot) {
            $label      = strtolower($slot['label'] ?? '');
            $labelShort = implode(' ', \array_slice(explode(' ', $label), 0, 2));
            if (\strlen($labelShort) > 3 && str_contains($message, $labelShort)) {
                return $slot;
            }
        }

        // Un solo slot propuesto + respuesta positiva → confirmar ese
        if (\count($calendarSlots) === 1 && preg_match('/perfecto|confirm|vale|sí|si\b|ok\b|acepto|genial|bien|adelante|listo/i', $message)) {
            return $calendarSlots[0];
        }

        return null;
    }

    /**
     * Crea el Meeting en BD, lo agenda en Google Calendar y devuelve el mensaje de confirmación.
     */
    private function bookSlot(User $user, VeraConfig $config, MeetingRequest $request, array $slot): string
    {
        try {
            $start    = Carbon::parse($slot['start']);
            $timezone = ($user->timezone && $user->timezone !== 'UTC') ? $user->timezone : config('app.timezone', 'Europe/Madrid');

            $meeting = Meeting::create([
                'user_id'            => $user->id,
                'contact_name'       => $this->contactName ?? $this->contactIdentifier,
                'contact_email'      => $this->channel === 'email' ? $this->contactIdentifier : null,
                'scheduled_at'       => $start,
                'duration_minutes'   => $config->meeting_duration ?? 45,
                'timezone'           => $timezone,
                'status'             => 'pending',
                'source_channel'     => $this->channel,
                'meeting_request_id' => $request->id,
            ]);

            $calendarService = new GoogleCalendarService();
            $calendarService->createEvent($user, $meeting);

            $request->update(['state' => 'confirmed', 'meeting_id' => $meeting->id]);

            VeraActivityLog::record([
                'user_id'       => $user->id,
                'activity_type' => 'meeting_confirmed',
                'title'         => 'Reunión confirmada con ' . ($this->contactName ?? $this->contactIdentifier),
                'description'   => 'Reunión agendada para ' . ($slot['label'] ?? $start->format('d/m/Y H:i')),
                'metadata'      => ['channel' => $this->channel, 'contact' => $this->contactIdentifier],
                'counts_towards_limit' => true,
                'status'        => 'completed',
            ]);

            $confirmationText = $this->generateConfirmationText($config, $slot['label'] ?? $start->format('d/m/Y H:i'));

            // Guardar respuesta exitosa como past_response para que Vera aprenda el patrón
            try {
                (new VeraMemoryService())->savePastResponse(
                    $user->id,
                    $this->message,
                    $confirmationText,
                    'meeting_confirmed'
                );
            } catch (\Exception) {}

            return $confirmationText;

        } catch (\Exception $e) {
            Log::error('Error al agendar reunión confirmada', ['error' => $e->getMessage(), 'slot' => $slot]);
            return $config->unknown_response ?? 'Ha habido un problema al confirmar la reunión. Por favor, inténtalo de nuevo.';
        }
    }

    /**
     * Genera el texto de confirmación de cita adaptado al tono configurado.
     * NO añade firma — GmailService::replyToEmail() ya la añade al final.
     */
    private function generateConfirmationText(VeraConfig $config, string $slotLabel): string
    {
        $tone = strtolower($config->tone ?? '');

        if (str_contains($tone, 'profesional') || str_contains($tone, 'sofisticado') || str_contains($tone, 'serio') || str_contains($tone, 'institucional')) {
            return "Confirmado el {$slotLabel}.\nLe llega la invitación de Google Calendar enseguida.";
        }
        if (str_contains($tone, 'cercano') || str_contains($tone, 'amigable') || str_contains($tone, 'informal') || str_contains($tone, 'natural') || str_contains($tone, 'coloquial')) {
            return "¡Perfecto, quedamos el {$slotLabel}!\nTe llega la invitación de Google Calendar ahora mismo.";
        }
        return "Confirmado el {$slotLabel}.\nRecibirá la invitación de Google Calendar en breve.";
    }

    /**
     * Devuelve la frase de cierre de la lista de slots según el tono configurado.
     */
    private function slotClosingPhrase(VeraConfig $config): string
    {
        $tone = strtolower($config->tone ?? '');

        if (str_contains($tone, 'cercano') || str_contains($tone, 'amigable') || str_contains($tone, 'informal') || str_contains($tone, 'natural') || str_contains($tone, 'coloquial')) {
            return '¿Cuál te va mejor?';
        }
        // Profesional, formal y resto: trato de usted sin "estimado/a cliente"
        return '¿Cuál le viene mejor?';
    }

    private function handleEscalation(User $user, MeetingRequest $request, array $aiResponse): void
    {
        $request->update([
            'state'             => 'escalated',
            'escalation_reason' => $aiResponse['escalation_reason'] ?? 'trigger detectado',
        ]);

        VeraActivityLog::record([
            'user_id'       => $user->id,
            'activity_type' => 'escalation',
            'title'         => 'Conversación escalada — ' . ($this->contactName ?? $this->contactIdentifier),
            'description'   => $aiResponse['escalation_reason'] ?? 'Trigger detectado',
            'metadata'      => [
                'channel' => $this->channel,
                'contact' => $this->contactIdentifier,
            ],
            'counts_towards_limit' => false,
            'status'        => 'completed',
        ]);

        Log::warning('⚠️ Conversación escalada al humano', [
            'user_id'    => $user->id,
            'channel'    => $this->channel,
            'contact'    => $this->contactIdentifier,
            'reason'     => $aiResponse['escalation_reason'] ?? 'desconocido',
        ]);
    }

    private function updateMeetingRequestState(MeetingRequest $request, string $mode, array $aiResponse): void
    {
        $newState = match ($mode) {
            'agenda'     => 'negotiating',
            'transicion' => 'negotiating',
            default      => $request->state === 'new' ? 'negotiating' : $request->state,
        };

        $context = $request->context ?? [];
        $context['last_ai_mode']       = $mode;
        $context['last_message']       = $this->message;
        $context['suggested_slots']    = $aiResponse['suggested_slots'] ?? [];

        $request->update(['state' => $newState, 'context' => $context]);
    }

    private function saveToHistory(MeetingRequest $request, ?string $response): void
    {
        if (!$request->conversation_id) {
            return;
        }

        $conversation = $request->conversation;
        if (!$conversation) {
            return;
        }

        // Guardar mensaje del usuario — truncado a 2.000 chars para no saturar la BD
        $conversation->addMessage('user', mb_substr(strip_tags($this->message), 0, 2000));

        // Guardar respuesta de Vera
        if ($response) {
            $conversation->addMessage('assistant', $response);
        }
    }

    /**
     * Envía la respuesta al cliente por el canal correspondiente.
     */
    private function sendResponse(User $user, string $response, ?VeraConfig $config = null): void
    {
        if ($this->channel === 'email' && $config?->hasGmailConnected()) {
            try {
                $gmail = new GmailService();

                $originalEmail = [
                    'from'      => $this->contactIdentifier,
                    'subject'   => $this->metadata['subject'] ?? '(sin asunto)',
                    'thread_id' => $this->metadata['thread_id'] ?? null,
                ];

                $signature = $config->vera_signature
                    ?? ($config->owner_name ? "Vera · Asistente de {$config->owner_name}" : 'Vera');

                $gmail->replyToEmail($config, $originalEmail, $response, $signature);

                Log::info('📧 Email enviado por Vera', [
                    'user_id' => $user->id,
                    'to'      => $this->contactIdentifier,
                ]);
                return;
            } catch (\Exception $e) {
                Log::error('❌ Error enviando email', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        if ($this->channel === 'telegram' && $config?->telegram_bot_token) {
            $chatId = $this->metadata['chat_id'] ?? null;

            if (!$chatId) {
                Log::warning('Telegram: respuesta sin chat_id en metadata', [
                    'user_id' => $user->id,
                ]);
                return;
            }

            $telegram = new TelegramService();
            $ok       = $telegram->sendMessage($config->telegram_bot_token, $chatId, $response);

            Log::info($ok ? '✈️ Telegram enviado por Vera' : '⚠️ Telegram envío parcial/fallido', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
            ]);
            return;
        }

        // Otros canales (WhatsApp): pendiente implementación
        Log::info('📤 Respuesta lista para enviar', [
            'user_id' => $user->id,
            'channel' => $this->channel,
            'contact' => $this->contactIdentifier,
            'length'  => strlen($response),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ ProcessIncomingMessage fallido', [
            'user_id' => $this->userId,
            'channel' => $this->channel,
            'contact' => $this->contactIdentifier,
            'error'   => $exception->getMessage(),
        ]);
    }

    /**
     * Despacha SynthesizeContactInsights con 5s de delay para que el historial
     * esté guardado antes de que el job de síntesis lo lea.
     * Solo se despacha si la conversación tiene un ID válido.
     */
    private function dispatchSynthesis(MeetingRequest $meetingRequest): void
    {
        if (!$meetingRequest->conversation_id) {
            return;
        }

        SynthesizeContactInsights::dispatch(
            userId:            $this->userId,
            channel:           $this->channel,
            contactIdentifier: $this->contactIdentifier,
            conversationId:    $meetingRequest->conversation_id,
        )->delay(now()->addSeconds(5));
    }
}
