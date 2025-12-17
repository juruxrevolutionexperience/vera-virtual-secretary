<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * VeraReportGenerator
 *
 * Genera reportes visuales y claros del análisis inteligente de Vera
 */
class VeraReportGenerator
{
    /**
     * Generar reporte completo en formato texto para CLI
     */
    public function generateCliReport(array $analysis): string
    {
        $output = [];

        $output[] = $this->getHeader();
        $output[] = $this->getEmailStatistics($analysis['email_statistics']);
        $output[] = $this->getCommunicationPatterns($analysis['communication_patterns']);

        if ($analysis['writing_style']['has_sent_emails']) {
            $output[] = $this->getWritingStyle($analysis['writing_style']);
        }

        $output[] = $this->getTopContacts($analysis['top_contacts']);
        $output[] = $this->getResponseBehavior($analysis['response_behavior']);

        if ($analysis['email_types']['total_analyzed'] > 0) {
            $output[] = $this->getEmailTypes($analysis['email_types']);
        }

        $output[] = $this->getConclusions($analysis['conclusions']);
        $output[] = $this->getRecommendedSettings($analysis['recommended_settings']);
        $output[] = $this->getFooter();

        return implode("\n\n", $output);
    }

    protected function getHeader(): string
    {
        return <<<HEADER
╔══════════════════════════════════════════════════════════════════════╗
║                     REPORTE DE ANÁLISIS INTELIGENTE                  ║
║                            VERA AI ASSISTANT                         ║
╚══════════════════════════════════════════════════════════════════════╝
HEADER;
    }

    protected function getEmailStatistics(array $stats): string
    {
        $receivedBar = $this->createBar($stats['received_emails'], $stats['total_emails']);
        $sentBar = $this->createBar($stats['sent_emails'], $stats['total_emails']);

        return <<<STATS
┌─────────────────────────────────────────────────────────────────────┐
│  📊 ESTADÍSTICAS GENERALES                                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Total de emails analizados:  {$stats['total_emails']}                                       │
│  Período de análisis:         {$stats['first_email_date']} → {$stats['last_email_date']}    │
│  Días con datos:              {$stats['days_of_data']} días                                  │
│  Promedio por día:            {$stats['avg_emails_per_day']} emails/día                      │
│                                                                      │
│  📥 Emails recibidos:  {$stats['received_emails']}  {$receivedBar}                            │
│  📤 Emails enviados:   {$stats['sent_emails']}   {$sentBar}                                   │
│                                                                      │
│  📈 Ratio recibidos/enviados: {$stats['ratio_received_sent']}:1                               │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
STATS;
    }

    protected function getCommunicationPatterns(array $patterns): string
    {
        $weekdayEmoji = $patterns['is_mostly_weekday'] ? '💼' : '🏖️';
        $businessEmoji = $patterns['is_mostly_business_hours'] ? '⏰' : '🌙';

        return <<<PATTERNS
┌─────────────────────────────────────────────────────────────────────┐
│  📅 PATRONES DE COMUNICACIÓN                                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Día más activo:     {$patterns['peak_day']}                                               │
│  Hora más activa:    {$patterns['peak_hour']}                                              │
│                                                                      │
│  {$weekdayEmoji} Mayoría de emails:  {$this->formatBoolean($patterns['is_mostly_weekday'], 'Entre semana', 'Fines de semana')} │
│  {$businessEmoji} Horario principal:  {$this->formatBoolean($patterns['is_mostly_business_hours'], 'Horario laboral (9-17h)', 'Fuera de horario')} │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
PATTERNS;
    }

    protected function getWritingStyle(array $style): string
    {
        $greetings = implode(', ', $style['top_greetings']);
        $closings = implode(', ', $style['top_closings']);
        $emojiEmoji = $style['uses_emojis'] ? '😊' : '🚫';

        return <<<STYLE
┌─────────────────────────────────────────────────────────────────────┐
│  ✍️  TU ESTILO DE ESCRITURA                                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  📝 Emails analizados:       {$style['total_sent']} emails enviados                        │
│  📏 Longitud promedio:       {$style['avg_email_length']} caracteres                       │
│  🎭 Tono general:            {$style['tone']}                                              │
│  📊 Nivel de formalidad:    {$this->formatFormalityScore($style['formality_score'])}   │
│  {$emojiEmoji} Uso de emojis:         {$this->formatBoolean($style['uses_emojis'], 'Sí', 'No')} ({$style['emoji_frequency']} por email)     │
│                                                                      │
│  👋 Saludos típicos:         {$greetings}                           │
│  👋 Despedidas típicas:      {$closings}                            │
│                                                                      │
│  💬 Estilo identificado:     {$style['writing_style']}              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
STYLE;
    }

    protected function getTopContacts(array $contacts): string
    {
        $sendersText = $this->formatContactList($contacts['top_senders'], 'top_senders');
        $recipientsText = $this->formatContactList($contacts['top_recipients'], 'top_recipients');

        return <<<CONTACTS
┌─────────────────────────────────────────────────────────────────────┐
│  👥 CONTACTOS PRINCIPALES                                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  📥 Top 5 remitentes (quienes te escriben más):                    │
{$sendersText}
│                                                                      │
│  📤 Top 5 destinatarios (a quienes escribes más):                  │
{$recipientsText}
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
CONTACTS;
    }

    protected function getResponseBehavior(array $response): string
    {
        $responseBar = $this->createProgressBar($response['response_rate'], 100);

        return <<<RESPONSE
┌─────────────────────────────────────────────────────────────────────┐
│  ⚡ COMPORTAMIENTO DE RESPUESTA                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  📨 Emails recibidos:           {$response['total_received']}                              │
│  ✅ Emails respondidos:         {$response['responded_to']}                                │
│                                                                      │
│  📊 Tasa de respuesta:          {$response['response_rate']}%                              │
│  {$responseBar}                                                      │
│                                                                      │
│  ⏱️  Tiempo promedio:           {$response['avg_response_time_hours']} horas ({$response['response_speed']})  │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
RESPONSE;
    }

    protected function getEmailTypes(array $types): string
    {
        $counts = $types['counts'];
        $percentages = $types['percentages'];

        return <<<TYPES
┌─────────────────────────────────────────────────────────────────────┐
│  📂 TIPOS DE EMAILS                                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  📰 Newsletters:          {$counts['newsletters']} ({$percentages['newsletters']}%)                           │
│  🔔 Notificaciones:       {$counts['notifications']} ({$percentages['notifications']}%)                       │
│  👤 Personal:             {$counts['personal']} ({$percentages['personal']}%)                                 │
│  💼 Negocios:             {$counts['business']} ({$percentages['business']}%)                                 │
│  🗑️  Spam:                {$counts['spam']} ({$percentages['spam']}%)                                         │
│  ✉️  Requiere respuesta:  {$counts['requires_response']} ({$percentages['requires_response']}%)              │
│  📥 Archivados:           {$counts['archived']} ({$percentages['archived']}%)                                 │
│                                                                      │
│  Total analizado: {$types['total_analyzed']} emails                                       │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
TYPES;
    }

    protected function getConclusions(array $conclusions): string
    {
        $lines = [
            '┌─────────────────────────────────────────────────────────────────────┐',
            '│  🧠 CONCLUSIONES INTELIGENTES                                       │',
            '├─────────────────────────────────────────────────────────────────────┤',
            '│                                                                      │',
        ];

        foreach ($conclusions as $i => $conclusion) {
            $num = $i + 1;
            $icon = $this->getIconForConclusionType($conclusion['type']);

            $lines[] = "│  {$icon} CONCLUSIÓN {$num}:                                                     │";
            $lines[] = "│  " . $this->wrapText($conclusion['message'], 68) . "│";
            $lines[] = "│                                                                      │";
            $lines[] = "│  💡 " . $this->wrapText($conclusion['suggestion'], 66) . "│";

            if ($i < count($conclusions) - 1) {
                $lines[] = "│  ────────────────────────────────────────────────────────────────  │";
            }
        }

        $lines[] = '│                                                                      │';
        $lines[] = '└─────────────────────────────────────────────────────────────────────┘';

        return implode("\n", $lines);
    }

    protected function getRecommendedSettings(array $settings): string
    {
        $autoArchive = $settings['auto_archive_newsletters'] ? '✅ Activado' : '❌ Desactivado';
        $autoResponse = $settings['auto_response_enabled'] ? '✅ Activado' : '❌ Desactivado';
        $threshold = ($settings['auto_response_confidence_threshold'] * 100) . '%';

        return <<<SETTINGS
┌─────────────────────────────────────────────────────────────────────┐
│  ⚙️  CONFIGURACIÓN RECOMENDADA                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  📥 Auto-archivar newsletters:      {$autoArchive}                  │
│  🤖 Respuestas automáticas:         {$autoResponse}                 │
│  🎯 Umbral de confianza:           {$threshold}                     │
│  📝 Estilo de respuesta:           {$settings['response_style']}    │
│  🔄 Re-análisis cada:              {$settings['re_analysis_frequency_days']} días           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
SETTINGS;
    }

    protected function getFooter(): string
    {
        return <<<FOOTER
╔══════════════════════════════════════════════════════════════════════╗
║  📋 PRÓXIMO PASO:                                                    ║
║                                                                      ║
║  Ejecuta el onboarding para configurar Vera con estos insights:     ║
║  ./vendor/bin/sail artisan vera:onboarding                          ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
FOOTER;
    }

    // ================ HELPERS ================

    protected function createBar(int $value, int $total, int $length = 20): string
    {
        if ($total === 0) return str_repeat('░', $length);

        $filled = round(($value / $total) * $length);
        return str_repeat('█', $filled) . str_repeat('░', $length - $filled);
    }

    protected function createProgressBar(float $percentage, float $max, int $length = 30): string
    {
        $filled = round(($percentage / $max) * $length);
        return str_repeat('█', $filled) . str_repeat('░', $length - $filled) . " {$percentage}%";
    }

    protected function formatBoolean(bool $value, string $trueText, string $falseText): string
    {
        return $value ? $trueText : $falseText;
    }

    protected function formatFormalityScore(float $score): string
    {
        $percentage = round($score * 100);
        $bars = round($score * 10);
        $bar = str_repeat('█', $bars) . str_repeat('░', 10 - $bars);

        return "{$bar} {$percentage}%";
    }

    protected function formatContactList(array $contacts, string $type): string
    {
        $lines = [];
        $topContacts = array_slice($contacts, 0, 5);

        foreach ($topContacts as $i => $contact) {
            $num = $i + 1;
            $email = $contact['email'];
            $count = $contact['email_count'];
            $name = $contact['name'] ?? '';

            if ($type === 'top_senders' && $name) {
                $lines[] = "│     {$num}. {$name} <{$email}> - {$count} emails";
            } else {
                $lines[] = "│     {$num}. {$email} - {$count} emails";
            }
        }

        return implode("\n", $lines);
    }

    protected function wrapText(string $text, int $width): string
    {
        // Simple text wrapping - para mejorar podríamos usar wordwrap
        if (strlen($text) <= $width) {
            return str_pad($text, $width) . ' ';
        }

        return substr($text, 0, $width - 3) . '...';
    }

    protected function getIconForConclusionType(string $type): string
    {
        return match($type) {
            'communication_volume' => '📊',
            'writing_style' => '✍️',
            'response_behavior' => '⚡',
            'email_types' => '📂',
            default => '💡',
        };
    }
}
