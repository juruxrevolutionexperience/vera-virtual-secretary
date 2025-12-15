<?php

namespace App\Services;

use App\Models\Email;
use Illuminate\Support\Facades\Log;

/**
 * VeraRulesService
 *
 * Análisis de emails usando REGLAS (sin IA)
 * - Gratis (0 coste)
 * - Rápido (milisegundos)
 * - Efectivo para 70-80% de emails
 */
class VeraRulesService
{
    /**
     * Analizar email con reglas (SIN IA)
     *
     * @param Email $email
     * @return array
     */
    public function analyzeEmail(Email $email): array
    {
        Log::info('📊 Analizando email con reglas', [
            'email_id' => $email->id,
            'subject' => $email->subject
        ]);

        $analysis = [
            'category' => $this->detectCategory($email),
            'priority' => $this->detectPriority($email),
            'sentiment_label' => $this->detectSentiment($email),
            'sentiment_score' => $this->calculateSentimentScore($email),
            'action_required' => $this->suggestAction($email),
            'topics' => $this->extractTopics($email),
            'summary' => '', // Se generará después
            'confidence' => 0.7, // Reglas son ~70% confiables
            'method' => 'rules',
        ];

        // Generar resumen basado en reglas
        $analysis['summary'] = $this->generateSummary($email, $analysis);

        Log::info('✅ Análisis con reglas completado', [
            'email_id' => $email->id,
            'category' => $analysis['category'],
            'priority' => $analysis['priority'],
            'sentiment' => $analysis['sentiment_label']
        ]);

        return $analysis;
    }

    /**
     * Detectar categoría del email
     *
     * @param Email $email
     * @return string promotional|newsletter|social|work|personal
     */
    protected function detectCategory(Email $email): string
    {
        $body = strtolower($email->body_text ?? $email->body_html ?? '');
        $subject = strtolower($email->subject ?? '');
        $from = strtolower($email->from_email);

        // 1. PROMOCIONES - alta prioridad de detección
        $promoIndicators = [
            'unsubscribe', 'darse de baja', 'cancelar suscripción',
            'promoción', 'oferta', 'descuento', 'rebaja', '% off',
            'compra ahora', 'ofertas', 'black friday', 'cyber monday',
            'últimas unidades', 'stock limitado'
        ];

        foreach ($promoIndicators as $indicator) {
            if (str_contains($body, $indicator) || str_contains($subject, $indicator)) {
                return 'promotional';
            }
        }

        // 2. NEWSLETTERS
        $newsletterIndicators = [
            'newsletter', 'noreply', 'no-reply', 'info@', 'news@',
            'boletin', 'boletín', 'suscripción'
        ];

        foreach ($newsletterIndicators as $indicator) {
            if (str_contains($from, $indicator)) {
                return 'newsletter';
            }
        }

        // 3. REDES SOCIALES
        $socialDomains = [
            'linkedin.com', 'facebook.com', 'twitter.com', 'x.com',
            'instagram.com', 'tiktok.com', 'youtube.com'
        ];

        foreach ($socialDomains as $domain) {
            if (str_contains($from, $domain)) {
                return 'social';
            }
        }

        // 4. TRABAJO - detectar por dominio corporativo
        // Si el from_email tiene un dominio que no es común (gmail, outlook, etc)
        // probablemente es trabajo
        $personalDomains = [
            'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com',
            'icloud.com', 'live.com', 'proton.me', 'protonmail.com'
        ];

        $isPersonalDomain = false;
        foreach ($personalDomains as $domain) {
            if (str_contains($from, $domain)) {
                $isPersonalDomain = true;
                break;
            }
        }

        // Si tiene palabras de trabajo y NO es dominio personal
        $workKeywords = [
            'proyecto', 'reunión', 'meeting', 'deadline', 'entrega',
            'informe', 'reporte', 'propuesta', 'contrato', 'factura'
        ];

        if (!$isPersonalDomain) {
            foreach ($workKeywords as $keyword) {
                if (str_contains($subject, $keyword) || str_contains($body, $keyword)) {
                    return 'work';
                }
            }
        }

        // 5. Por defecto: PERSONAL
        return 'personal';
    }

    /**
     * Detectar prioridad del email
     *
     * @param Email $email
     * @return string urgent|high|normal|low
     */
    protected function detectPriority(Email $email): string
    {
        $subject = strtolower($email->subject ?? '');
        $body = strtolower($email->body_text ?? '');

        // 1. URGENTE - palabras clave críticas
        $urgentKeywords = [
            'urgente', 'urgent', 'asap', 'inmediato', 'ahora mismo',
            'cuanto antes', 'crítico', 'emergencia', 'emergency'
        ];

        foreach ($urgentKeywords as $keyword) {
            if (str_contains($subject, $keyword) || str_contains($body, $keyword)) {
                return 'urgent';
            }
        }

        // 2. ALTA - indicadores de importancia
        $highPriorityIndicators = [
            'importante', 'important', 'deadline', 'fecha límite',
            'hoy', 'today', 'mañana', 'tomorrow', 'esta semana',
            'reunión', 'meeting', 'junta', 'llamada', 'call'
        ];

        foreach ($highPriorityIndicators as $indicator) {
            if (str_contains($subject, $indicator)) {
                return 'high';
            }
        }

        // Emails con archivos adjuntos suelen ser importantes
        if ($email->attachments_count > 0) {
            return 'high';
        }

        // 3. BAJA - si es promoción o newsletter
        $category = $this->detectCategory($email);
        if (in_array($category, ['promotional', 'newsletter', 'social'])) {
            return 'low';
        }

        // 4. Por defecto: NORMAL
        return 'normal';
    }

    /**
     * Detectar sentimiento del email
     *
     * @param Email $email
     * @return string positive|neutral|negative|urgent
     */
    protected function detectSentiment(Email $email): string
    {
        $text = strtolower($email->subject . ' ' . ($email->body_text ?? ''));

        // 1. URGENTE (tiene prioridad sobre otros sentimientos)
        $priority = $this->detectPriority($email);
        if ($priority === 'urgent') {
            return 'urgent';
        }

        // 2. NEGATIVO - problemas, quejas, errores
        $negativeWords = [
            'problema', 'error', 'mal', 'no funciona', 'roto', 'broken',
            'fallo', 'ayuda', 'help', 'issue', 'queja', 'complaint',
            'molesto', 'frustrado', 'decepcionado', 'insatisfecho',
            'cancelar', 'devolver', 'reembolso'
        ];

        $negativeCount = 0;
        foreach ($negativeWords as $word) {
            if (str_contains($text, $word)) {
                $negativeCount++;
            }
        }

        if ($negativeCount >= 2) {
            return 'negative';
        }

        // 3. POSITIVO - agradecimientos, felicitaciones
        $positiveWords = [
            'gracias', 'thank', 'excelente', 'excellent', 'perfecto', 'perfect',
            'genial', 'great', 'fantástico', 'fantastic', 'felicidades',
            'congratulations', 'bien hecho', 'love', 'encanta', 'increíble',
            'amazing', 'wonderful', 'maravilloso'
        ];

        $positiveCount = 0;
        foreach ($positiveWords as $word) {
            if (str_contains($text, $word)) {
                $positiveCount++;
            }
        }

        if ($positiveCount >= 1) {
            return 'positive';
        }

        // 4. Por defecto: NEUTRAL
        return 'neutral';
    }

    /**
     * Calcular score de sentimiento (0.0 a 1.0)
     *
     * @param Email $email
     * @return float
     */
    protected function calculateSentimentScore(Email $email): float
    {
        $sentiment = $this->detectSentiment($email);

        return match($sentiment) {
            'urgent' => 0.2,    // Urgente = negativo
            'negative' => 0.3,  // Negativo
            'neutral' => 0.5,   // Neutral
            'positive' => 0.8,  // Positivo
            default => 0.5
        };
    }

    /**
     * Sugerir acción a realizar
     *
     * @param Email $email
     * @return string reply|archive|schedule|forward|none
     */
    protected function suggestAction(Email $email): string
    {
        $category = $this->detectCategory($email);
        $priority = $this->detectPriority($email);

        // 1. ARCHIVAR - promociones y newsletters
        if (in_array($category, ['promotional', 'newsletter'])) {
            return 'archive';
        }

        // 2. RESPONDER - emails urgentes sin leer
        if ($email->is_unread && $priority === 'urgent') {
            return 'reply';
        }

        // 3. RESPONDER - emails de alta prioridad sin leer
        if ($email->is_unread && $priority === 'high') {
            return 'reply';
        }

        // 4. PROGRAMAR - si menciona fecha futura
        $body = strtolower($email->body_text ?? '');
        $scheduleKeywords = ['próxima semana', 'next week', 'el lunes', 'monday'];
        foreach ($scheduleKeywords as $keyword) {
            if (str_contains($body, $keyword)) {
                return 'schedule';
            }
        }

        // 5. Por defecto: NINGUNA
        return 'none';
    }

    /**
     * Extraer tópicos principales del email
     *
     * @param Email $email
     * @return array
     */
    protected function extractTopics(Email $email): array
    {
        $text = strtolower($email->subject . ' ' . ($email->body_text ?? ''));
        $topics = [];

        // Tópicos comunes a detectar
        $topicKeywords = [
            'reunión' => ['reunión', 'meeting', 'junta', 'cita'],
            'proyecto' => ['proyecto', 'project', 'desarrollo', 'development'],
            'factura' => ['factura', 'invoice', 'pago', 'payment', 'cobro'],
            'contrato' => ['contrato', 'contract', 'acuerdo', 'agreement'],
            'entrega' => ['entrega', 'delivery', 'envío', 'shipping'],
            'soporte' => ['soporte', 'support', 'ayuda', 'help', 'problema'],
            'reporte' => ['reporte', 'report', 'informe', 'análisis'],
            'propuesta' => ['propuesta', 'proposal', 'presupuesto', 'quote'],
        ];

        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $topics[] = $topic;
                    break; // Solo agregar una vez
                }
            }
        }

        // Si no encontramos tópicos, usar categoría como tópico
        if (empty($topics)) {
            $category = $this->detectCategory($email);
            $topics[] = $category;
        }

        return array_unique($topics);
    }

    /**
     * Generar resumen basado en reglas
     *
     * @param Email $email
     * @param array $analysis
     * @return string
     */
    protected function generateSummary(Email $email, array $analysis): string
    {
        $from = $email->from_name ?? explode('@', $email->from_email)[0];
        $category = $analysis['category'];
        $priority = $analysis['priority'];

        // Construir resumen según categoría
        $summary = match($category) {
            'promotional' => "Promoción de {$from}",
            'newsletter' => "Newsletter de {$from}",
            'social' => "Notificación de red social de {$from}",
            'work' => "Email de trabajo de {$from}",
            'personal' => "Email de {$from}",
            default => "Email de {$from}"
        };

        // Añadir prioridad si es alta o urgente
        if ($priority === 'urgent') {
            $summary .= ' (URGENTE)';
        } elseif ($priority === 'high') {
            $summary .= ' (importante)';
        }

        // Añadir tópicos principales
        if (!empty($analysis['topics']) && $category !== 'promotional') {
            $mainTopic = $analysis['topics'][0];
            $summary .= " sobre {$mainTopic}";
        }

        return $summary;
    }

    /**
     * Verificar si el email necesita análisis con IA
     *
     * @param Email $email
     * @param array $rulesAnalysis
     * @return bool
     */
    public function needsAI(Email $email, array $rulesAnalysis): bool
    {
        // 1. Emails cortos y simples NO necesitan IA
        $bodyLength = strlen($email->body_text ?? $email->body_html ?? '');
        if ($bodyLength < 100) {
            return false;
        }

        // 2. Promociones y newsletters NO necesitan IA
        if (in_array($rulesAnalysis['category'], ['promotional', 'newsletter', 'social'])) {
            return false;
        }

        // 3. Emails urgentes SÍ necesitan IA (mejor análisis)
        if ($rulesAnalysis['priority'] === 'urgent') {
            return true;
        }

        // 4. Emails importantes y largos SÍ necesitan IA
        if ($rulesAnalysis['priority'] === 'high' && $bodyLength > 500) {
            return true;
        }

        // 5. Emails personales largos SÍ necesitan IA (para resumir)
        if ($rulesAnalysis['category'] === 'personal' && $bodyLength > 1000) {
            return true;
        }

        // 6. Emails de trabajo SÍ necesitan IA (contexto importante)
        if ($rulesAnalysis['category'] === 'work') {
            return true;
        }

        // 7. Por defecto: NO necesita IA (ahorrar costes)
        return false;
    }

    /**
     * Obtener estadísticas de análisis por reglas
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'version' => '1.0',
            'method' => 'rules',
            'cost_per_analysis' => 0.0,
            'avg_time_ms' => 5, // ~5 milisegundos
            'supported_categories' => [
                'promotional',
                'newsletter',
                'social',
                'work',
                'personal'
            ],
            'supported_priorities' => [
                'urgent',
                'high',
                'normal',
                'low'
            ],
            'supported_sentiments' => [
                'positive',
                'neutral',
                'negative',
                'urgent'
            ],
            'confidence_score' => 0.7, // 70% confiable
        ];
    }
}
