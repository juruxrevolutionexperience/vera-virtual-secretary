<?php

namespace App\Jobs;

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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ProcessIncomingMessage — Sprint 3
 *
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

    public int $tries   = 3;
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

        // ── 1. Crear o recuperar la solicitud de reunión en curso ─────────────
        $meetingRequest = $this->getOrCreateMeetingRequest($user, $config);

        // ── 2. Cargar historial de la conversación ────────────────────────────
        $history = $this->loadConversationHistory($meetingRequest);

        // ── 3. Llamar a ai-service ────────────────────────────────────────────
        $aiResponse = $this->callAiService($user, $config, $history);

        // ── 4. Interpretar respuesta ──────────────────────────────────────────
        $mode      = $aiResponse['mode']      ?? 'respuesta';
        $response  = $aiResponse['response']  ?? null;
        $escalate  = $aiResponse['escalate']  ?? false;
        $slots     = $aiResponse['suggested_slots'] ?? [];

        // ── 5. Actuar según el modo ───────────────────────────────────────────

        if ($escalate) {
            $this->handleEscalation($user, $meetingRequest, $aiResponse);
            $this->sendResponse($user, $response ?? $config->unknown_response ?? 'Déjame consultarlo y te respondo en breve.');
            return;
        }

        if ($mode === 'agenda' && !empty($slots)) {
            $this->handleAgendaMode($user, $config, $meetingRequest, $slots);
        }

        if ($mode === 'agenda' && empty($slots) && $config->hasCalendarConnected()) {
            // El ai-service no devolvió slots — los consultamos nosotros
            $response = $this->buildAvailabilityResponse($user, $config, $response);
        }

        // ── 6. Actualizar estado de la solicitud ──────────────────────────────
        $this->updateMeetingRequestState($meetingRequest, $mode, $aiResponse);

        // ── 7. Guardar mensaje en historial ───────────────────────────────────
        $this->saveToHistory($meetingRequest, $response);

        // ── 8. Enviar respuesta al cliente ────────────────────────────────────
        if ($response) {
            $this->sendResponse($user, $response);
        }

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
     * Llama al microservicio Python de IA (ai-service de Roque).
     * Mientras no esté disponible, devuelve respuesta placeholder.
     */
    private function callAiService(User $user, VeraConfig $config, array $history): array
    {
        $aiServiceUrl = config('services.ai_service.url');

        // Si ai-service no está configurado, respuesta placeholder
        if (!$aiServiceUrl) {
            return $this->placeholderResponse($config);
        }

        try {
            $response = Http::timeout(30)->post("{$aiServiceUrl}/process", [
                'user_id' => (string) $user->id,
                'message' => $this->message,
                'channel' => $this->channel,
                'history' => $history,
                'brain'   => [
                    'business_name'       => $config->business_name,
                    'owner_name'          => $config->owner_name,
                    'tone'                => $config->tone,
                    'working_hours'       => $config->working_hours,
                    'meeting_duration'    => $config->meeting_duration,
                    'buffer_minutes'      => $config->buffer_minutes,
                    'unknown_response'    => $config->unknown_response,
                    'escalation_triggers' => $config->escalation_triggers ?? [],
                    'mode'                => $config->mode,
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
    private function placeholderResponse(VeraConfig $config): array
    {
        return [
            'response'        => $config->unknown_response ?? 'Hola, soy Vera. En breve me pondré en contacto contigo para gestionar tu solicitud.',
            'mode'            => 'respuesta',
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

    private function buildAvailabilityResponse(User $user, VeraConfig $config, ?string $baseResponse): string
    {
        try {
            $calendarService = new GoogleCalendarService();
            $slots = $calendarService->getAvailableSlots($user, daysAhead: 5, maxSlots: 3);

            if (empty($slots)) {
                return ($baseResponse ?? '') . ' No tengo disponibilidad en los próximos días. Te confirmo en cuanto se libere algún hueco.';
            }

            $slotList = implode("\n", array_map(fn ($s) => "• {$s['label']}", $slots));
            return ($baseResponse ? $baseResponse . "\n\n" : '') . "Tengo disponibilidad en:\n{$slotList}\n\n¿Cuál te viene mejor?";

        } catch (\Exception $e) {
            Log::warning('No se pudo consultar disponibilidad en Calendar', ['error' => $e->getMessage()]);
            return $baseResponse ?? ($config->unknown_response ?? 'Déjame consultarlo y te respondo en breve.');
        }
    }

    private function handleEscalation(User $user, MeetingRequest $request, array $aiResponse): void
    {
        $request->update([
            'state'             => 'escalated',
            'escalation_reason' => $aiResponse['escalation_reason'] ?? 'trigger detectado',
        ]);

        // TODO Sprint 4: enviar notificación push/email al dueño del negocio
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

        // Guardar mensaje del usuario
        $conversation->addMessage('user', $this->message);

        // Guardar respuesta de Vera
        if ($response) {
            $conversation->addMessage('assistant', $response);
        }
    }

    /**
     * Envía la respuesta al cliente por el canal correspondiente.
     * TODO Sprint 4: implementar envío real por WhatsApp API y Telegram Bot API.
     */
    private function sendResponse(User $user, string $response): void
    {
        Log::info('📤 Respuesta lista para enviar', [
            'user_id' => $user->id,
            'channel' => $this->channel,
            'contact' => $this->contactIdentifier,
            'length'  => strlen($response),
        ]);

        // TODO Sprint 4: envío real
        // match ($this->channel) {
        //     'whatsapp' => $this->sendWhatsApp($response),
        //     'telegram' => $this->sendTelegram($response),
        //     'email'    => $this->sendEmail($response),
        // }
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
}
