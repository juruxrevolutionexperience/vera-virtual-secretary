<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Models\VeraRule;
use App\Services\VeraRulesEngine;
use Illuminate\Console\Command;

class ApplyVeraRulesToEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vera:apply-rules
                            {--limit= : Límite de emails a procesar}
                            {--dry-run : Simular sin aplicar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplicar reglas de Vera a emails existentes';

    /**
     * Execute the console command.
     */
    public function handle(VeraRulesEngine $rulesEngine)
    {
        $this->info('🎯 Vera - Aplicar Reglas a Emails Existentes');
        $this->newLine();

        // Verificar reglas activas
        $rules = VeraRule::enabled()->get();

        if ($rules->isEmpty()) {
            $this->warn('⚠️  No hay reglas habilitadas');
            return 0;
        }

        $this->info('📋 Reglas activas: ' . $rules->count());
        foreach ($rules as $rule) {
            $this->line("   • {$rule->name}");
        }
        $this->newLine();

        // Obtener emails
        $query = Email::query();

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $emails = $query->get();
        $total = $emails->count();

        if ($total === 0) {
            $this->info('✅ No hay emails para procesar');
            return 0;
        }

        $this->info("📊 Emails a procesar: {$total}");

        if ($this->option('dry-run')) {
            $this->warn('🔍 MODO DRY-RUN (simulación)');
        }

        $this->newLine();

        // Confirmar si son muchos
        if ($total > 1000 && !$this->option('limit')) {
            if (!$this->confirm("⚠️  Vas a procesar {$total} emails. ¿Continuar?", true)) {
                $this->info('❌ Operación cancelada');
                return 0;
            }
            $this->newLine();
        }

        // Procesar con barra de progreso
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Iniciando...');
        $bar->start();

        $stats = [
            'processed' => 0,
            'rules_applied' => 0,
            'archived' => 0,
            'categorized' => 0,
            'prioritized' => 0,
        ];

        foreach ($emails as $email) {
            if (!$this->option('dry-run')) {
                $appliedRules = $rulesEngine->applyRules($email);

                foreach ($appliedRules as $applied) {
                    $stats['rules_applied']++;

                    // Contar por tipo de acción
                    if (isset($applied['action']['action'])) {
                        match($applied['action']['action']) {
                            'archive' => $stats['archived']++,
                            'categorize' => $stats['categorized']++,
                            'prioritize' => $stats['prioritized']++,
                            default => null,
                        };
                    }
                }
            } else {
                // Dry run - solo verificar
                foreach ($rules as $rule) {
                    if ($rule->matches($email)) {
                        $stats['rules_applied']++;

                        if ($rule->rule_type === 'auto_archive') {
                            $stats['archived']++;
                        } elseif ($rule->rule_type === 'auto_categorize') {
                            $stats['categorized']++;
                        } elseif ($rule->rule_type === 'auto_prioritize') {
                            $stats['prioritized']++;
                        }
                    }
                }
            }

            $stats['processed']++;
            $bar->setMessage("Procesando... ({$stats['rules_applied']} reglas aplicadas)");
            $bar->advance();
        }

        $bar->setMessage('Completado');
        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('✅ Procesamiento completado');
        $this->newLine();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Emails procesados', $stats['processed']],
                ['Reglas aplicadas', $stats['rules_applied']],
                ['Emails archivados', $stats['archived']],
                ['Emails categorizados', $stats['categorized']],
                ['Emails priorizados', $stats['prioritized']],
            ]
        );

        $this->newLine();

        // Estadísticas de reglas
        $this->info('📊 Estadísticas por regla:');
        foreach ($rules->fresh() as $rule) {
            $this->line("   • {$rule->name}: {$rule->times_applied} veces");
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 Esto fue una simulación. Ejecuta sin --dry-run para aplicar cambios.');
        }

        return 0;
    }
}
