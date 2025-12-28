<?php

namespace App\Http\Controllers;

use App\Services\VeraChatService;
use App\Services\VeraChatEngine;
use App\Models\VeraConversation;
use App\Models\VeraConversationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * VeraChatController
 *
 * FASE 3: Chat interactivo con Vera
 *
 * Sistema potente basado en REGLAS (NO usa IA):
 * - Intent Recognition: Detecta intenciones del usuario
 * - Context-Aware: Respuestas según hora del día y estado
 * - Query Builder: Convierte lenguaje natural a queries DB
 * - Templates Dinámicos: Respuestas con datos reales
 * - Action Cards: Genera tarjetas interactivas automáticamente
 *
 * La IA de Claude se reserva SOLO para:
 * - Analizar y categorizar emails entrantes
 * - Generar respuestas de emails (el core de Vera)
 * - Aprender estilo de escritura del usuario
 */
class VeraChatController extends Controller
{
    protected VeraChatService $chatService;

    public function __construct()
    {
        $this->chatService = new VeraChatService();
    }

    /**
     * Mostrar interfaz de chat
     */
    public function index()
    {
        $user = Auth::user();

        // Obtener conversación activa o crear una nueva
        $conversation = $this->chatService->getOrCreateActiveConversation($user);

        // Obtener todas las conversaciones del usuario
        $conversations = $this->chatService->getUserConversations($user);

        return view('vera.chat.index', compact('conversation', 'conversations'));
    }

    /**
     * API: Enviar mensaje
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:vera_conversations,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = Auth::user();

        $conversation = VeraConversation::findOrFail($validated['conversation_id']);

        // Verificar que la conversación pertenece al usuario
        if ($conversation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado'
            ], 403);
        }

        try {
            // Guardar mensaje del usuario
            $userMessage = VeraConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $validated['message'],
                'tokens_consumed' => 0, // No consume tokens
            ]);

            // Procesar mensaje con el motor de reglas (SIN IA)
            $chatEngine = new VeraChatEngine($user);
            $engineResponse = $chatEngine->processMessage($validated['message']);

            // Guardar respuesta de Vera
            $veraMessage = VeraConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $engineResponse['content'],
                'tokens_consumed' => 0, // No consume tokens (sistema de reglas)
                'metadata' => $engineResponse['metadata'],
            ]);

            // Registrar actividad para el log
            $activityDescription = null;
            $intent = $engineResponse['intent'] ?? null;

            if (in_array($intent, ['inbox', 'stats', 'calendar'])) {
                $activityLabels = [
                    'inbox' => 'Consultó emails',
                    'stats' => 'Revisó estadísticas',
                    'calendar' => 'Consultó agenda',
                ];

                $activityDescription = [
                    'time' => now()->format('H:i'),
                    'description' => $activityLabels[$intent] ?? 'Consultó información'
                ];
            }

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $veraMessage->id,
                    'role' => $veraMessage->role,
                    'content' => $veraMessage->content,
                    'created_at' => $veraMessage->created_at->toIso8601String(),
                    'metadata' => $veraMessage->metadata,
                    'action_cards' => $engineResponse['action_cards'] ?? [],
                ],
                'activity' => $activityDescription,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error procesando mensaje: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener mensajes de una conversación
     */
    public function getMessages(Request $request, $conversationId)
    {
        $user = Auth::user();

        $conversation = VeraConversation::findOrFail($conversationId);

        // Verificar que la conversación pertenece al usuario
        if ($conversation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado'
            ], 403);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'created_at' => $msg->created_at->toIso8601String(),
                    'tokens_consumed' => $msg->tokens_consumed,
                    'metadata' => $msg->metadata,
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'type' => $conversation->conversation_type,
                'created_at' => $conversation->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * API: Crear nueva conversación
     */
    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:chat,training,rule_creation,email_review,style_learning',
            'title' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $conversation = $this->chatService->createConversation(
            $user,
            $validated['type'] ?? 'chat',
            $validated['title'] ?? null
        );

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'type' => $conversation->conversation_type,
                'created_at' => $conversation->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * API: Archivar conversación
     */
    public function archiveConversation($conversationId)
    {
        $user = Auth::user();

        $conversation = VeraConversation::findOrFail($conversationId);

        // Verificar que la conversación pertenece al usuario
        if ($conversation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado'
            ], 403);
        }

        $this->chatService->archiveConversation($conversation);

        return response()->json([
            'success' => true,
            'message' => 'Conversación archivada correctamente',
        ]);
    }

    /**
     * API: Obtener todas las conversaciones
     */
    public function getConversations(Request $request)
    {
        $user = Auth::user();
        $conversations = $this->chatService->getUserConversations($user);

        return response()->json([
            'success' => true,
            'conversations' => $conversations->map(function ($conv) {
                $lastMessage = $conv->messages->first();

                return [
                    'id' => $conv->id,
                    'title' => $conv->title ?? 'Nueva conversación',
                    'type' => $conv->conversation_type,
                    'created_at' => $conv->created_at->toIso8601String(),
                    'updated_at' => $conv->updated_at->toIso8601String(),
                    'last_message' => $lastMessage ? [
                        'content' => substr($lastMessage->content, 0, 100),
                        'created_at' => $lastMessage->created_at->toIso8601String(),
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * API: Dar feedback a un mensaje
     */
    public function messageFeedback(Request $request, $messageId)
    {
        $validated = $request->validate([
            'feedback' => 'required|in:helpful,not_helpful,neutral',
        ]);

        $user = Auth::user();

        $message = \App\Models\VeraConversationMessage::findOrFail($messageId);

        // Verificar que el mensaje pertenece a una conversación del usuario
        if ($message->conversation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado'
            ], 403);
        }

        $message->update([
            'user_feedback' => $validated['feedback'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback guardado correctamente',
        ]);
    }

    /**
     * API: Obtener contexto actual del usuario
     * Para el panel lateral del chat
     */
    public function getContext(Request $request)
    {
        $user = Auth::user();

        // Obtener emails sin leer de hoy
        $unreadEmails = \App\Models\Email::where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereDate('received_at', '>=', now()->startOfDay())
            ->count();

        // Emails importantes
        $importantEmails = \App\Models\Email::where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('is_starred', true)
            ->count();

        // Emails que requieren acción (sin leer y no archivados)
        $actionRequired = \App\Models\Email::where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->whereDate('received_at', '>=', now()->subDays(3))
            ->count();

        // TODO: Reuniones de hoy (cuando tengas el modelo Calendar)
        $todayMeetings = 0;
        $meetings = [];

        // Reglas activas (cuando implementes el sistema de reglas)
        $activeRules = 2; // Placeholder

        // Alertas
        $alerts = [];
        $emailsThisMonth = \App\Models\VeraActivityLog::where('user_id', $user->id)
            ->where('counts_towards_limit', true)
            ->whereMonth('created_at', now()->month)
            ->count();

        $monthlyLimit = $user->monthly_email_limit ?? 100;
        $percentage = $monthlyLimit > 0 ? ($emailsThisMonth / $monthlyLimit) * 100 : 0;

        if ($percentage >= 80) {
            $alerts[] = [
                'id' => 1,
                'type' => 'limit_warning',
                'message' => "Límite de emails al " . round($percentage) . "% ({$emailsThisMonth}/{$monthlyLimit})"
            ];
        }

        // Log de actividad de hoy
        $activityLog = \App\Models\VeraActivityLog::where('user_id', $user->id)
            ->whereDate('created_at', now())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'time' => $activity->created_at->format('H:i'),
                    'description' => $this->getActivityDescription($activity),
                ];
            });

        // Estadísticas de la semana
        $weekStart = now()->startOfWeek();
        $weekEmails = \App\Models\Email::where('user_id', $user->id)
            ->whereBetween('received_at', [$weekStart, now()])
            ->count();

        $weekMeetings = 0; // TODO: Cuando tengas calendario

        // Acciones sugeridas dinámicas
        $suggestedActions = $this->generateSuggestedActions($user, $unreadEmails, $importantEmails);

        return response()->json([
            'success' => true,
            'context' => [
                'unread_emails' => $unreadEmails,
                'important_emails' => $importantEmails,
                'action_required' => $actionRequired,
                'today_meetings' => $todayMeetings,
                'meetings' => $meetings,
                'active_rules' => $activeRules,
                'alerts' => $alerts,
                'week_emails' => $weekEmails,
                'week_meetings' => $weekMeetings,
                'pending_actions' => $actionRequired,
            ],
            'activity_log' => $activityLog,
            'suggested_actions' => $suggestedActions,
        ]);
    }

    /**
     * Generar descripción legible de una actividad
     */
    private function getActivityDescription($activity): string
    {
        $descriptions = [
            'email_processed' => 'Procesó un email',
            'email_replied' => 'Respondió un email',
            'email_archived' => 'Archivó un email',
            'email_categorized' => 'Categorizó un email',
            'chat_interaction' => 'Chat con usuario',
            'rule_created' => 'Creó una regla',
            'training_updated' => 'Actualizó entrenamiento',
        ];

        $baseDescription = $descriptions[$activity->activity_type] ?? 'Realizó una acción';

        // Agregar detalles si hay metadata
        if ($activity->metadata && isset($activity->metadata['email_subject'])) {
            $subject = substr($activity->metadata['email_subject'], 0, 30);
            return $baseDescription . ': "' . $subject . '"';
        }

        return $baseDescription;
    }

    /**
     * Generar acciones sugeridas dinámicas según el contexto
     */
    private function generateSuggestedActions($user, $unreadEmails, $importantEmails): array
    {
        $actions = [];

        // Si hay emails urgentes
        if ($importantEmails > 0) {
            $actions[] = [
                'id' => 1,
                'icon' => '📧',
                'title' => "Revisar {$importantEmails} email" . ($importantEmails > 1 ? 's' : '') . " importante" . ($importantEmails > 1 ? 's' : ''),
                'description' => 'Requieren tu atención',
                'bgColor' => 'bg-red-100',
                'action' => '/inbox urgentes'
            ];
        }

        // Si hay emails sin leer
        if ($unreadEmails > 5) {
            $actions[] = [
                'id' => 2,
                'icon' => '🗑️',
                'title' => 'Organizar bandeja de entrada',
                'description' => "{$unreadEmails} emails sin leer",
                'bgColor' => 'bg-yellow-100',
                'action' => '/inbox organizar'
            ];
        }

        // Acción de stats siempre disponible
        $actions[] = [
            'id' => 3,
            'icon' => '📊',
            'title' => 'Ver actividad de la semana',
            'description' => 'Resumen y estadísticas',
            'bgColor' => 'bg-purple-100',
            'action' => '/stats'
        ];

        // Crear regla siempre disponible
        $actions[] = [
            'id' => 4,
            'icon' => '⚡',
            'title' => 'Crear regla de automatización',
            'description' => 'Ahorra tiempo en el futuro',
            'bgColor' => 'bg-green-100',
            'action' => '/crear-regla'
        ];

        return array_slice($actions, 0, 4); // Máximo 4 acciones
    }

}
