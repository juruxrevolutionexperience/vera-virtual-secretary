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
            'suggested_workflow_status' => '', // Se calculará después  
        ];

        // Generar resumen basado en reglas
        $analysis['summary'] = $this->generateSummary($email, $analysis);
        // Determinar workflow_status automáticamente  
        $analysis['suggested_workflow_status'] = $this->determineWorkflowStatus($email, $analysis);

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
        $body    = strtolower($email->body_text ?? strip_tags($email->body_html ?? ''));
        $subject = strtolower($email->subject ?? '');
        $from    = strtolower($email->from_email ?? '');

        // 0. TRANSACCIONAL - recibos, facturas automáticas, OTP, confirmaciones
        $transactionalIndicators = [
            'your receipt',
            'tu recibo',
            'order confirmation',
            'confirmación de pedido',
            'payment confirmation',
            'confirmación de pago',
            'invoice #',
            'factura #',
            'verification code',
            'código de verificación',
            'otp',
            'one-time password',
            'your account',
            'password reset',
            'restablecimiento de contraseña',
        ];
        foreach ($transactionalIndicators as $indicator) {
            if (str_contains($subject, $indicator) || str_contains($body, $indicator)) {
                return 'transactional';
            }
        }

        // Remitentes típicos de sistemas automáticos transaccionales
        $transactionalSenders = [
            'receipt@', 'billing@', 'invoices@', 'payments@',
            'noreply@', 'no-reply@', 'donotreply@',
            'notifications@', 'automated@', 'alerts@',
            'support@stripe', 'support@paypal',
        ];
        foreach ($transactionalSenders as $sender) {
            if (str_contains($from, $sender)) {
                return 'transactional';
            }
        }

        // 1. NEWSLETTERS - detectar por contenido y remitente
        $newsletterBodyIndicators = [
            'unsubscribe',
            'darse de baja',
            'cancelar suscripción',
            'opt-out',
            'ver en el navegador',
            'view in browser',
            'view this email',
            'newsletter',
            'boletín',
        ];
        foreach ($newsletterBodyIndicators as $indicator) {
            if (str_contains($body, $indicator)) {
                return 'newsletter';
            }
        }

        $newsletterSenders = [
            'newsletter', 'news@', 'bulletin@', 'digest@',
            'updates@', 'weekly@', 'monthly@', 'boletin@',
        ];
        foreach ($newsletterSenders as $indicator) {
            if (str_contains($from, $indicator)) {
                return 'newsletter';
            }
        }

        // 2. PROMOCIONES - ofertas, descuentos, marketing
        $promoIndicators = [
            'promoción', 'oferta', 'descuento', 'rebaja',
            '% off', '% de descuento', 'compra ahora', 'ofertas',
            'black friday', 'cyber monday', 'últimas unidades',
            'stock limitado', 'envío gratis', 'free shipping',
            'deal', 'sale ends', 'expires soon',
        ];
        foreach ($promoIndicators as $indicator) {
            if (str_contains($body, $indicator) || str_contains($subject, $indicator)) {
                return 'promotional';
            }
        }

        // 3. REDES SOCIALES
        $socialDomains = [
            'linkedin.com',
            'facebook.com',
            'twitter.com',
            'x.com',
            'instagram.com',
            'tiktok.com',
            'youtube.com'
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
            'gmail.com',
            'hotmail.com',
            'outlook.com',
            'yahoo.com',
            'icloud.com',
            'live.com',
            'proton.me',
            'protonmail.com'
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
            'proyecto',
            'reunión',
            'meeting',
            'deadline',
            'entrega',
            'informe',
            'reporte',
            'propuesta',
            'contrato',
            'factura'
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
            'urgente',
            'urgent',
            'asap',
            'inmediato',
            'ahora mismo',
            'cuanto antes',
            'crítico',
            'emergencia',
            'emergency'
        ];

        foreach ($urgentKeywords as $keyword) {
            if (str_contains($subject, $keyword) || str_contains($body, $keyword)) {
                return 'urgent';
            }
        }

        // 2. ALTA - indicadores de importancia
        $highPriorityIndicators = [
            'importante',
            'important',
            'deadline',
            'fecha límite',
            'hoy',
            'today',
            'mañana',
            'tomorrow',
            'esta semana',
            'reunión',
            'meeting',
            'junta',
            'llamada',
            'call'
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
            'problema',
            'error',
            'mal',
            'no funciona',
            'roto',
            'broken',
            'fallo',
            'ayuda',
            'help',
            'issue',
            'queja',
            'complaint',
            'molesto',
            'frustrado',
            'decepcionado',
            'insatisfecho',
            'cancelar',
            'devolver',
            'reembolso'
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
            'gracias',
            'thank',
            'excelente',
            'excellent',
            'perfecto',
            'perfect',
            'genial',
            'great',
            'fantástico',
            'fantastic',
            'felicidades',
            'congratulations',
            'bien hecho',
            'love',
            'encanta',
            'increíble',
            'amazing',
            'wonderful',
            'maravilloso'
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

        return match ($sentiment) {
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
        $summary = match ($category) {
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
        // 1. Emails automáticos NUNCA necesitan IA (reglas son suficientes)
        if (in_array($rulesAnalysis['category'], ['promotional', 'newsletter', 'social', 'transactional'])) {
            return false;
        }

        // 2. Emails vacíos/basura no necesitan IA
        $bodyLength = strlen($email->body_text ?? $email->body_html ?? '');
        if ($bodyLength < 20) {
            return false;
        }

        $text = strtolower(($email->body_text ?? '') . ' ' . ($email->subject ?? ''));

        // 3. Emails con preguntas directas SÍ necesitan IA (independientemente del tamaño)
        $hasQuestion = str_contains($text, '?')
            || str_contains($text, 'información')
            || str_contains($text, 'saber más')
            || str_contains($text, 'me podrías')
            || str_contains($text, 'podrías')
            || str_contains($text, 'puedes')
            || str_contains($text, 'cómo funciona')
            || str_contains($text, 'qué es')
            || str_contains($text, 'cuánto')
            || str_contains($text, 'cuanto')
            || str_contains($text, 'tell me')
            || str_contains($text, 'can you')
            || str_contains($text, 'could you');

        if ($hasQuestion) {
            return true;
        }

        // 4. Emails urgentes SÍ necesitan IA
        if ($rulesAnalysis['priority'] === 'urgent') {
            return true;
        }

        // 5. Emails de trabajo SÍ necesitan IA
        if ($rulesAnalysis['category'] === 'work') {
            return true;
        }

        // 6. Emails de alta prioridad SÍ necesitan IA
        if ($rulesAnalysis['priority'] === 'high') {
            return true;
        }

        // 7. Emails personales con cuerpo sustancial SÍ necesitan IA
        if ($rulesAnalysis['category'] === 'personal' && $bodyLength > 200) {
            return true;
        }

        // 8. Por defecto: NO necesita IA (ahorrar costes)
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
    /**                                                                                                                                                             
     * Determinar workflow_status automáticamente                                                                                                                   
     *                                                                                                                                                              
     * @param Email $email                                                                                                                                          
     * @param array $analysis                                                                                                                                       
     * @return string workflow_status sugerido                                                                                                                      
     */
    public function determineWorkflowStatus(Email $email, array $analysis): string
    {
        $category = $analysis['category'];
        $priority = $analysis['priority'];
        $action = $analysis['action_required'];
        $sentiment = $analysis['sentiment_label'];

        // REGLA 1: Emails urgentes que requieren respuesta                                                                                                         
        if ($priority === 'urgent' && $action === 'reply') {
            return 'pending_response';
        }

        // REGLA 2: Emails urgentes sin acción clara                                                                                                                
        if ($priority === 'urgent') {
            return 'pending_validation';
        }

        // REGLA 3: Emails importantes que requieren respuesta                                                                                                      
        if ($priority === 'high' && $action === 'reply') {
            return 'pending_response';
        }

        // REGLA 4: Emails de trabajo importantes sin responder                                                                                                     
        if ($category === 'work' && $priority === 'high' && !$email->is_read) {
            return 'pending_validation';
        }

        // REGLA 5: Emails negativos/problemas que necesitan revisión                                                                                               
        if ($sentiment === 'negative') {
            return 'in_review';
        }

        // REGLA 6: Emails que deben archivarse                                                                                                                     
        if ($action === 'archive') {
            return 'no_action_needed';
        }

        // REGLA 7: Promociones y newsletters (AUNQUE no estén leídos)                                                                                              
        if (in_array($category, ['promotional', 'newsletter', 'social'])) {
            return 'no_action_needed';
        }

        // REGLA 8: Emails que necesitan programarse                                                                                                                
        if ($action === 'schedule') {
            return 'pending_response';
        }

        // REGLA 9: Emails ya leídos sin acción requerida                                                                                                           
        if ($email->is_read && $action === 'none') {
            return 'no_action_needed';
        }

        // REGLA 10 (FALLBACK): Emails sin leer que no entraron en ninguna categoría específica                                                                     
        if (!$email->is_read) {
            return 'unread';
        }

        // REGLA 11 (ÚLTIMO FALLBACK): Emails leídos sin clasificación específica                                                                                   
        return 'no_action_needed';
    }
}
