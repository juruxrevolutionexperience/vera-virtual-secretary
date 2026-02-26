<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VeraWritingStyleService;
use Illuminate\Console\Command;

class AnalyzeWritingStyle extends Command
{
    protected $signature = 'vera:analyze-writing-style
                            {--user= : ID del usuario (si no se indica, procesa todos los que tienen cuenta conectada)}';

    protected $description = 'Analiza los emails enviados del usuario y aprende su estilo de escritura';

    public function handle(VeraWritingStyleService $service): int
    {
        $this->info('✍️  Vera - Analizador de Estilo de Escritura');
        $this->newLine();

        $userId = $this->option('user');

        $users = $userId
            ? User::where('id', $userId)->get()
            : User::whereHas('emailAccounts', fn($q) => $q->where('sync_enabled', true))->get();

        if ($users->isEmpty()) {
            $this->error('No se encontraron usuarios con cuentas de email conectadas.');
            return 1;
        }

        foreach ($users as $user) {
            $this->line("👤 Usuario: {$user->name} (ID: {$user->id})");

            try {
                $result = $service->analyzeAndSave($user->id);
                $profile = $result['profile'];

                $this->info("  ✅ Análisis completado ({$result['emails_analyzed']} emails analizados)");
                $this->line("  → Tono: {$profile['tone']}");
                $this->line("  → Formalidad: " . ($profile['formality_score'] * 100) . "%");
                $this->line("  → Saludos detectados: " . implode(', ', $profile['greetings'] ?? []));
                $this->line("  → Despedidas detectadas: " . implode(', ', $profile['closings'] ?? []));
                $this->line("  → Usa emojis: " . ($profile['uses_emojis'] ? 'Sí' : 'No'));
                if (!empty($profile['analysis_summary'])) {
                    $this->line("  → Resumen: {$profile['analysis_summary']}");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info('Proceso completado.');
        return 0;
    }
}
