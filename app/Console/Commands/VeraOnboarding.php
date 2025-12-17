<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VeraIntelligentAnalyzer;
use App\Services\VeraReportGenerator;
use App\Services\VeraOnboardingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VeraOnboarding extends Command
{
    protected $signature = 'vera:onboarding
                            {--user-id=1 : ID del usuario}
                            {--skip-analysis : Saltar análisis y usar reporte previo}
                            {--show-report-only : Solo mostrar el reporte sin configurar}';

    protected $description = 'Configuración inteligente de Vera mediante análisis de emails y preguntas personalizadas';

    protected VeraIntelligentAnalyzer $analyzer;
    protected VeraReportGenerator $reportGenerator;
    protected VeraOnboardingService $onboardingService;

    public function __construct()
    {
        parent::__construct();
        $this->analyzer = new VeraIntelligentAnalyzer();
        $this->reportGenerator = new VeraReportGenerator();
        $this->onboardingService = new VeraOnboardingService();
    }

    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuario no encontrado: {$userId}");
            return Command::FAILURE;
        }

        $this->displayWelcome();

        // PASO 1: Análisis inteligente
        $analysis = $this->runAnalysis($user);

        if (!$analysis) {
            $this->error('Error al analizar emails');
            return Command::FAILURE;
        }

        // PASO 2: Mostrar reporte
        $this->displayReport($analysis);

        if ($this->option('show-report-only')) {
            $this->info("\n✅ Reporte generado exitosamente");
            return Command::SUCCESS;
        }

        // PASO 3: Preguntar si quiere continuar
        if (!$this->confirm('¿Quieres continuar con la configuración de Vera?', true)) {
            $this->info('Configuración cancelada. Puedes ejecutar el onboarding nuevamente cuando quieras.');
            return Command::SUCCESS;
        }

        // PASO 4: Hacer preguntas inteligentes
        $answers = $this->askQuestions($analysis);

        // PASO 5: Procesar respuestas y generar configuración
        $config = $this->onboardingService->processAnswers($answers, $analysis);

        // PASO 6: Guardar configuración
        $this->saveConfiguration($user, $config, $analysis);

        // PASO 7: Mostrar resumen
        $this->displaySummary($config);

        $this->newLine();
        $this->info('🎉 ¡Configuración completada! Vera está lista para trabajar.');
        $this->info('');
        $this->comment('Próximos pasos:');
        $this->line('  1. Vera comenzará a procesar emails automáticamente');
        $this->line('  2. Usa: ./vendor/bin/sail artisan vera:review-responses para revisar borradores');
        $this->line('  3. Usa: ./vendor/bin/sail artisan vera:stats para ver estadísticas');

        return Command::SUCCESS;
    }

    protected function displayWelcome()
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════════════╗');
        $this->line('║                    BIENVENIDO AL ONBOARDING DE VERA                 ║');
        $this->line('║                         Tu Asistente de Email IA                     ║');
        $this->line('╚══════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('🧠 Vera va a analizar tus emails para conocerte mejor y configurarse automáticamente.');
        $this->info('   Este proceso toma solo unos minutos.');
        $this->newLine();
    }

    protected function runAnalysis(User $user): ?array
    {
        if ($this->option('skip-analysis')) {
            $this->info('⏩ Saltando análisis, usando reporte previo...');
            return $this->loadPreviousAnalysis($user);
        }

        $this->info('🔍 Analizando tus emails...');
        $this->newLine();

        $bar = $this->output->createProgressBar(5);
        $bar->setFormat(' %current%/%max% [%bar%] %message%');

        $bar->setMessage('Analizando estadísticas generales...');
        $bar->start();
        sleep(1);

        $bar->advance();
        $bar->setMessage('Identificando patrones de comunicación...');
        sleep(1);

        $bar->advance();
        $bar->setMessage('Analizando tu estilo de escritura...');
        sleep(1);

        $bar->advance();
        $bar->setMessage('Procesando contactos frecuentes...');
        sleep(1);

        $bar->advance();
        $bar->setMessage('Generando conclusiones inteligentes...');

        // Ejecutar análisis real
        $analysis = $this->analyzer->analyzeUser($user);

        $bar->advance();
        $bar->setMessage('¡Análisis completado!');
        $bar->finish();

        $this->newLine(2);

        // Guardar análisis para referencia futura
        $this->saveAnalysis($user, $analysis);

        return $analysis;
    }

    protected function displayReport(array $analysis)
    {
        $this->info('📊 REPORTE DE ANÁLISIS');
        $this->newLine();

        $report = $this->reportGenerator->generateCliReport($analysis);
        $this->line($report);

        $this->newLine();
    }

    protected function askQuestions(array $analysis): array
    {
        $this->info('🤔 Ahora voy a hacerte algunas preguntas para configurar Vera de la mejor manera.');
        $this->newLine();

        $questions = $this->onboardingService->generateQuestions($analysis);
        $answers = [];

        foreach ($questions as $i => $question) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("PREGUNTA " . ($i + 1) . " de " . count($questions));
            $this->newLine();

            $answer = $this->askQuestion($question);
            $answers[$question['id']] = $answer;

            $this->newLine();
        }

        return $answers;
    }

    protected function askQuestion(array $question)
    {
        $this->line("<fg=yellow>{$question['question']}</>");

        if (isset($question['description'])) {
            $this->line("<fg=gray>{$question['description']}</>");
        }

        $this->newLine();

        return match($question['type']) {
            'choice' => $this->askChoiceQuestion($question),
            'multi_input' => $this->askMultiInputQuestion($question),
            'time_range' => $this->askTimeRangeQuestion($question),
            default => $this->ask($question['question']),
        };
    }

    protected function askChoiceQuestion(array $question)
    {
        $options = [];
        $descriptions = [];

        foreach ($question['options'] as $key => $option) {
            $options[$key] = $option['label'];
            $descriptions[$key] = $option['description'] ?? '';
        }

        // Mostrar opciones con descripciones
        foreach ($options as $key => $label) {
            $this->line("  <fg=cyan>[$key]</> {$label}");
            if ($descriptions[$key]) {
                $this->line("       <fg=gray>{$descriptions[$key]}</>");
            }
        }

        $this->newLine();

        $default = $question['default'] ?? array_key_first($options);
        $optionKeys = array_keys($options);

        $answer = $this->anticipate(
            'Tu elección',
            $optionKeys,
            $default
        );

        // Validar que la respuesta sea válida
        while (!in_array($answer, $optionKeys)) {
            $this->warn("Opción inválida. Por favor elige entre: " . implode(', ', $optionKeys));
            $answer = $this->anticipate('Tu elección', $optionKeys, $default);
        }

        return $answer;
    }

    protected function askMultiInputQuestion(array $question)
    {
        if (isset($question['suggestions']) && !empty($question['suggestions'])) {
            $this->line("  <fg=gray>Sugerencias basadas en tu historial:</>");
            foreach ($question['suggestions'] as $suggestion) {
                $this->line("    • {$suggestion}");
            }
            $this->newLine();
        }

        $this->line("  <fg=gray>Ingresa emails importantes, uno por línea.</>");
        $this->line("  <fg=gray>Presiona Enter en una línea vacía para terminar.</>");
        $this->newLine();

        $emails = [];
        while (true) {
            $email = $this->ask('Email (Enter para terminar)');
            if (empty($email)) {
                break;
            }

            // Validar email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
                $this->info("  ✓ {$email} agregado");
            } else {
                $this->warn("  ✗ Email inválido, intenta de nuevo");
            }
        }

        return $emails;
    }

    protected function askTimeRangeQuestion(array $question)
    {
        $this->line("  <fg=gray>Opciones predefinidas:</>");

        foreach ($question['options'] as $key => $option) {
            if ($key === 'custom') continue;

            $this->line("  <fg=cyan>[$key]</> {$option['label']} ({$option['start']} - {$option['end']})");
        }

        $this->newLine();

        $choice = $this->choice(
            '¿Quieres usar una opción predefinida o personalizar?',
            array_keys($question['options']),
            $question['default']
        );

        if ($choice === 'custom') {
            $start = $this->ask('Hora de inicio (HH:MM)', $question['default_start']);
            $end = $this->ask('Hora de fin (HH:MM)', $question['default_end']);

            return [
                'mode' => 'custom',
                'start' => $start,
                'end' => $end,
            ];
        }

        return $choice;
    }

    protected function saveConfiguration(User $user, array $config, array $analysis)
    {
        // Guardar configuración en la base de datos
        $veraConfig = \App\Models\VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'main_objective' => $config['main_objective'],
                'writing_style' => $config['writing_style'],
                'auto_response_enabled' => $config['auto_response']['enabled'],
                'auto_response_mode' => $config['auto_response']['mode'],
                'auto_response_require_approval' => $config['auto_response']['require_approval'],
                'confidence_threshold' => $config['confidence_threshold'],
                'newsletter_handling' => $config['newsletter_handling'],
                'important_contacts' => $config['important_contacts'],
                'availability_start' => $config['availability']['start'],
                'availability_end' => $config['availability']['end'],
                'autonomy_level' => $config['autonomy_level'],
                're_analysis_frequency_days' => $config['re_analysis_days'],
                'last_analysis_at' => now(),
                'analysis_summary' => [
                    'total_emails' => $analysis['email_statistics']['total_emails'],
                    'analyzed_at' => $analysis['analyzed_at'],
                ],
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]
        );

        $this->info("💾 Configuración guardada en base de datos (ID: {$veraConfig->id})");

        // También guardar en JSON como backup
        $configPath = "vera/config_user_{$user->id}.json";
        Storage::put($configPath, json_encode([
            'user_id' => $user->id,
            'config' => $config,
            'analysis_summary' => [
                'total_emails' => $analysis['email_statistics']['total_emails'],
                'analyzed_at' => $analysis['analyzed_at'],
            ],
            'created_at' => now()->toDateTimeString(),
        ], JSON_PRETTY_PRINT));
    }

    protected function saveAnalysis(User $user, array $analysis)
    {
        $analysisPath = "vera/analysis_user_{$user->id}.json";

        Storage::put($analysisPath, json_encode($analysis, JSON_PRETTY_PRINT));
    }

    protected function loadPreviousAnalysis(User $user): ?array
    {
        $analysisPath = "vera/analysis_user_{$user->id}.json";

        if (!Storage::exists($analysisPath)) {
            $this->warn('No hay análisis previo. Ejecutando análisis completo...');
            return $this->analyzer->analyzeUser($user);
        }

        $content = Storage::get($analysisPath);
        return json_decode($content, true);
    }

    protected function displaySummary(array $config)
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════════════╗');
        $this->line('║                     RESUMEN DE CONFIGURACIÓN                         ║');
        $this->line('╚══════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Configuración', 'Valor'],
            [
                ['Objetivo principal', $this->formatMainObjective($config['main_objective'])],
                ['Estilo de escritura', $config['writing_style']['tone']],
                ['Respuestas automáticas', $config['auto_response']['enabled'] ? '✅ Activado' : '❌ Desactivado'],
                ['Modo de respuesta', $config['auto_response']['mode'] ?? 'N/A'],
                ['Umbral de confianza', ($config['confidence_threshold'] * 100) . '%'],
                ['Manejo de newsletters', $this->formatNewsletterHandling($config['newsletter_handling'])],
                ['Contactos importantes', count($config['important_contacts']) . ' configurados'],
                ['Horario activo', "{$config['availability']['start']} - {$config['availability']['end']}"],
                ['Nivel de autonomía', ucfirst($config['autonomy_level'])],
            ]
        );
    }

    protected function formatMainObjective(string $objective): string
    {
        return match($objective) {
            'filter' => '🗂️ Filtrar y organizar',
            'respond' => '✉️ Ayudar a responder',
            'balanced' => '⚖️ Ambos (filtrar y responder)',
            default => $objective,
        };
    }

    protected function formatNewsletterHandling(string $handling): string
    {
        return match($handling) {
            'auto_archive' => '📥 Archivar automáticamente',
            'mark_read' => '👁️ Marcar como leídas',
            'categorize' => '🏷️ Solo categorizar',
            'manual' => '✋ Manejo manual',
            default => $handling,
        };
    }
}
