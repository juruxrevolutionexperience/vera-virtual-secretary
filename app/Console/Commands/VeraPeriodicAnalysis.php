<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VeraConfig;
use App\Services\VeraIntelligentAnalyzer;
use Illuminate\Console\Command;

class VeraPeriodicAnalysis extends Command
{
    protected $signature = 'vera:periodic-analysis
                            {--user-id=1 : ID del usuario}
                            {--force : Forzar análisis aunque no hayan pasado los días configurados}
                            {--auto-update : Actualizar configuración automáticamente si hay cambios significativos}';

    protected $description = 'Análisis periódico de Vera para detectar cambios en patrones de email';

    protected VeraIntelligentAnalyzer $analyzer;

    public function __construct()
    {
        parent::__construct();
        $this->analyzer = new VeraIntelligentAnalyzer();
    }

    public function handle()
    {
        $userId = $this->option('user-id');
        $force = $this->option('force');
        $autoUpdate = $this->option('auto-update');

        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuario no encontrado: {$userId}");
            return Command::FAILURE;
        }

        $config = VeraConfig::where('user_id', $userId)->first();

        if (!$config) {
            $this->warn('⚠️  No hay configuración de Vera para este usuario.');
            $this->info('💡 Ejecuta el onboarding primero:');
            $this->line('   ./vendor/bin/sail artisan vera:onboarding');
            return Command::FAILURE;
        }

        // Verificar si necesita re-análisis
        if (!$force && !$config->needsReAnalysis()) {
            $lastAnalysis = $config->last_re_analysis_at ?? $config->last_analysis_at;
            $nextAnalysis = $lastAnalysis->addDays($config->re_analysis_frequency_days);

            $this->info('✅ No es necesario re-analizar todavía');
            $this->line("   Último análisis: {$lastAnalysis->format('Y-m-d')}");
            $this->line("   Próximo análisis: {$nextAnalysis->format('Y-m-d')}");
            $this->newLine();
            $this->line('💡 Usa --force para forzar el re-análisis ahora');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('🔄 Iniciando análisis periódico...');
        $this->newLine();

        // Ejecutar análisis
        $analysis = $this->analyzer->analyzeUser($user);

        // Comparar con configuración actual
        $changes = $this->detectChanges($config, $analysis);

        if (empty($changes)) {
            $this->info('✅ No se detectaron cambios significativos en tus patrones de email');
            $config->update(['last_re_analysis_at' => now()]);
            return Command::SUCCESS;
        }

        // Mostrar cambios detectados
        $this->displayChanges($changes);

        // Sugerir actualizaciones
        $suggestions = $this->generateSuggestions($changes, $analysis);
        $this->displaySuggestions($suggestions);

        // Auto-actualizar o preguntar
        if ($autoUpdate) {
            $this->applyUpdates($config, $suggestions);
            $this->info('✅ Configuración actualizada automáticamente');
        } else {
            if ($this->confirm('¿Quieres actualizar la configuración con estas sugerencias?', true)) {
                $this->applyUpdates($config, $suggestions);
                $this->info('✅ Configuración actualizada');
            } else {
                $this->line('ℹ️  Configuración no modificada');
            }
        }

        $config->update(['last_re_analysis_at' => now()]);

        return Command::SUCCESS;
    }

    protected function detectChanges(VeraConfig $config, array $analysis): array
    {
        $changes = [];

        // Comparar tasa de respuesta
        $currentResponseEnabled = $config->auto_response_enabled;
        $recommendedEnabled = $analysis['recommended_settings']['auto_response_enabled'];

        if ($currentResponseEnabled !== $recommendedEnabled) {
            $changes['auto_response_enabled'] = [
                'current' => $currentResponseEnabled,
                'recommended' => $recommendedEnabled,
                'reason' => $recommendedEnabled
                    ? 'Tu tasa de respuesta ha aumentado'
                    : 'Tu tasa de respuesta ha disminuido',
            ];
        }

        // Comparar umbral de confianza
        $currentThreshold = (float) $config->confidence_threshold;
        $recommendedThreshold = $analysis['recommended_settings']['auto_response_confidence_threshold'];

        if (abs($currentThreshold - $recommendedThreshold) >= 0.1) {
            $changes['confidence_threshold'] = [
                'current' => $currentThreshold,
                'recommended' => $recommendedThreshold,
                'reason' => $recommendedThreshold > $currentThreshold
                    ? 'Deberías ser más conservador con respuestas automáticas'
                    : 'Puedes ser más permisivo, tu comportamiento lo justifica',
            ];
        }

        // Comparar estilo de escritura (si hay emails enviados nuevos)
        if ($analysis['writing_style']['has_sent_emails']) {
            $currentTone = $config->writing_style['tone'] ?? 'desconocido';
            $detectedTone = $analysis['writing_style']['tone'];

            if ($currentTone !== $detectedTone) {
                $changes['writing_tone'] = [
                    'current' => $currentTone,
                    'detected' => $detectedTone,
                    'reason' => 'Tu estilo de escritura ha cambiado',
                ];
            }
        }

        return $changes;
    }

    protected function displayChanges(array $changes)
    {
        $this->newLine();
        $this->info('🔍 CAMBIOS DETECTADOS:');
        $this->newLine();

        foreach ($changes as $key => $change) {
            $this->line("  📌 {$this->formatChangeKey($key)}:");
            $this->line("     Actual: {$this->formatValue($change['current'] ?? $change['detected'])}");
            $this->line("     Recomendado: {$this->formatValue($change['recommended'] ?? $change['detected'])}");
            $this->line("     Razón: {$change['reason']}");
            $this->newLine();
        }
    }

    protected function generateSuggestions(array $changes, array $analysis): array
    {
        $suggestions = [];

        foreach ($changes as $key => $change) {
            $suggestions[$key] = $change['recommended'] ?? $change['detected'];
        }

        return $suggestions;
    }

    protected function displaySuggestions(array $suggestions)
    {
        $this->info('💡 SUGERENCIAS:');
        $this->newLine();

        foreach ($suggestions as $key => $value) {
            $this->line("  ✓ Actualizar {$this->formatChangeKey($key)} a: {$this->formatValue($value)}");
        }

        $this->newLine();
    }

    protected function applyUpdates(VeraConfig $config, array $suggestions)
    {
        $updates = [];

        foreach ($suggestions as $key => $value) {
            $updates[$key] = $value;
        }

        $config->update($updates);
    }

    protected function formatChangeKey(string $key): string
    {
        return match($key) {
            'auto_response_enabled' => 'Respuestas automáticas',
            'confidence_threshold' => 'Umbral de confianza',
            'writing_tone' => 'Tono de escritura',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    protected function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Activado' : 'Desactivado';
        }

        if (is_float($value)) {
            return round($value * 100) . '%';
        }

        return (string) $value;
    }
}
