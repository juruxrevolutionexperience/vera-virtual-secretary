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
use App\Events\VeraTokensConsumed;
use App\Jobs\SynthesizeContactInsights;
use App\Services\GmailService;
use App\Services\TelegramService;
use App\Services\VeraMemoryService;
use App\Services\VeraNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

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
        public string           $message,
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

        // Quitar el hilo citado: cuando el cliente RESPONDE, el email arrastra toda la
        // conversación anterior (la respuesta de Vera, firma, etc.). Vera solo debe
        // procesar el mensaje NUEVO — así clasifica bien y no gasta tokens en el hilo.
        $clean = $this->stripQuotedEmailText($this->message);
        if (trim($clean) !== '') {
            $this->message = $clean;
        }

        if (!$config || !$config->isBrainReady()) {
            Log::warning('ProcessIncomingMessage: Cerebro de Vera no configurado', ['user_id' => $user->id]);
            $this->sendResponse($user, 'Lo siento, Vera aún no está configurada para gestionar reuniones.');
            return;
        }

        // ── SEGURIDAD: límites anti-abuso/coste ANTES de tocar el LLM ──────────
        // Protege contra flood de correos (DoS) y gasto descontrolado de tokens.
        // (a) Tope de plan del dueño (monthly_tokens_limit; 0/null = ilimitado, NO bloquea).
        if (!$user->hasTokensAvailable()) {
            Log::warning('Vera: tope de uso del plan alcanzado — mensaje NO procesado', ['user_id' => $user->id]);
            return;
        }
        // (b) Rate-limit por CONTACTO: máx 12 mensajes/hora del mismo remitente (anti-flood/bot).
        $rlContact = 'vera-msg:' . $user->id . ':' . md5((string) $this->contactIdentifier);
        // (c) Rate-limit GLOBAL del negocio: máx 120 mensajes/hora (buzón inundado/comprometido).
        $rlUser = 'vera-msg-user:' . $user->id;
        if (RateLimiter::tooManyAttempts($rlContact, 12) || RateLimiter::tooManyAttempts($rlUser, 120)) {
            Log::warning('Vera: rate-limit alcanzado — mensaje descartado', [
                'user_id' => $user->id,
                'contact' => $this->contactIdentifier,
            ]);
            return;
        }
        RateLimiter::hit($rlContact, 3600);
        RateLimiter::hit($rlUser, 3600);

        // ── Log: email recibido ───────────────────────────────────────────────
        VeraActivityLog::record([
            'user_id'       => $user->id,
            'activity_type' => 'message_received',
            'title'         => 'Email recibido de ' . ($this->contactName ?? $this->contactIdentifier),
            'description'   => mb_substr($this->message, 0, 200),
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

        // ── 3. Cargar historial de la conversación ────────────────────────────
        $history = $this->loadConversationHistory($meetingRequest);

        // NOTA (2026-06-07): Vera ya NO agenda desde el texto. Todas las citas se
        // reservan a través de la web de reservas (el cliente elige el hueco real en
        // el calendario). Por eso no extraemos hora pedida ni reservamos por texto:
        // en modo agenda siempre se manda el ENLACE. Pasamos requested_time=null.
        $requestedTime          = null;
        $requestedSlotAvailable = false;

        // ── 4. Llamar a ai-service ────────────────────────────────────────────
        $aiResponse = $this->callAiService($user, $config, $history, $meetingRequest, $requestedTime, $requestedSlotAvailable);

        // ── 4.1 Registrar consumo de tokens del procesamiento ─────────────────
        // Cubre TODAS las ramas (irrelevante, respuesta, agenda, escalation) porque
        // va antes de bifurcar. Fail-safe: si no hay dato (0), no dispara nada y el
        // flujo sigue igual. El listener RecordTokenUsage está en cola y try/catch.
        $tokensUsed = (int) ($aiResponse['tokens_used'] ?? 0);
        if ($tokensUsed > 0) {
            event(new VeraTokensConsumed(
                userId: $user->id,
                // 'response_generation' es un valor válido del enum action_type.
                // El canal (email/whatsapp/telegram) queda en metadata para distinguir.
                actionType: 'response_generation',
                tokenData: [
                    'prompt_tokens'     => 0,
                    'completion_tokens' => 0,
                    'total_tokens'      => $tokensUsed,
                    'model'             => 'llama-3.3-70b-versatile',
                ],
                metadata: ['channel' => $this->channel, 'contact' => $this->contactIdentifier],
            ));
        }

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

        // NOTA (2026-06-07): Vera ya NO reserva desde el texto (ni por intención del
        // LLM ni por hora pedida). TODAS las citas se reservan en la web de reservas,
        // donde el cliente elige el hueco real. En modo agenda se manda el ENLACE.

        // ── 6. Actuar según el modo ───────────────────────────────────────────

        if ($escalate) {
            $this->handleEscalation($user, $meetingRequest, $aiResponse);
            $this->sendResponse($user, $response ?? $config->unknown_response ?? 'Déjame consultarlo y te respondo en breve.', $config);
            $this->dispatchSynthesis($meetingRequest);
            return;
        }

        // ── Cancelación de cita ────────────────────────────────────────────────
        // Igual que con las reservas, Vera NO cancela desde el texto (sería adivinar
        // cuál cita si el cliente tiene varias). Manda el ENLACE de cancelación: el
        // cliente ve SUS citas y elige cuál anular en la página /cancelar/{token}.
        if ($mode === 'cancelacion') {
            $hasUpcoming = Meeting::where('user_id', $user->id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->where('scheduled_at', '>', now())
                ->where(function ($q) {
                    $email = preg_match('/<(.+?)>/', $this->contactIdentifier, $m)
                        ? strtolower(trim($m[1]))
                        : strtolower(trim($this->contactIdentifier));
                    $q->where('contact_email', $this->contactIdentifier)
                      ->orWhereRaw('LOWER(contact_email) = ?', [$email])
                      ->orWhere('contact_email', 'like', '%<' . $email . '>%');
                })
                ->exists();

            if ($hasUpcoming) {
                $token     = $meetingRequest->cancelTokenIsValid()
                    ? $meetingRequest->cancel_token
                    : $meetingRequest->generateCancelToken();
                $cancelUrl = rtrim(config('app.url'), '/') . '/cancelar/' . $token;
                $intro     = $response ?: 'Claro, puedes cancelar tu cita desde aquí:';
                $response  = trim((string) $intro) . "\n\n" . $cancelUrl;
            } else {
                // No tiene citas activas: lo tratamos con naturalidad (sin enlace de cancelar).
                $response = $response ?: 'No encuentro ninguna cita próxima a tu nombre. Si quieres reservar una nueva, dímelo y te ayudo.';
            }
        }

        if (\in_array($mode, ['agenda', 'transicion']) && !empty($slots)) {
            $this->handleAgendaMode($user, $config, $meetingRequest, $slots);
        }

        // CAPA 3 — En modo agenda/transicion, en vez de recitar slots, Vera manda el
        // ENLACE de reservas: el cliente ve la agenda real y elige su hueco él mismo.
        // (Si el cliente dio una hora concreta reservable, ya se reservó arriba con
        // return — esto solo se alcanza cuando quiere ver opciones o no concreta.)
        if (\in_array($mode, ['agenda', 'transicion']) && $config->hasCalendarConnected()) {
            // Token: reutilizamos el válido si aún existe; si ya se usó o caducó,
            // generamos uno NUEVO. Así, si el cliente vuelve a pedir cita (aunque ya
            // reservara antes), siempre recibe un enlace fresco — no se queda sin él.
            $token = $meetingRequest->bookingTokenIsValid()
                ? $meetingRequest->booking_token
                : $meetingRequest->generateBookingToken();
            $bookingUrl = rtrim(config('app.url'), '/') . '/reservar/' . $token;

            // Limpiar la redacción del LLM: quitar slots inventados, bullets, firmas y
            // cierres tipo "¿cuál te viene mejor?" (los horarios ya no los pone el LLM).
            if ($response) {
                $response = preg_replace(
                    '/[,]?\s*(tengo disponibilidad en:|aquí tienes los huecos disponibles:|estamos disponibles los|tenemos disponibilidad los|le ofrecemos disponibilidad|podemos atenderle los)[\s\S]*/i',
                    '',
                    $response
                );
                $response = preg_replace('/\n*(•\s+.+(\n|$))+/i', '', $response);
                $response = preg_replace('/\n+--?\s*\n?.+$/m', '', $response);
                $response = preg_replace('/\n*¿cuál (de estas opciones )?(te|le) viene mejor\?/i', '', $response);
                $response = trim($response);
            }

            // RED DE SEGURIDAD determinista: si el LLM se despista y AFIRMA que ya
            // reservó/apuntó/agendó la cita (Vera NO reserva desde texto — lo hace el
            // enlace), descartamos su texto y caemos al intro neutral. No depende de la
            // suerte del modelo: garantiza que el cliente nunca lea una falsa confirmación.
            if ($response && preg_match('/\bte\s+(lo\s+|la\s+)?reserv|\bte\s+(he\s+)?apunt|\bte\s+(he\s+)?agend|qued[ao]\s+(reservad|agendad|confirmad)|ya\s+est[áa]\s+(reservad|agendad|confirmad)|\bcita\s+(reservad|agendad|confirmad)|\bhe\s+(reservad|apuntad|agendad)/iu', $response)) {
                Log::info('Falsa confirmación de reserva neutralizada en agenda', ['contact' => $this->contactIdentifier]);
                $response = null; // fuerza el intro neutral de abajo
            }

            // Añadir SOLO la URL: la invitación conversacional ya la redacta Vera (el LLM).
            // GmailService convierte la URL en botón en el email; en texto plano /
            // Telegram va como URL clicable. Fallback mínimo si el LLM no dejó texto.
            if ($bookingUrl) {
                $intro    = $response ?: 'Te dejo mi agenda para que elijas el hueco que mejor te venga:';
                $response = $intro . "\n\n" . $bookingUrl;

                // Marcar cuándo se envió el enlace y resetear el follow-up, para el
                // seguimiento a 24h si el cliente no reserva.
                $meetingRequest->update([
                    'booking_link_sent_at' => now(),
                    'follow_up_sent_at'    => null,
                ]);

                VeraActivityLog::record([
                    'user_id'              => $user->id,
                    'activity_type'        => 'slot_proposed',
                    'title'                => 'Enlace de reserva enviado a ' . ($this->contactName ?? $this->contactIdentifier),
                    'description'          => 'Vera envió el enlace para que el cliente elija su hueco en la agenda real.',
                    'metadata'             => ['channel' => $this->channel, 'contact' => $this->contactIdentifier, 'via' => 'booking_link'],
                    'counts_towards_limit' => true,
                    'status'               => 'completed',
                ]);
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
        $slotStart = $this->resolveRequestedSlotStart($requestedTime);
        return $slotStart ? $this->isSlotFree($user, $config, $slotStart) : false;
    }

    /**
     * Núcleo de disponibilidad sobre un Carbon ya resuelto: dentro del horario
     * laboral Y sin conflictos en Calendar. Lo usan el flujo de regex y el de
     * intención estructurada del LLM.
     */
    private function isSlotFree(User $user, VeraConfig $config, Carbon $slotStart): bool
    {
        try {
            if (!$this->isSlotWithinWorkingHours($slotStart, $config)) {
                Log::info('Slot fuera del horario laboral', ['slot' => $slotStart->toDateTimeString()]);
                return false;
            }
            $slotEnd = $slotStart->copy()->addMinutes($config->meeting_duration ?? 45);
            return (new GoogleCalendarService())->checkConflicts($user, $slotStart, $slotEnd);
        } catch (\Exception $e) {
            Log::warning('No se pudo verificar disponibilidad del slot', ['error' => $e->getMessage()]);
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
        // "00:00" como fin = medianoche (fin del día), no inicio del día.
        $endStr   = $dayConfig['end'] ?? '18:00';
        $dayEnd   = $endStr === '00:00'
            ? $slotStart->copy()->addDay()->startOfDay()
            : $slotStart->copy()->setTimeFromTimeString($endStr . ':00');

        return $slotStart->greaterThanOrEqualTo($dayStart) && $slotStart->lessThan($dayEnd);
    }

    /**
     * Crea el Meeting en BD y el evento en Google Calendar cuando el LLM ya confirmó
     * un slot concreto que estaba libre (requested_slot_available=true).
     * Complementa el flujo de bookSlot(), que solo cubre confirmaciones desde listas propuestas.
     */
    private function bookConfirmedRequestedSlot(User $user, VeraConfig $config, MeetingRequest $request, string $requestedTime): bool
    {
        $slotStart = $this->resolveRequestedSlotStart($requestedTime);
        return $slotStart ? $this->bookSlotAtTime($user, $config, $request, $slotStart) : false;
    }

    /**
     * Construye un Carbon desde la fecha+hora ESTRUCTURADAS que devolvió el LLM
     * (YYYY-MM-DD + HH:MM) en la zona del negocio. Null si no parsea o ya pasó.
     */
    private function resolveIntentSlot(User $user, string $date, string $time): ?Carbon
    {
        try {
            $tz   = ($user->timezone && $user->timezone !== 'UTC') ? $user->timezone : config('app.timezone', 'Europe/Madrid');
            $slot = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", $tz);
            return ($slot && $slot->isFuture()) ? $slot : null;
        } catch (\Throwable $e) {
            Log::warning('resolveIntentSlot no pudo parsear', ['date' => $date, 'time' => $time, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Núcleo de reserva sobre un Carbon ya resuelto: crea el Meeting, el evento en
     * Calendar, marca la solicitud como confirmada y registra la actividad.
     * Lo usan el flujo de regex (bookConfirmedRequestedSlot) y el de intención del LLM.
     */
    private function bookSlotAtTime(User $user, VeraConfig $config, MeetingRequest $request, Carbon $slotStart): bool
    {
        try {
            $timezone = $config->timezone ?: config('app.timezone', 'Europe/Madrid');

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

            (new GoogleCalendarService())->createEvent($user, $meeting);

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
            Log::error('Error al reservar slot', [
                'error' => $e->getMessage(),
                'slot'  => $slotStart->toDateTimeString(),
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
            $timezone = $config->timezone ?: config('app.timezone', 'Europe/Madrid');

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
        // Guardar EN EL CONTEXTO el mensaje que disparó la escalada. La rama de
        // escalación hace return temprano y nunca pasa por updateMeetingRequestState,
        // así que sin esto la Bandeja mostraría el last_message de un mensaje ANTERIOR
        // de la conversación (p.ej. una petición de cita) en lugar de la queja real,
        // y el texto de la incidencia se perdería.
        $context = $request->context ?? [];
        $context['last_message']     = $this->message;
        $context['escalated_message'] = $this->message;
        $context['escalated_at']      = now()->toIso8601String();
        // Guardar el ID del mensaje de Gmail para poder abrir ESE email concreto
        // desde la Bandeja (enlace directo), no un listado del remitente.
        if (!empty($this->metadata['gmail_message_id'])) {
            $context['gmail_message_id'] = $this->metadata['gmail_message_id'];
        }

        $request->update([
            'state'             => 'escalated',
            'escalation_reason' => $aiResponse['escalation_reason'] ?? 'trigger detectado',
            'context'           => $context,
        ]);

        // Avisar al dueño de inmediato (email + in-app). Una queja no puede esperar
        // a que entre al panel por casualidad. No bloquea el flujo si falla.
        try {
            (new VeraNotificationService())->sendEscalationAlert(
                $user,
                $this->contactName ?? $this->contactIdentifier,
                $this->message
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar al dueño de la escalada', ['error' => $e->getMessage()]);
        }

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
