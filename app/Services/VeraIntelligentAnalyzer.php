<?php

namespace App\Services;

use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VeraIntelligentAnalyzer
 *
 * Analiza TODOS los emails del usuario y saca conclusiones inteligentes
 * para configurar Vera de forma óptima.
 */
class VeraIntelligentAnalyzer
{
    /**
     * Analizar todos los emails del usuario y generar reporte completo
     */
    public function analyzeUser(User $user): array
    {
        Log::info('🧠 Iniciando análisis inteligente de Vera', ['user_id' => $user->id]);

        $report = [
            'user_id' => $user->id,
            'analyzed_at' => now()->toDateTimeString(),
            'email_statistics' => $this->getEmailStatistics($user),
            'communication_patterns' => $this->analyzeCommunicationPatterns($user),
            'writing_style' => $this->analyzeWritingStyle($user),
            'top_contacts' => $this->getTopContacts($user),
            'response_behavior' => $this->analyzeResponseBehavior($user),
            'email_types' => $this->categorizeEmailTypes($user),
            'conclusions' => [],
            'recommended_settings' => [],
        ];

        // Generar conclusiones inteligentes
        $report['conclusions'] = $this->generateConclusions($report);

        // Generar configuración recomendada
        $report['recommended_settings'] = $this->generateRecommendedSettings($report);

        Log::info('✅ Análisis inteligente completado', [
            'total_emails' => $report['email_statistics']['total_emails'],
            'conclusions' => count($report['conclusions']),
        ]);

        return $report;
    }

    /**
     * Obtener estadísticas generales de emails
     */
    protected function getEmailStatistics(User $user): array
    {
        $totalEmails = Email::where('user_id', $user->id)->count();
        $receivedEmails = Email::where('user_id', $user->id)
            ->whereNull('status')
            ->orWhere('status', '!=', 'sent')
            ->count();
        $sentEmails = Email::where('user_id', $user->id)
            ->where('status', 'sent')
            ->count();

        $firstEmail = Email::where('user_id', $user->id)
            ->orderBy('received_at')
            ->first();
        $lastEmail = Email::where('user_id', $user->id)
            ->orderBy('received_at', 'desc')
            ->first();

        $daysOfData = $firstEmail && $lastEmail
            ? $firstEmail->received_at->diffInDays($lastEmail->received_at)
            : 0;

        return [
            'total_emails' => $totalEmails,
            'received_emails' => $receivedEmails,
            'sent_emails' => $sentEmails,
            'ratio_received_sent' => $sentEmails > 0 ? round($receivedEmails / $sentEmails, 2) : 0,
            'days_of_data' => $daysOfData,
            'avg_emails_per_day' => $daysOfData > 0 ? round($totalEmails / $daysOfData, 2) : 0,
            'first_email_date' => $firstEmail?->received_at?->toDateString(),
            'last_email_date' => $lastEmail?->received_at?->toDateString(),
        ];
    }

    /**
     * Analizar patrones de comunicación
     */
    protected function analyzeCommunicationPatterns(User $user): array
    {
        // Analizar por día de la semana
        $byDayOfWeek = Email::where('user_id', $user->id)
            ->select(DB::raw('DAYOFWEEK(received_at) as day, COUNT(*) as count'))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Analizar por hora del día
        $byHourOfDay = Email::where('user_id', $user->id)
            ->select(DB::raw('HOUR(received_at) as hour, COUNT(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Determinar horario más activo
        $peakHour = !empty($byHourOfDay) ? array_keys($byHourOfDay, max($byHourOfDay))[0] : null;
        $peakDay = !empty($byDayOfWeek) ? array_keys($byDayOfWeek, max($byDayOfWeek))[0] : null;

        $dayNames = [
            1 => 'Domingo', 2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles',
            5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado'
        ];

        return [
            'by_day_of_week' => $byDayOfWeek,
            'by_hour_of_day' => $byHourOfDay,
            'peak_day' => $peakDay ? $dayNames[$peakDay] : 'Desconocido',
            'peak_hour' => $peakHour ? "{$peakHour}:00" : 'Desconocido',
            'is_mostly_weekday' => $this->isMostlyWeekday($byDayOfWeek),
            'is_mostly_business_hours' => $this->isMostlyBusinessHours($byHourOfDay),
        ];
    }

    /**
     * Analizar estilo de escritura del usuario
     */
    protected function analyzeWritingStyle(User $user): array
    {
        $sentEmails = Email::where('user_id', $user->id)
            ->where('status', 'sent')
            ->whereNotNull('body_text')
            ->get();

        if ($sentEmails->isEmpty()) {
            return [
                'has_sent_emails' => false,
                'total_sent' => 0,
                'message' => 'No hay emails enviados para analizar el estilo de escritura',
            ];
        }

        $greetings = [];
        $closings = [];
        $totalLength = 0;
        $totalEmojis = 0;
        $formalWordCount = 0;
        $casualWordCount = 0;

        $formalWords = ['estimado', 'cordialmente', 'atentamente', 'agradezco', 'disculpe'];
        $casualWords = ['hola', 'hey', 'qué tal', 'genial', 'perfecto', 'vale', 'ok'];

        foreach ($sentEmails as $email) {
            $text = $email->body_text ?? '';
            $totalLength += strlen($text);

            // Detectar emojis
            $totalEmojis += preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $text);

            // Detectar saludos (primeras 3 líneas)
            $lines = explode("\n", $text);
            $firstLines = array_slice($lines, 0, 3);
            foreach ($firstLines as $line) {
                $line = trim(strtolower($line));
                if (preg_match('/^(hola|buenos días|buenas tardes|buenas|hey|qué tal|estimado|estimada)/i', $line, $matches)) {
                    $greeting = ucfirst($matches[1]);
                    $greetings[$greeting] = ($greetings[$greeting] ?? 0) + 1;
                }
            }

            // Detectar despedidas (últimas 3 líneas)
            $lastLines = array_slice(array_filter($lines), -3);
            foreach ($lastLines as $line) {
                $line = trim(strtolower($line));
                if (preg_match('/(saludos|un abrazo|un saludo|gracias|muchas gracias|hasta pronto|nos vemos|cordialmente|atentamente)/i', $line, $matches)) {
                    $closing = ucfirst($matches[1]);
                    $closings[$closing] = ($closings[$closing] ?? 0) + 1;
                }
            }

            // Contar palabras formales vs casuales
            $textLower = strtolower($text);
            foreach ($formalWords as $word) {
                $formalWordCount += substr_count($textLower, $word);
            }
            foreach ($casualWords as $word) {
                $casualWordCount += substr_count($textLower, $word);
            }
        }

        $avgLength = round($totalLength / $sentEmails->count());
        $usesEmojis = $totalEmojis > 0;
        $emojiFrequency = $sentEmails->count() > 0 ? round($totalEmojis / $sentEmails->count(), 2) : 0;

        // Determinar nivel de formalidad (0 = muy casual, 1 = muy formal)
        $formalityScore = 0.5; // neutral por defecto
        if ($formalWordCount + $casualWordCount > 0) {
            $formalityScore = round($formalWordCount / ($formalWordCount + $casualWordCount), 2);
        }

        // Ordenar y obtener top 3
        arsort($greetings);
        arsort($closings);
        $topGreetings = array_slice(array_keys($greetings), 0, 3);
        $topClosings = array_slice(array_keys($closings), 0, 3);

        return [
            'has_sent_emails' => true,
            'total_sent' => $sentEmails->count(),
            'avg_email_length' => $avgLength,
            'uses_emojis' => $usesEmojis,
            'emoji_frequency' => $emojiFrequency,
            'formality_score' => $formalityScore,
            'tone' => $this->determineTone($formalityScore),
            'top_greetings' => $topGreetings,
            'top_closings' => $topClosings,
            'writing_style' => $this->determineWritingStyle($avgLength, $formalityScore, $usesEmojis),
        ];
    }

    /**
     * Obtener contactos más frecuentes
     */
    protected function getTopContacts(User $user): array
    {
        // Contactos que te envían más emails (OPTIMIZADO)
        // Usamos whereNull o where != 'sent' de forma eficiente
        $topSenders = Email::where('user_id', $user->id)
            ->whereNotNull('from_email')
            ->where(function($query) {
                $query->whereNull('status')
                      ->orWhere('status', '!=', 'sent');
            })
            ->select('from_email', 'from_name', DB::raw('COUNT(*) as email_count'))
            ->groupBy('from_email', 'from_name')
            ->orderByDesc('email_count')
            ->limit(10)
            ->get()
            ->map(function ($contact) {
                return [
                    'email' => $contact->from_email,
                    'name' => $contact->from_name,
                    'email_count' => $contact->email_count,
                ];
            })
            ->toArray();

        // Contactos a los que más escribes (OPTIMIZADO)
        $topRecipients = Email::where('user_id', $user->id)
            ->where('status', '=', 'sent')
            ->whereNotNull('to_email')
            ->select('to_email', DB::raw('COUNT(*) as email_count'))
            ->groupBy('to_email')
            ->orderByDesc('email_count')
            ->limit(10)
            ->get()
            ->map(function ($contact) {
                return [
                    'email' => $contact->to_email,
                    'email_count' => $contact->email_count,
                ];
            })
            ->toArray();

        return [
            'top_senders' => $topSenders,
            'top_recipients' => $topRecipients,
        ];
    }

    /**
     * Analizar comportamiento de respuesta
     */
    protected function analyzeResponseBehavior(User $user): array
    {
        // OPTIMIZADO: Solo analizar últimos 200 emails para evitar miles de queries
        $receivedEmails = Email::where('user_id', $user->id)
            ->where(function($query) {
                $query->whereNull('status')
                      ->orWhere('status', '!=', 'sent');
            })
            ->orderByDesc('received_at')
            ->limit(200)
            ->get();

        $totalReceived = Email::where('user_id', $user->id)
            ->where(function($query) {
                $query->whereNull('status')
                      ->orWhere('status', '!=', 'sent');
            })
            ->count();

        $respondedCount = 0;
        $responseTimes = [];

        foreach ($receivedEmails as $received) {
            // Buscar respuesta (email enviado después a ese remitente)
            $response = Email::where('user_id', $user->id)
                ->where('status', 'sent')
                ->where('to_email', $received->from_email)
                ->where('received_at', '>', $received->received_at)
                ->orderBy('received_at')
                ->first();

            if ($response) {
                $respondedCount++;
                $hoursToRespond = $received->received_at->diffInHours($response->received_at);
                $responseTimes[] = $hoursToRespond;
            }
        }

        $responseRate = $receivedEmails->count() > 0
            ? round(($respondedCount / $receivedEmails->count()) * 100, 2)
            : 0;

        $avgResponseTime = !empty($responseTimes)
            ? round(array_sum($responseTimes) / count($responseTimes), 2)
            : null;

        return [
            'total_received' => $totalReceived,
            'responded_to' => $respondedCount,
            'response_rate' => $responseRate,
            'avg_response_time_hours' => $avgResponseTime,
            'response_speed' => $this->determineResponseSpeed($avgResponseTime),
        ];
    }

    /**
     * Categorizar tipos de emails
     */
    protected function categorizeEmailTypes(User $user): array
    {
        $emails = Email::where('user_id', $user->id)
            ->where('vera_processed', true)
            ->get();

        $categories = [
            'newsletters' => 0,
            'notifications' => 0,
            'personal' => 0,
            'business' => 0,
            'spam' => 0,
            'requires_response' => 0,
            'archived' => 0,
        ];

        foreach ($emails as $email) {
            // Basarse en análisis de Vera
            $summary = strtolower($email->vera_summary ?? '');
            $action = $email->vera_action_required;

            if (str_contains($summary, 'newsletter') || str_contains($summary, 'boletín')) {
                $categories['newsletters']++;
            } elseif (str_contains($summary, 'notificación') || str_contains($summary, 'notification')) {
                $categories['notifications']++;
            } elseif (str_contains($summary, 'personal')) {
                $categories['personal']++;
            } elseif (str_contains($summary, 'negocio') || str_contains($summary, 'business')) {
                $categories['business']++;
            }

            if ($action === 'reply') {
                $categories['requires_response']++;
            }

            if ($email->is_archived) {
                $categories['archived']++;
            }
        }

        $total = $emails->count();
        $percentages = [];
        foreach ($categories as $type => $count) {
            $percentages[$type] = $total > 0 ? round(($count / $total) * 100, 2) : 0;
        }

        return [
            'counts' => $categories,
            'percentages' => $percentages,
            'total_analyzed' => $total,
        ];
    }

    /**
     * Generar conclusiones inteligentes basadas en el análisis
     */
    protected function generateConclusions(array $report): array
    {
        $conclusions = [];

        $stats = $report['email_statistics'];
        $writing = $report['writing_style'];
        $response = $report['response_behavior'];
        $types = $report['email_types'];

        // Conclusión 1: Volumen de comunicación
        if ($stats['ratio_received_sent'] > 10) {
            $conclusions[] = [
                'type' => 'communication_volume',
                'level' => 'high',
                'message' => "Recibes {$stats['ratio_received_sent']}x más emails de los que envías. La mayoría son emails entrantes, no conversaciones bidireccionales.",
                'suggestion' => 'Vera se enfocará en filtrar y archivar emails que no requieren respuesta.',
            ];
        } elseif ($stats['ratio_received_sent'] > 3) {
            $conclusions[] = [
                'type' => 'communication_volume',
                'level' => 'medium',
                'message' => "Recibes {$stats['ratio_received_sent']}x más emails de los que envías. Mix entre notificaciones y conversaciones.",
                'suggestion' => 'Vera balanceará entre filtrado automático y asistencia en respuestas.',
            ];
        } else {
            $conclusions[] = [
                'type' => 'communication_volume',
                'level' => 'balanced',
                'message' => "Tu comunicación es equilibrada (ratio {$stats['ratio_received_sent']}:1). Tienes conversaciones bidireccionales frecuentes.",
                'suggestion' => 'Vera se enfocará en ayudarte a responder más rápido manteniendo tu estilo.',
            ];
        }

        // Conclusión 2: Estilo de escritura
        if ($writing['has_sent_emails']) {
            $tone = $writing['tone'];
            $conclusions[] = [
                'type' => 'writing_style',
                'level' => 'identified',
                'message' => "Tu estilo de escritura es {$tone}. " .
                    ($writing['uses_emojis'] ? "Usas emojis ocasionalmente. " : "No usas emojis. ") .
                    "Emails de ~{$writing['avg_email_length']} caracteres.",
                'suggestion' => "Vera imitará este estilo en sus respuestas automáticas.",
            ];
        } else {
            $conclusions[] = [
                'type' => 'writing_style',
                'level' => 'unknown',
                'message' => "No hay emails enviados para analizar tu estilo de escritura.",
                'suggestion' => 'Vera usará un estilo neutral hasta que envíes algunos emails.',
            ];
        }

        // Conclusión 3: Tasa de respuesta
        if ($response['response_rate'] < 20) {
            $conclusions[] = [
                'type' => 'response_behavior',
                'level' => 'low',
                'message' => "Solo respondes al {$response['response_rate']}% de emails recibidos. La mayoría no requieren respuesta.",
                'suggestion' => 'Vera será muy selectiva al sugerir respuestas, solo para emails importantes.',
            ];
        } elseif ($response['response_rate'] > 50) {
            $conclusions[] = [
                'type' => 'response_behavior',
                'level' => 'high',
                'message' => "Respondes al {$response['response_rate']}% de emails recibidos. Eres muy activo.",
                'suggestion' => 'Vera te ayudará a responder más rápido generando borradores frecuentemente.',
            ];
        }

        // Conclusión 4: Tipos de emails
        if ($types['percentages']['newsletters'] > 30) {
            $conclusions[] = [
                'type' => 'email_types',
                'level' => 'high_newsletters',
                'message' => "{$types['percentages']['newsletters']}% de tus emails son newsletters. Generan mucho ruido.",
                'suggestion' => 'Vera activará reglas agresivas para archivar newsletters automáticamente.',
            ];
        }

        return $conclusions;
    }

    /**
     * Generar configuración recomendada
     */
    protected function generateRecommendedSettings(array $report): array
    {
        $stats = $report['email_statistics'];
        $writing = $report['writing_style'];
        $response = $report['response_behavior'];

        $settings = [
            'auto_archive_newsletters' => true, // Siempre activado
            'auto_response_enabled' => false,
            'auto_response_confidence_threshold' => 0.85,
            'response_style' => 'balanced',
            're_analysis_frequency_days' => 30,
        ];

        // Determinar si activar respuestas automáticas
        if ($writing['has_sent_emails'] && $response['response_rate'] > 30) {
            $settings['auto_response_enabled'] = true;
        }

        // Ajustar umbral de confianza
        if ($response['response_rate'] > 60) {
            $settings['auto_response_confidence_threshold'] = 0.75; // Más permisivo
        }

        // Determinar estilo de respuesta (solo si hay emails enviados)
        if ($writing['has_sent_emails'] && isset($writing['formality_score'])) {
            if ($writing['formality_score'] > 0.7) {
                $settings['response_style'] = 'formal';
            } elseif ($writing['formality_score'] < 0.3) {
                $settings['response_style'] = 'casual';
            }
        }

        return $settings;
    }

    // ================ HELPERS ================

    protected function isMostlyWeekday(array $byDayOfWeek): bool
    {
        $weekdayTotal = ($byDayOfWeek[2] ?? 0) + ($byDayOfWeek[3] ?? 0) + ($byDayOfWeek[4] ?? 0) + ($byDayOfWeek[5] ?? 0) + ($byDayOfWeek[6] ?? 0);
        $weekendTotal = ($byDayOfWeek[1] ?? 0) + ($byDayOfWeek[7] ?? 0);

        return $weekdayTotal > $weekendTotal * 2;
    }

    protected function isMostlyBusinessHours(array $byHourOfDay): bool
    {
        $businessHoursTotal = 0;
        for ($h = 9; $h <= 17; $h++) {
            $businessHoursTotal += $byHourOfDay[$h] ?? 0;
        }

        $totalEmails = array_sum($byHourOfDay);

        return $businessHoursTotal > ($totalEmails * 0.6);
    }

    protected function determineTone(float $formalityScore): string
    {
        if ($formalityScore > 0.7) return 'formal';
        if ($formalityScore > 0.4) return 'profesional';
        if ($formalityScore > 0.2) return 'casual-profesional';
        return 'casual';
    }

    protected function determineWritingStyle(int $avgLength, float $formalityScore, bool $usesEmojis): string
    {
        if ($avgLength > 1000 && $formalityScore > 0.6) return 'formal y detallado';
        if ($avgLength > 1000) return 'detallado y explicativo';
        if ($avgLength > 500 && $formalityScore > 0.6) return 'profesional y estructurado';
        if ($avgLength > 500) return 'balanceado';
        if ($avgLength > 200 && $usesEmojis) return 'conciso y amigable';
        if ($avgLength > 200) return 'conciso y profesional';
        return 'muy breve y directo';
    }

    protected function determineResponseSpeed(?float $avgHours): string
    {
        if ($avgHours === null) return 'desconocido';
        if ($avgHours < 2) return 'muy rápido (< 2 horas)';
        if ($avgHours < 8) return 'rápido (< 8 horas)';
        if ($avgHours < 24) return 'mismo día';
        if ($avgHours < 48) return '1-2 días';
        return 'más de 2 días';
    }
}
