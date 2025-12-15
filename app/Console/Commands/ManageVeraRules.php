<?php

namespace App\Console\Commands;

use App\Models\VeraRule;
use Illuminate\Console\Command;

class ManageVeraRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vera:rules
                            {action? : list, enable, disable, stats}
                            {id? : ID de la regla}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar reglas de Vera';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action') ?? 'list';

        match($action) {
            'list' => $this->listRules(),
            'enable' => $this->enableRule(),
            'disable' => $this->disableRule(),
            'stats' => $this->showStats(),
            default => $this->error("Acción no válida: {$action}"),
        };

        return 0;
    }

    /**
     * Listar todas las reglas
     */
    protected function listRules(): void
    {
        $this->info('📋 Reglas de Vera');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $rules = VeraRule::orderBy('priority', 'desc')->get();

        if ($rules->isEmpty()) {
            $this->warn('No hay reglas configuradas');
            return;
        }

        $rows = [];
        foreach ($rules as $rule) {
            $status = $rule->enabled ? '<fg=green>✓ Activa</>' : '<fg=red>✗ Inactiva</>';
            $rows[] = [
                $rule->id,
                $rule->name,
                $status,
                $rule->rule_type,
                $rule->times_applied,
                $rule->priority,
            ];
        }

        $this->table(
            ['ID', 'Nombre', 'Estado', 'Tipo', 'Aplicada', 'Prioridad'],
            $rows
        );

        $this->newLine();
        $this->info('💡 Comandos:');
        $this->line('   vera:rules enable <id>    - Activar regla');
        $this->line('   vera:rules disable <id>   - Desactivar regla');
        $this->line('   vera:rules stats          - Ver estadísticas detalladas');
    }

    /**
     * Habilitar una regla
     */
    protected function enableRule(): void
    {
        $id = $this->argument('id');

        if (!$id) {
            $this->error('Debes especificar el ID de la regla');
            $this->info('Uso: vera:rules enable <id>');
            return;
        }

        $rule = VeraRule::find($id);

        if (!$rule) {
            $this->error("Regla #{$id} no encontrada");
            return;
        }

        $rule->update(['enabled' => true]);
        $this->info("✅ Regla activada: {$rule->name}");
    }

    /**
     * Deshabilitar una regla
     */
    protected function disableRule(): void
    {
        $id = $this->argument('id');

        if (!$id) {
            $this->error('Debes especificar el ID de la regla');
            $this->info('Uso: vera:rules disable <id>');
            return;
        }

        $rule = VeraRule::find($id);

        if (!$rule) {
            $this->error("Regla #{$id} no encontrada");
            return;
        }

        $rule->update(['enabled' => false]);
        $this->warn("⏸️  Regla desactivada: {$rule->name}");
    }

    /**
     * Mostrar estadísticas detalladas
     */
    protected function showStats(): void
    {
        $this->info('📊 Estadísticas de Reglas de Vera');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $rules = VeraRule::orderBy('times_applied', 'desc')->get();

        if ($rules->isEmpty()) {
            $this->warn('No hay reglas configuradas');
            return;
        }

        foreach ($rules as $rule) {
            $status = $rule->enabled ? '🟢' : '🔴';
            $this->line("{$status} <fg=cyan>{$rule->name}</>");
            $this->line("   Tipo: {$rule->rule_type}");
            $this->line("   Aplicada: {$rule->times_applied} veces");
            if ($rule->last_applied_at) {
                $this->line("   Última vez: {$rule->last_applied_at->diffForHumans()}");
            }
            $this->line("   Condiciones: " . json_encode($rule->conditions));
            $this->newLine();
        }

        // Resumen general
        $totalActive = $rules->where('enabled', true)->count();
        $totalInactive = $rules->where('enabled', false)->count();
        $totalApplications = $rules->sum('times_applied');

        $this->info('═══════════════════════════════════════════════════════');
        $this->line("Total reglas: {$rules->count()}");
        $this->line("Activas: <fg=green>{$totalActive}</>");
        $this->line("Inactivas: <fg=red>{$totalInactive}</>");
        $this->line("Total aplicaciones: {$totalApplications}");
    }
}
