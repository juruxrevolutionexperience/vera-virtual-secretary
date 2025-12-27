<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VeraUsageService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class VeraResetMonthlyLimits extends Command
{
    protected $signature = 'vera:reset-monthly-limits {--force : Forzar reset sin confirmación}';
    protected $description = 'Resetea los límites mensuales de todos los usuarios';

    protected VeraUsageService $usageService;

    public function __construct()
    {
        parent::__construct();
        $this->usageService = new VeraUsageService();
    }

    public function handle()
    {
        $this->info('🔄 Iniciando reseteo de límites mensuales...');
        $this->info('');

        // Obtener usuarios que necesitan reset
        $users = User::whereNotNull('billing_cycle_start')
            ->whereRaw('DAY(billing_cycle_start) = DAY(NOW())')
            ->get();

        if ($users->isEmpty()) {
            $this->info('✅ No hay usuarios que necesiten reset hoy');
            $this->info('   (Los resets se hacen el día de inicio del ciclo de cada usuario)');
            return 0;
        }

        $this->info("📊 Usuarios a resetear: {$users->count()}");
        $this->info('');

        if (!$this->option('force')) {
            if (!$this->confirm('¿Continuar con el reset?', true)) {
                $this->warn('❌ Operación cancelada');
                return 1;
            }
        }

        $this->info('');
        $this->info('Procesando...');
        $this->info('');

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $resetCount = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                // Resetear ciclo
                $this->usageService->resetBillingCycle($user);
                $resetCount++;

                $this->info('');
                $this->info("✅ Usuario #{$user->id} ({$user->name})");
                $this->info("   Plan: {$user->plan_type}");
                $this->info("   Nuevo ciclo: {$user->billing_cycle_start->format('Y-m-d')}");

            } catch (\Exception $e) {
                $errors[] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ];

                $this->error('');
                $this->error("❌ Error con usuario #{$user->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('');
        $this->info('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMEN');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✅ Usuarios reseteados: {$resetCount}");

        if (!empty($errors)) {
            $this->error("❌ Errores: " . count($errors));
            $this->info('');
            $this->error('Detalles de errores:');
            foreach ($errors as $error) {
                $this->error("   Usuario #{$error['user_id']}: {$error['error']}");
            }
        }

        $this->info('');
        $this->info('🎉 Reset de límites mensuales completado');

        return 0;
    }
}
