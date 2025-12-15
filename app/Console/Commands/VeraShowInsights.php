<?php

namespace App\Console\Commands;

use App\Services\VeraInsightsService;
use Illuminate\Console\Command;

class VeraShowInsights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vera:insights {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar insights inteligentes generados por Vera';

    /**
     * Execute the console command.
     */
    public function handle(VeraInsightsService $insightsService)
    {
        $user = \App\Models\User::first(); // TODO: Obtener del contexto

        if (!$user) {
            $this->error('No se encontró ningún usuario');
            return 1;
        }

        $this->info('🤖 Vera - Análisis Inteligente de Emails');
        $this->newLine();
        $this->info('Generando insights...');
        $this->newLine();

        $insights = $insightsService->generateInsights($user);

        if (!$insights['success']) {
            $this->warn($insights['message']);
            return 0;
        }

        // Opción JSON
        if ($this->option('json')) {
            $this->line(json_encode($insights, JSON_PRETTY_PRINT));
            return 0;
        }

        // Mostrar insights en formato bonito
        $this->displayInsights($insights);

        return 0;
    }

    /**
     * Mostrar insights en formato bonito
     */
    protected function displayInsights(array $insights): void
    {
        // Header
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("   📊 INFORME DE ANÁLISIS VERA");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        // Total analizado
        $this->info("✅ Emails analizados: " . $insights['total_analyzed']);
        $this->newLine(2);

        // Top Senders
        $this->displayTopSenders($insights['top_senders']);
        $this->newLine(2);

        // Categories
        $this->displayCategories($insights['categories']);
        $this->newLine(2);

        // Sentiment
        $this->displaySentiment($insights['sentiment_analysis']);
        $this->newLine(2);

        // Actions Required
        $this->displayActionsRequired($insights['action_required']);
        $this->newLine(2);

        // Time Patterns
        $this->displayTimePatterns($insights['time_patterns']);
        $this->newLine(2);

        // Automation Suggestions
        $this->displayAutomationSuggestions($insights['automation_suggestions']);

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }

    /**
     * Mostrar top remitentes
     */
    protected function displayTopSenders(array $senders): void
    {
        $this->info("📧 TOP 10 REMITENTES MÁS FRECUENTES");
        $this->info("─────────────────────────────────────────────────────────");

        $topTen = array_slice($senders, 0, 10);
        $rows = [];

        foreach ($topTen as $index => $sender) {
            $rows[] = [
                '#' . ($index + 1),
                $sender['name'] ?: $sender['email'],
                $sender['count'],
                $sender['sentiment_label'],
                $sender['most_common_category'] ?? 'N/A',
            ];
        }

        $this->table(
            ['#', 'Remitente', 'Emails', 'Sentimiento', 'Categoría'],
            $rows
        );
    }

    /**
     * Mostrar categorías
     */
    protected function displayCategories(array $categories): void
    {
        $this->info("📁 DISTRIBUCIÓN POR CATEGORÍAS");
        $this->info("─────────────────────────────────────────────────────────");

        $rows = [];
        foreach ($categories as $category) {
            $rows[] = [
                $category['category'],
                $category['count'],
                $category['percentage'] . '%',
                $this->getProgressBar($category['percentage']),
            ];
        }

        $this->table(
            ['Categoría', 'Cantidad', '%', 'Distribución'],
            $rows
        );
    }

    /**
     * Mostrar análisis de sentimiento
     */
    protected function displaySentiment(array $sentiment): void
    {
        $this->info("😊 ANÁLISIS DE SENTIMIENTO");
        $this->info("─────────────────────────────────────────────────────────");

        $this->line("Promedio general: <fg=cyan>{$sentiment['average']}</> ({$sentiment['label']})");
        $this->newLine();

        $this->table(
            ['Sentimiento', 'Cantidad'],
            [
                ['Positivo', $sentiment['distribution']['positive']],
                ['Neutral', $sentiment['distribution']['neutral']],
                ['Negativo', $sentiment['distribution']['negative']],
            ]
        );

        if ($sentiment['most_positive_sender']) {
            $this->line("");
            $this->line("👍 Remitente más positivo: <fg=green>{$sentiment['most_positive_sender']['name']}</> ({$sentiment['most_positive_sender']['email']})");
        }

        if ($sentiment['most_negative_sender']) {
            $this->line("👎 Remitente más negativo: <fg=red>{$sentiment['most_negative_sender']['name']}</> ({$sentiment['most_negative_sender']['email']})");
        }
    }

    /**
     * Mostrar acciones requeridas
     */
    protected function displayActionsRequired(array $actions): void
    {
        $this->info("⚡ ACCIONES REQUERIDAS");
        $this->info("─────────────────────────────────────────────────────────");

        $rows = [];
        foreach ($actions as $action) {
            $rows[] = [
                $this->getActionLabel($action['action']),
                $action['count'],
            ];
        }

        $this->table(
            ['Acción', 'Cantidad'],
            $rows
        );
    }

    /**
     * Mostrar patrones temporales
     */
    protected function displayTimePatterns(array $patterns): void
    {
        $this->info("⏰ PATRONES TEMPORALES");
        $this->info("─────────────────────────────────────────────────────────");

        $this->line("Día más activo: <fg=cyan>{$patterns['busiest_day']}</>");
        $this->line("Hora más activa: <fg=cyan>{$patterns['busiest_hour']}:00</>");
    }

    /**
     * Mostrar sugerencias de automatización
     */
    protected function displayAutomationSuggestions(array $suggestions): void
    {
        $this->info("💡 SUGERENCIAS DE AUTOMATIZACIÓN");
        $this->info("─────────────────────────────────────────────────────────");

        if (empty($suggestions)) {
            $this->line("No hay sugerencias en este momento.");
            return;
        }

        foreach ($suggestions as $index => $suggestion) {
            $priority = $suggestion['priority'] === 'high' ? '<fg=red>ALTA</>' : '<fg=yellow>MEDIA</>';
            $this->line("");
            $this->line("<fg=cyan>" . ($index + 1) . ".</> {$suggestion['title']} [Prioridad: {$priority}]");
            $this->line("   {$suggestion['description']}");
            $this->line("   <fg=green>→</> Acción sugerida: {$this->getActionSuggestionLabel($suggestion['suggested_action'])}");
        }
    }

    /**
     * Generar barra de progreso visual
     */
    protected function getProgressBar(float $percentage): string
    {
        $filled = (int) round($percentage / 5); // Cada bloque = 5%
        $empty = 20 - $filled;

        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }

    /**
     * Label para acción
     */
    protected function getActionLabel(string $action): string
    {
        return match($action) {
            'reply' => '📤 Responder',
            'schedule' => '📅 Agendar',
            'forward' => '➡️  Reenviar',
            'archive' => '📦 Archivar',
            'none' => '⭕ Sin acción',
            default => $action,
        };
    }

    /**
     * Label para sugerencia de acción
     */
    protected function getActionSuggestionLabel(string $action): string
    {
        return match($action) {
            'auto_categorize' => 'Categorizar automáticamente',
            'auto_response' => 'Configurar respuestas automáticas',
            'auto_reply_suggestions' => 'Activar sugerencias de respuesta',
            'auto_prioritize' => 'Priorizar automáticamente',
            default => $action,
        };
    }
}
