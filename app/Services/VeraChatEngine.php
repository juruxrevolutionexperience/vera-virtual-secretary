<?php

namespace App\Services;

use App\Models\User;
use App\Models\Email;
use App\Models\VeraActivityLog;
use Illuminate\Support\Facades\DB;

/**
 * Motor de Chat Basado en Reglas (SIN IA)
 *
 * Sistema potente que simula inteligencia usando:
 * - Detección de intenciones
 * - Respuestas context-aware
 * - Query builder natural
 * - Templates dinámicos
 */
class VeraChatEngine
{
    private User $user;
    private array $context;

    // Mapeo de intenciones con patrones
    private array $intentPatterns = [
        'inbox' => [
            'keywords' => ['inbox', 'bandeja', 'correo', 'email', 'mensaje'],
            'modifiers' => ['urgente', 'importante', 'pendiente', 'sin leer', 'hoy', 'nuevos'],
            'priority' => 10
        ],
        'calendar' => [
            'keywords' => ['agenda', 'reunión', 'reuniones', 'calendario', 'cita', 'meeting'],
            'modifiers' => ['hoy', 'mañana', 'semana', 'próxima', 'pendiente'],
            'priority' => 10
        ],
        'stats' => [
            'keywords' => ['estadística', 'resumen', 'actividad', 'reporte', 'cuánto', 'cuántos'],
            'modifiers' => ['hoy', 'semana', 'mes', 'total'],
            'priority' => 8
        ],
        'help' => [
            'keywords' => ['ayuda', 'help', 'qué puedes', 'comandos', 'cómo'],
            'modifiers' => [],
            'priority' => 5
        ],
        'greeting' => [
            'keywords' => ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hey', 'hi'],
            'modifiers' => [],
            'priority' => 3
        ],
        'create_rule' => [
            'keywords' => ['crear regla', 'automatizar', 'automatización', 'regla nueva'],
            'modifiers' => [],
            'priority' => 9
        ],
        'archive' => [
            'keywords' => ['archivar', 'limpiar', 'organizar'],
            'modifiers' => ['newsletter', 'spam', 'promocional'],
            'priority' => 7
        ]
    ];

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->loadContext();
    }

    /**
     * Procesar mensaje del usuario y generar respuesta
     */
    public function processMessage(string $message): array
    {
        // 1. Detectar intención
        $intent = $this->detectIntent($message);

        // 2. Extraer parámetros
        $params = $this->extractParameters($message, $intent);

        // 3. Generar respuesta basada en intención
        $response = $this->generateResponse($intent, $params);

        // 4. Generar action cards si aplica
        $actionCards = $this->generateActionCards($intent, $params);

        return [
            'content' => $response,
            'action_cards' => $actionCards,
            'intent' => $intent,
            'metadata' => [
                'detected_intent' => $intent,
                'parameters' => $params,
                'processed_at' => now()->toIso8601String()
            ]
        ];
    }

    /**
     * Detectar intención del mensaje usando patrones
     */
    private function detectIntent(string $message): string
    {
        $messageLower = strtolower($message);
        $scores = [];

        foreach ($this->intentPatterns as $intent => $pattern) {
            $score = 0;

            // Buscar keywords principales
            foreach ($pattern['keywords'] as $keyword) {
                if (str_contains($messageLower, strtolower($keyword))) {
                    $score += $pattern['priority'];
                    break; // Solo contar una vez por intent
                }
            }

            // Buscar modificadores (añaden puntos extra)
            foreach ($pattern['modifiers'] as $modifier) {
                if (str_contains($messageLower, strtolower($modifier))) {
                    $score += 2;
                }
            }

            $scores[$intent] = $score;
        }

        // Obtener intent con mayor score
        $detectedIntent = array_search(max($scores), $scores);

        // Si no hay match claro, default a 'general'
        return max($scores) > 0 ? $detectedIntent : 'general';
    }

    /**
     * Extraer parámetros del mensaje
     */
    private function extractParameters(string $message, string $intent): array
    {
        $messageLower = strtolower($message);
        $params = [];

        // Detectar filtros temporales
        if (str_contains($messageLower, 'hoy')) {
            $params['time_filter'] = 'today';
        } elseif (str_contains($messageLower, 'ayer')) {
            $params['time_filter'] = 'yesterday';
        } elseif (str_contains($messageLower, 'semana')) {
            $params['time_filter'] = 'week';
        } elseif (str_contains($messageLower, 'mes')) {
            $params['time_filter'] = 'month';
        }

        // Detectar filtros de importancia
        if (str_contains($messageLower, 'urgente') || str_contains($messageLower, 'importante')) {
            $params['importance'] = 'high';
        }

        // Detectar estado
        if (str_contains($messageLower, 'sin leer') || str_contains($messageLower, 'no leído')) {
            $params['read_status'] = 'unread';
        }

        // Detectar tipo de email
        if (str_contains($messageLower, 'newsletter')) {
            $params['email_type'] = 'newsletter';
        } elseif (str_contains($messageLower, 'promocional')) {
            $params['email_type'] = 'promotional';
        }

        return $params;
    }

    /**
     * Generar respuesta basada en intención y contexto
     */
    private function generateResponse(string $intent, array $params): string
    {
        switch ($intent) {
            case 'inbox':
                return $this->generateInboxResponse($params);

            case 'calendar':
                return $this->generateCalendarResponse($params);

            case 'stats':
                return $this->generateStatsResponse($params);

            case 'help':
                return $this->generateHelpResponse();

            case 'greeting':
                return $this->generateGreetingResponse();

            case 'create_rule':
                return $this->generateCreateRuleResponse();

            case 'archive':
                return $this->generateArchiveResponse($params);

            default:
                return $this->generateGeneralResponse();
        }
    }

    /**
     * Respuesta para consultas de inbox
     */
    private function generateInboxResponse(array $params): string
    {
        $query = Email::where('user_id', $this->user->id);

        // Aplicar filtros
        if (isset($params['time_filter'])) {
            $query = $this->applyTimeFilter($query, $params['time_filter']);
        }

        if (isset($params['read_status']) && $params['read_status'] === 'unread') {
            $query->whereNull('read_at');
        }

        if (isset($params['importance']) && $params['importance'] === 'high') {
            $query->where('is_starred', true);
        }

        $count = $query->count();
        $emails = $query->orderByDesc('received_at')->limit(5)->get();

        // Generar respuesta contextual
        $response = $this->buildContextualMessage($count, $params);

        // Añadir detalles de emails importantes
        if ($count > 0 && $emails->count() > 0) {
            $response .= "\n\n📬 **Aquí están tus emails más relevantes:**\n";
            foreach ($emails->take(3) as $email) {
                $from = $email->from_name ?? $email->from_email;
                $subject = substr($email->subject, 0, 50);
                $response .= "\n• **{$from}**: {$subject}";
            }
        }

        return $response;
    }

    /**
     * Respuesta para consultas de calendario
     */
    private function generateCalendarResponse(array $params): string
    {
        // TODO: Cuando implementes el modelo Calendar
        $timeContext = $params['time_filter'] ?? 'today';

        $responses = [
            'today' => "📅 No tienes reuniones programadas para hoy. ¡Día perfecto para enfocarte en emails y tareas pendientes!",
            'week' => "📅 Esta semana no tienes reuniones agendadas.",
        ];

        return $responses[$timeContext] ?? $responses['today'];
    }

    /**
     * Respuesta para estadísticas
     */
    private function generateStatsResponse(array $params): string
    {
        $timeFilter = $params['time_filter'] ?? 'today';

        switch ($timeFilter) {
            case 'today':
                $processed = VeraActivityLog::where('user_id', $this->user->id)
                    ->whereDate('created_at', today())
                    ->count();

                $emails = Email::where('user_id', $this->user->id)
                    ->whereDate('received_at', today())
                    ->count();

                return "📊 **Resumen de hoy:**\n\n" .
                       "• Emails recibidos: {$emails}\n" .
                       "• Acciones de Vera: {$processed}\n" .
                       "• Estado: " . ($emails > 0 ? "Activo procesando emails" : "Todo al día") . "\n\n" .
                       "¿Quieres ver estadísticas de la semana completa?";

            case 'week':
                $weekStart = now()->startOfWeek();
                $processed = VeraActivityLog::where('user_id', $this->user->id)
                    ->whereBetween('created_at', [$weekStart, now()])
                    ->count();

                $emails = Email::where('user_id', $this->user->id)
                    ->whereBetween('received_at', [$weekStart, now()])
                    ->count();

                return "📊 **Resumen de la semana:**\n\n" .
                       "• Emails recibidos: {$emails}\n" .
                       "• Acciones de Vera: {$processed}\n" .
                       "• Promedio diario: " . round($emails / 7, 1) . " emails\n\n" .
                       "💡 Vera te ha ahorrado aproximadamente " . round($processed * 2) . " minutos esta semana.";

            default:
                return $this->generateStatsResponse(['time_filter' => 'today']);
        }
    }

    /**
     * Respuesta de ayuda
     */
    private function generateHelpResponse(): string
    {
        return "👋 **¡Estoy aquí para ayudarte!** Aquí hay algunas cosas que puedo hacer:\n\n" .
               "📧 **Emails:**\n" .
               "• \"Muéstrame emails urgentes\"\n" .
               "• \"¿Cuántos emails tengo sin leer?\"\n" .
               "• \"Emails de hoy\"\n\n" .
               "📊 **Estadísticas:**\n" .
               "• \"Resumen de hoy\"\n" .
               "• \"Estadísticas de la semana\"\n\n" .
               "⚡ **Automatización:**\n" .
               "• \"Crear una regla nueva\"\n" .
               "• \"Archivar newsletters\"\n\n" .
               "💡 **Tip:** También puedes usar comandos rápidos como `/inbox`, `/stats`, `/agenda`";
    }

    /**
     * Respuesta de saludo contextual
     */
    private function generateGreetingResponse(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = "¡Buenos días";
        } elseif ($hour < 19) {
            $greeting = "¡Buenas tardes";
        } else {
            $greeting = "¡Buenas noches";
        }

        $name = explode(' ', $this->user->name)[0];
        $unreadCount = $this->context['unread_emails'];

        $response = "{$greeting}, {$name}! 👋\n\n";

        if ($unreadCount > 0) {
            $response .= "Tienes **{$unreadCount} emails sin leer**. ";
            if ($unreadCount > 10) {
                $response .= "¿Quieres que te ayude a organizarlos?";
            } else {
                $response .= "¿Quieres revisarlos?";
            }
        } else {
            $response .= "¡Tu bandeja está al día! ✨ ¿En qué puedo ayudarte?";
        }

        return $response;
    }

    /**
     * Respuesta para crear reglas
     */
    private function generateCreateRuleResponse(): string
    {
        return "⚡ **Crear regla de automatización**\n\n" .
               "Las reglas te permiten automatizar acciones repetitivas. Por ejemplo:\n\n" .
               "• Archivar automáticamente newsletters\n" .
               "• Marcar como importantes emails de ciertos remitentes\n" .
               "• Categorizar emails por asunto\n\n" .
               "💡 Pronto podrás crear reglas personalizadas. Por ahora, cuéntame qué tarea repetitiva quieres automatizar y la guardaré para implementarla.";
    }

    /**
     * Respuesta para archivar
     */
    private function generateArchiveResponse(array $params): string
    {
        $emailType = $params['email_type'] ?? null;

        if ($emailType === 'newsletter') {
            $query = Email::where('user_id', $this->user->id)
                ->whereNull('archived_at')
                ->whereNull('read_at')
                ->where(function($q) {
                    $q->where('subject', 'like', '%newsletter%')
                      ->orWhere('subject', 'like', '%boletín%');
                });

            $count = $query->count();

            if ($count > 0) {
                return "🗑️ Encontré **{$count} newsletters** sin leer.\n\n" .
                       "💡 ¿Quieres que cree una regla para archivar automáticamente las newsletters en el futuro?";
            } else {
                return "✨ No encontré newsletters pendientes. ¡Todo limpio!";
            }
        }

        return "🗑️ ¿Qué tipo de emails quieres archivar? (newsletters, promocionales, etc.)";
    }

    /**
     * Respuesta general
     */
    private function generateGeneralResponse(): string
    {
        return "Entiendo que quieres hacer algo, pero no estoy segura de qué exactamente. 🤔\n\n" .
               "Puedes pedirme cosas como:\n" .
               "• Ver emails importantes\n" .
               "• Revisar tu agenda\n" .
               "• Obtener estadísticas\n" .
               "• Crear reglas de automatización\n\n" .
               "¿En qué te puedo ayudar?";
    }

    /**
     * Generar action cards según intención
     */
    private function generateActionCards(string $intent, array $params): array
    {
        $cards = [];

        if ($intent === 'inbox') {
            $query = Email::where('user_id', $this->user->id)
                ->whereNull('read_at');

            if (isset($params['importance']) && $params['importance'] === 'high') {
                $query->where('is_starred', true);
            }

            $emails = $query->orderByDesc('received_at')->limit(3)->get();

            foreach ($emails as $email) {
                $cards[] = [
                    'id' => 'email_' . $email->id,
                    'icon' => '📧',
                    'title' => $email->from_name ?? $email->from_email,
                    'subtitle' => substr($email->subject ?? 'Sin asunto', 0, 50),
                    'data' => [
                        'id' => $email->id,
                        'subject' => $email->subject,
                    ],
                    'actions' => [
                        [
                            'label' => 'Ver',
                            'action' => 'view_email',
                            'primary' => false,
                        ],
                        [
                            'label' => 'Responder',
                            'action' => 'respond',
                            'primary' => true,
                        ],
                    ],
                ];
            }
        }

        return $cards;
    }

    /**
     * Aplicar filtro temporal a query
     */
    private function applyTimeFilter($query, string $filter)
    {
        switch ($filter) {
            case 'today':
                return $query->whereDate('received_at', today());

            case 'yesterday':
                return $query->whereDate('received_at', today()->subDay());

            case 'week':
                return $query->whereBetween('received_at', [now()->startOfWeek(), now()]);

            case 'month':
                return $query->whereMonth('received_at', now()->month)
                            ->whereYear('received_at', now()->year);

            default:
                return $query;
        }
    }

    /**
     * Construir mensaje contextual
     */
    private function buildContextualMessage(int $count, array $params): string
    {
        $timeContext = $params['time_filter'] ?? 'general';
        $importance = $params['importance'] ?? null;
        $readStatus = $params['read_status'] ?? null;

        $prefix = '';
        if ($importance === 'high') {
            $prefix = 'importante' . ($count !== 1 ? 's' : '');
        } elseif ($readStatus === 'unread') {
            $prefix = 'sin leer';
        }

        $timeText = [
            'today' => 'hoy',
            'yesterday' => 'ayer',
            'week' => 'esta semana',
            'month' => 'este mes',
        ][$timeContext] ?? '';

        if ($count === 0) {
            return "✨ No tienes emails {$prefix} " . ($timeText ? "de {$timeText}" : '') . ". ¡Todo al día!";
        } elseif ($count === 1) {
            return "📧 Tienes 1 email {$prefix} " . ($timeText ? "de {$timeText}" : '') . ".";
        } else {
            return "📧 Tienes **{$count} emails** {$prefix} " . ($timeText ? "de {$timeText}" : '') . ".";
        }
    }

    /**
     * Cargar contexto del usuario
     */
    private function loadContext(): void
    {
        $this->context = [
            'unread_emails' => Email::where('user_id', $this->user->id)
                ->whereNull('read_at')
                ->count(),
            'important_emails' => Email::where('user_id', $this->user->id)
                ->whereNull('read_at')
                ->where('is_starred', true)
                ->count(),
        ];
    }
}
