<?php

namespace App\Services;

use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * VeraInsightsService - Análisis profundo de emails
 *
 * Genera insights inteligentes basados en emails procesados por Vera
 */
class VeraInsightsService
{
    /**
     * Generar insights completos para un usuario
     *
     * @param User $user
     * @return array
     */
    public function generateInsights(User $user): array
    {
        $processedEmails = Email::where('user_id', $user->id)
            ->where('vera_processed', true)
            ->get();

        if ($processedEmails->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No hay emails procesados por Vera aún',
            ];
        }

        return [
            'success' => true,
            'total_analyzed' => $processedEmails->count(),
            'top_senders' => $this->getTopSenders($processedEmails),
            'categories' => $this->getCategoryDistribution($processedEmails),
            'sentiment_analysis' => $this->getSentimentAnalysis($processedEmails),
            'action_required' => $this->getActionRequiredStats($processedEmails),
            'time_patterns' => $this->getTimePatterns($processedEmails),
            'automation_suggestions' => $this->generateAutomationSuggestions($processedEmails),
        ];
    }

    /**
     * Top remitentes más frecuentes
     *
     * @param Collection $emails
     * @return array
     */
    protected function getTopSenders(Collection $emails): array
    {
        $senders = $emails->groupBy('from_email')
            ->map(function ($emailsFromSender) {
                $avgSentiment = $emailsFromSender->avg('vera_sentiment_score');
                $categories = $emailsFromSender->pluck('category')->countBy();

                return [
                    'email' => $emailsFromSender->first()->from_email,
                    'name' => $emailsFromSender->first()->from_name ?? 'Sin nombre',
                    'count' => $emailsFromSender->count(),
                    'avg_sentiment' => round($avgSentiment, 2),
                    'sentiment_label' => $this->getSentimentLabel($avgSentiment),
                    'most_common_category' => $categories->keys()->first(),
                ];
            })
            ->sortByDesc('count')
            ->take(20)
            ->values()
            ->toArray();

        return $senders;
    }

    /**
     * Distribución de categorías
     *
     * @param Collection $emails
     * @return array
     */
    protected function getCategoryDistribution(Collection $emails): array
    {
        $categories = $emails->groupBy('category')
            ->map(function ($emailsInCategory, $category) {
                return [
                    'category' => $category ?? 'sin_categoria',
                    'count' => $emailsInCategory->count(),
                    'percentage' => 0, // Se calculará después
                ];
            })
            ->sortByDesc('count')
            ->values();

        $total = $categories->sum('count');

        return $categories->map(function ($cat) use ($total) {
            $cat['percentage'] = round(($cat['count'] / $total) * 100, 1);
            return $cat;
        })->toArray();
    }

    /**
     * Análisis de sentimiento general
     *
     * @param Collection $emails
     * @return array
     */
    protected function getSentimentAnalysis(Collection $emails): array
    {
        $avgSentiment = $emails->avg('vera_sentiment_score');

        $distribution = [
            'positive' => $emails->where('vera_sentiment_score', '>', 0.6)->count(),
            'neutral' => $emails->whereBetween('vera_sentiment_score', [0.4, 0.6])->count(),
            'negative' => $emails->where('vera_sentiment_score', '<', 0.4)->count(),
        ];

        return [
            'average' => round($avgSentiment, 2),
            'label' => $this->getSentimentLabel($avgSentiment),
            'distribution' => $distribution,
            'most_positive_sender' => $this->getMostPositiveSender($emails),
            'most_negative_sender' => $this->getMostNegativeSender($emails),
        ];
    }

    /**
     * Estadísticas de acciones requeridas
     *
     * @param Collection $emails
     * @return array
     */
    protected function getActionRequiredStats(Collection $emails): array
    {
        $actions = $emails->groupBy('vera_action_required')
            ->map(function ($emailsWithAction, $action) {
                return [
                    'action' => $action,
                    'count' => $emailsWithAction->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();

        return $actions;
    }

    /**
     * Patrones temporales
     *
     * @param Collection $emails
     * @return array
     */
    protected function getTimePatterns(Collection $emails): array
    {
        // Emails por día de la semana
        $byDayOfWeek = $emails->groupBy(function ($email) {
            return $email->received_at->format('l'); // Monday, Tuesday, etc.
        })->map(fn($group) => $group->count())->toArray();

        // Emails por hora del día
        $byHour = $emails->groupBy(function ($email) {
            return $email->received_at->format('H'); // 00-23
        })->map(fn($group) => $group->count())->toArray();

        return [
            'by_day_of_week' => $byDayOfWeek,
            'by_hour' => $byHour,
            'busiest_day' => collect($byDayOfWeek)->sortDesc()->keys()->first(),
            'busiest_hour' => collect($byHour)->sortDesc()->keys()->first(),
        ];
    }

    /**
     * Generar sugerencias de automatización
     *
     * @param Collection $emails
     * @return array
     */
    protected function generateAutomationSuggestions(Collection $emails): array
    {
        $suggestions = [];

        // Sugerencia 1: Remitentes frecuentes
        $topSender = $emails->groupBy('from_email')
            ->sortByDesc(fn($group) => $group->count())
            ->first();

        if ($topSender && $topSender->count() > 50) {
            $suggestions[] = [
                'type' => 'frequent_sender',
                'priority' => 'high',
                'title' => 'Remitente muy frecuente detectado',
                'description' => "Recibes muchos emails de {$topSender->first()->from_email} ({$topSender->count()} emails). ¿Crear regla automática?",
                'suggested_action' => 'auto_categorize',
            ];
        }

        // Sugerencia 2: Categoría dominante
        $categories = $emails->groupBy('category');
        $dominantCategory = $categories->sortByDesc(fn($group) => $group->count())->first();

        if ($dominantCategory && $dominantCategory->count() > ($emails->count() * 0.3)) {
            $categoryName = $dominantCategory->first()->category ?? 'desconocida';
            $suggestions[] = [
                'type' => 'dominant_category',
                'priority' => 'medium',
                'title' => "Categoría '{$categoryName}' muy frecuente",
                'description' => "El {$this->calculatePercentage($dominantCategory->count(), $emails->count())}% de tus emails son de tipo '{$categoryName}'. ¿Configurar respuestas automáticas?",
                'suggested_action' => 'auto_response',
            ];
        }

        // Sugerencia 3: Acción requerida: responder
        $needsReply = $emails->where('vera_action_required', 'reply');
        if ($needsReply->count() > 100) {
            $suggestions[] = [
                'type' => 'needs_reply',
                'priority' => 'high',
                'title' => 'Muchos emails requieren respuesta',
                'description' => "{$needsReply->count()} emails detectados que necesitan respuesta. ¿Activar sugerencias automáticas de respuesta?",
                'suggested_action' => 'auto_reply_suggestions',
            ];
        }

        // Sugerencia 4: Sentimiento negativo
        $negativeEmails = $emails->where('vera_sentiment_score', '<', 0.3);
        if ($negativeEmails->count() > 20) {
            $suggestions[] = [
                'type' => 'negative_sentiment',
                'priority' => 'medium',
                'title' => 'Emails con sentimiento negativo detectados',
                'description' => "{$negativeEmails->count()} emails con tono negativo o urgente. ¿Priorizar automáticamente?",
                'suggested_action' => 'auto_prioritize',
            ];
        }

        return $suggestions;
    }

    /**
     * Remitente más positivo
     */
    protected function getMostPositiveSender(Collection $emails): ?array
    {
        $sender = $emails->groupBy('from_email')
            ->filter(fn($group) => $group->count() >= 5) // Mínimo 5 emails
            ->map(function ($group) {
                return [
                    'email' => $group->first()->from_email,
                    'name' => $group->first()->from_name,
                    'avg_sentiment' => $group->avg('vera_sentiment_score'),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('avg_sentiment')
            ->first();

        return $sender;
    }

    /**
     * Remitente más negativo
     */
    protected function getMostNegativeSender(Collection $emails): ?array
    {
        $sender = $emails->groupBy('from_email')
            ->filter(fn($group) => $group->count() >= 5) // Mínimo 5 emails
            ->map(function ($group) {
                return [
                    'email' => $group->first()->from_email,
                    'name' => $group->first()->from_name,
                    'avg_sentiment' => $group->avg('vera_sentiment_score'),
                    'count' => $group->count(),
                ];
            })
            ->sortBy('avg_sentiment')
            ->first();

        return $sender;
    }

    /**
     * Obtener label de sentimiento
     */
    protected function getSentimentLabel(float $score): string
    {
        if ($score >= 0.7) return 'Muy positivo';
        if ($score >= 0.55) return 'Positivo';
        if ($score >= 0.45) return 'Neutral';
        if ($score >= 0.3) return 'Negativo';
        return 'Muy negativo';
    }

    /**
     * Calcular porcentaje
     */
    protected function calculatePercentage(int $part, int $total): float
    {
        return round(($part / $total) * 100, 1);
    }
}
