<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Models\User;
use App\Models\VeraConfig;
use App\Models\VeraMemoryChunk;
use App\Services\AI\AIGatewayService;
use App\Services\VeraIntelligentAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VeraWeeklyReanalysis extends Command
{
    protected $signature = 'vera:weekly-reanalysis
                            {--user-id= : ID específico de usuario para re-analizar}
                            {--force : Forzar re-análisis aunque no haya pasado el tiempo}';

    protected $description = 'Re-analiza los emails de usuarios para que Vera siga aprendiendo';

    public function handle(VeraIntelligentAnalyzer $analyzer, AIGatewayService $aiGateway)
    {
        $this->info('🔄 Iniciando re-análisis semanal de Vera...');
        $this->newLine();

        $users = $this->getUsersToReanalyze();

        if ($users->isEmpty()) {
            $this->info('✅ No hay usuarios que requieran re-análisis en este momento');
            return Command::SUCCESS;
        }

        $this->info("👥 Usuarios a re-analizar: {$users->count()}");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                $this->line("📊 Analizando usuario: {$user->name} (ID: {$user->id})");

                // Análisis de escritura y patrones (ya existía)
                $analysis = $analyzer->analyzeUser($user);

                $config = VeraConfig::where('user_id', $user->id)->first();

                if ($config) {
                    $config->update([
                        'writing_style' => $analysis['writing_style'],
                        'last_re_analysis_at' => now(),
                        'analysis_summary' => [
                            'email_statistics'       => $analysis['email_statistics'],
                            'communication_patterns' => $analysis['communication_patterns'],
                            'response_behavior'      => $analysis['response_behavior'],
                            'conclusions'            => $analysis['conclusions'],
                            'analyzed_at'            => now()->toDateTimeString(),
                        ],
                    ]);

                    $this->info("   ✅ Configuración actualizada");
                    $this->line("   📝 Emails analizados: {$analysis['email_statistics']['total_emails']}");

                    if ($analysis['writing_style']['has_sent_emails']) {
                        $this->line("   ✍️  Tono detectado: {$analysis['writing_style']['tone']}");
                        $this->line("   📏 Longitud promedio: {$analysis['writing_style']['avg_email_length']} caracteres");
                    }

                    // NUEVO: Generar resumen semanal + sugerencias con IA
                    $this->line("   🧠 Generando resumen semanal con IA...");
                    $this->generateWeeklySuggestions($user, $config, $analysis, $aiGateway);
                    $this->info("   ✅ Resumen semanal generado");

                    $successCount++;
                } else {
                    $this->warn("   ⚠️  No tiene VeraConfig, saltando...");
                }

                $this->newLine();

            } catch (\Exception $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                $errorCount++;

                Log::error('Error en re-análisis semanal de Vera', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMEN:');
        $this->line("   ✅ Exitosos: {$successCount}");
        $this->line("   ❌ Errores: {$errorCount}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return Command::SUCCESS;
    }

    /**
     * Genera el resumen narrativo de la semana y sugerencias proactivas.
     * Usa Gemini con las stats ya calculadas (sin leer emails de nuevo).
     */
    protected function generateWeeklySuggestions(
        User $user,
        VeraConfig $config,
        array $analysis,
        AIGatewayService $aiGateway
    ): void {
        // Stats de los últimos 7 días (datos reales de BD, sin IA)
        $weekStart = now()->subDays(7)->startOfDay();

        $weekEmails = Email::where('user_id', $user->id)
            ->where('received_at', '>=', $weekStart)
            ->get();

        $weekTotal     = $weekEmails->count();
        $weekCategories = $weekEmails->groupBy('category')
            ->map(fn($g) => $g->count())
            ->sortDesc()
            ->take(3)
            ->toArray();
        $weekUrgent = $weekEmails->where('priority', 'urgent')->count();
        $weekNeedsReply = $weekEmails->where('vera_action_required', 'reply')->count();

        // Si no hay emails esta semana, no generar sugerencias
        if ($weekTotal === 0) {
            return;
        }

        // Stats para pills visuales en el widget
        $stats = [
            'total'        => $weekTotal,
            'urgent'       => $weekUrgent,
            'needs_reply'  => $weekNeedsReply,
            'top_category' => array_key_first($weekCategories) ?? null,
            'top_count'    => array_values($weekCategories)[0] ?? 0,
        ];

        // Prompt compacto para Gemini: le damos stats, nos devuelve JSON con action_type
        $statsJson = json_encode([
            'emails_esta_semana'   => $weekTotal,
            'categorias_top'       => $weekCategories,
            'emails_urgentes'      => $weekUrgent,
            'necesitan_respuesta'  => $weekNeedsReply,
            'tono_usuario'         => $analysis['writing_style']['tone'] ?? 'profesional',
            'conclusiones_vera'    => array_slice($analysis['conclusions'] ?? [], 0, 3),
        ], JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Eres Vera, asistente de email IA. Analiza estas estadísticas semanales y responde SOLO con JSON válido, sin markdown.

Estadísticas:
{$statsJson}

Genera:
1. "narrative": 2 frases en español, hablando al usuario de tú, tono cercano. Menciona cifras concretas.
2. "suggestions": array de exactamente 2 objetos. Elige el action_type más relevante según los datos.

Estructura de cada sugerencia:
{
  "id": "sug_1",
  "title": "título corto (4-5 palabras)",
  "message": "una frase concreta y accionable",
  "action_type": "ELIGE UNO: enable_newsletter_archive | enable_auto_response | configure_writing_style | enable_priority_sort"
}

Reglas para elegir action_type:
- enable_newsletter_archive → si hay muchos promocionales/newsletters (>10)
- enable_auto_response → si hay muchos emails que necesitan respuesta (>5)
- configure_writing_style → si no se ha configurado el tono del usuario
- enable_priority_sort → si hay emails urgentes (>3)

Responde SOLO con:
{"narrative": "...", "suggestions": [...]}
PROMPT;

        try {
            $response = $aiGateway->analyze(
                systemPrompt: 'Eres Vera AI. Respondes SOLO con JSON válido, sin markdown, sin explicaciones.',
                userPrompt: $prompt,
                userId: $user->id,
                actionType: 'weekly_insights',
                metadata: ['week_emails' => $weekTotal]
            );

            $text = preg_replace('/```json\s*|\s*```/', '', $response['content']);
            $data = json_decode(trim($text), true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['narrative'])) {
                Log::warning('⚠️ No se pudo parsear el resumen semanal de Vera', [
                    'user_id' => $user->id,
                    'raw'     => $response['content'],
                ]);
                return;
            }

            // Consolidar insights aprendidos de vera_memory_chunks (GAP 3)
            $confirmableInsights = $this->consolidateInsights($user, $aiGateway);

            // Construir el objeto final y guardar en vera_configs
            $weeklySuggestions = [
                'generated_at'        => now()->toDateTimeString(),
                'week_label'          => now()->subDays(7)->format('d M') . ' - ' . now()->format('d M'),
                'narrative'           => $data['narrative'],
                'suggestions'         => $data['suggestions'] ?? [],
                'dismissed_ids'       => [],
                'stats'               => $stats, // Pills visuales en el widget
                'confirmable_insights' => $confirmableInsights,
            ];

            $config->update(['weekly_suggestions' => $weeklySuggestions]);

            Log::info('✅ Resumen semanal generado', [
                'user_id'     => $user->id,
                'week_emails' => $weekTotal,
                'suggestions' => count($data['suggestions'] ?? []),
            ]);

        } catch (\Exception $e) {
            // No es crítico: si falla, simplemente no hay resumen esta semana
            Log::warning('⚠️ Error generando resumen semanal', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consolida los aprender_de_esto de los últimos 7 días en insights confirmables.
     * Devuelve array de hasta 3 insights que el usuario puede confirmar o denegar.
     */
    protected function consolidateInsights(User $user, AIGatewayService $aiGateway): array
    {
        $weekStart = now()->subDays(7)->startOfDay();

        $chunks = \App\Models\VeraMemoryChunk::where('user_id', $user->id)
            ->where('source', 'email_analysis')
            ->where('type', 'contact_profile')
            ->where('created_at', '>=', $weekStart)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'content', 'title']);

        if ($chunks->count() < 3) {
            return [];
        }

        $chunksText = $chunks->map(fn($c) => "- [{$c->title}]: {$c->content}")->implode("\n");

        $prompt = <<<PROMPT
Eres Vera, asistente de email IA. Analiza estos patrones que he aprendido esta semana sobre los remitentes del usuario:

{$chunksText}

Identifica hasta 3 patrones de comportamiento del USUARIO (no de los remitentes) que sean accionables y confirmables.
Por ejemplo: "Recibes muchos emails de clientes los lunes", "Sueles responder rápido a emails urgentes", etc.

Responde SOLO con JSON válido, sin markdown:
{
  "confirmable_insights": [
    {
      "id": "ins_1",
      "statement": "Parece que prefieres responder emails de trabajo antes que personales",
      "action_on_confirm": null,
      "action_on_deny": null
    }
  ]
}

Reglas:
- Máximo 3 insights
- El "statement" en primera persona del usuario (máx 120 chars)
- Si no hay suficientes patrones claros, devuelve "confirmable_insights": []
- action_on_confirm puede ser: "block_friday_evening", "prioritize_urgent", null
PROMPT;

        try {
            $response = $aiGateway->analyze(
                systemPrompt: 'Eres Vera AI. Respondes SOLO con JSON válido, sin markdown.',
                userPrompt: $prompt,
                userId: $user->id,
                actionType: 'weekly_insights',
                metadata: ['chunks_count' => $chunks->count()]
            );

            $text = preg_replace('/```json\s*|\s*```/', '', $response['content']);
            $data = json_decode(trim($text), true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['confirmable_insights'])) {
                return [];
            }

            return array_slice($data['confirmable_insights'], 0, 3);

        } catch (\Exception $e) {
            Log::warning('⚠️ Error consolidando insights semanales', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener usuarios que necesitan re-análisis
     */
    protected function getUsersToReanalyze()
    {
        $userId = $this->option('user-id');
        $force  = $this->option('force');

        if ($userId) {
            return User::where('id', $userId)->get();
        }

        if ($force) {
            return User::whereHas('veraConfig')->get();
        }

        return User::whereHas('veraConfig', function ($query) {
            $query->where(function ($q) {
                $q->whereNull('last_re_analysis_at')
                  ->orWhereRaw('DATEDIFF(NOW(), last_re_analysis_at) >= re_analysis_frequency_days');
            });
        })->get();
    }
}
