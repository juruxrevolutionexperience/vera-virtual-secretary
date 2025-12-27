<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\VeraUsageService;
use App\Http\Middleware\VeraEmailLimitMiddleware;

/**
 * TestVeraUsageSystem
 *
 * Comando para probar el sistema completo de límites y uso de Vera
 */
class TestVeraUsageSystem extends Command
{
    protected $signature = 'test:vera-usage-system';
    protected $description = 'Probar el sistema de límites de Vera (Fase 3)';

    protected VeraUsageService $usageService;

    public function __construct()
    {
        parent::__construct();
        $this->usageService = new VeraUsageService();
    }

    public function handle()
    {
        $this->info('====================================');
        $this->info('PRUEBA DEL SISTEMA DE LÍMITES VERA');
        $this->info('====================================');
        $this->newLine();

        // Obtener primer usuario
        $user = User::first();

        if (!$user) {
            $this->error('No hay usuarios en la base de datos');
            return 1;
        }

        $this->info("Usuario de prueba: {$user->name} (ID: {$user->id})");
        $this->info("Email: {$user->email}");
        $this->newLine();

        // Test 1: Verificar plan actual
        $this->test1_currentPlan($user);

        // Test 2: Calcular uso mensual
        $this->test2_monthlyUsage($user);

        // Test 3: Verificar si puede procesar emails
        $this->test3_canProcessEmail($user);

        // Test 4: Simular alcanzar límite
        $this->test4_simulateLimit($user);

        // Test 5: Proyecciones
        $this->test5_projections($user);

        $this->newLine();
        $this->info('====================================');
        $this->info('✅ TODAS LAS PRUEBAS COMPLETADAS');
        $this->info('====================================');

        return 0;
    }

    protected function test1_currentPlan(User $user)
    {
        $this->info('📋 TEST 1: Plan actual del usuario');
        $this->line('-----------------------------------');

        $this->table(
            ['Campo', 'Valor'],
            [
                ['Plan', $user->plan_type],
                ['Límite mensual', $user->monthly_email_limit . ' emails'],
                ['Ciclo inicio', $user->billing_cycle_start ?? 'No definido'],
            ]
        );

        $this->newLine();
    }

    protected function test2_monthlyUsage(User $user)
    {
        $this->info('📊 TEST 2: Uso mensual calculado');
        $this->line('-----------------------------------');

        $usage = $this->usageService->calculateMonthlyUsage($user);

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Plan', $usage['plan_type']],
                ['Límite', $usage['email_limit'] . ' emails'],
                ['Procesados', $usage['emails_processed'] . ' emails'],
                ['Restantes', $usage['emails_remaining'] . ' emails'],
                ['Porcentaje uso', number_format($usage['usage_percentage'], 2) . '%'],
                ['Tokens totales', number_format($usage['total_tokens'])],
                ['Ciclo inicio', $usage['billing_cycle_start']],
                ['Ciclo fin', $usage['billing_cycle_end']],
            ]
        );

        $this->newLine();
    }

    protected function test3_canProcessEmail(User $user)
    {
        $this->info('🔍 TEST 3: Verificar capacidad de procesamiento');
        $this->line('-----------------------------------');

        $canProcess = $this->usageService->canProcessEmail($user);
        $remaining = $this->usageService->getRemainingEmails($user);
        $alertLevel = $this->usageService->getAlertLevel($user);

        $this->table(
            ['Check', 'Resultado'],
            [
                ['¿Puede procesar?', $canProcess ? '✅ SÍ' : '❌ NO'],
                ['Emails restantes', $remaining],
                ['Nivel de alerta', strtoupper($alertLevel)],
                ['¿Debe enviar alerta?', $this->usageService->shouldSendAlert($user) ? 'SÍ' : 'NO'],
            ]
        );

        $this->newLine();
    }

    protected function test4_simulateLimit(User $user)
    {
        $this->info('⚠️  TEST 4: Simular alcanzar límite');
        $this->line('-----------------------------------');

        // Guardar límite original
        $originalLimit = $user->monthly_email_limit;

        // Calcular emails procesados actualmente
        $processed = $this->usageService->getEmailsProcessedThisMonth($user);

        $this->line("Emails procesados actualmente: {$processed}");
        $this->line("Límite original: {$originalLimit}");

        // Modificar temporalmente el límite para que esté al límite
        $user->monthly_email_limit = $processed;
        $user->save();
        $this->line("Límite modificado temporalmente a: {$processed}");

        // IMPORTANTE: Refrescar el usuario desde la DB
        $user->refresh();

        // Verificar que no puede procesar más
        $canProcess = $this->usageService->canProcessEmail($user);
        $remaining = $this->usageService->getRemainingEmails($user);
        $alertLevel = $this->usageService->getAlertLevel($user);

        $this->table(
            ['Check', 'Resultado'],
            [
                ['¿Puede procesar?', $canProcess ? '✅ SÍ (ERROR!)' : '❌ NO (CORRECTO)'],
                ['Emails restantes', $remaining],
                ['Nivel de alerta', strtoupper($alertLevel)],
            ]
        );

        // Restaurar límite original
        $user->monthly_email_limit = $originalLimit;
        $user->save();
        $this->line("✅ Límite restaurado a: {$originalLimit}");

        $this->newLine();
    }

    protected function test5_projections(User $user)
    {
        $this->info('📈 TEST 5: Proyecciones y estadísticas');
        $this->line('-----------------------------------');

        $avgPerWeek = $this->usageService->getAverageEmailsPerWeek($user);
        $limitDate = $this->usageService->projectLimitReachDate($user);

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Promedio semanal', number_format($avgPerWeek, 2) . ' emails/semana'],
                ['Fecha proyectada límite', $limitDate ? $limitDate->format('d/m/Y') : 'No hay suficiente data'],
                ['Días hasta fin ciclo', now()->diffInDays($user->billing_cycle_start ? now()->parse($user->billing_cycle_start)->addMonth()->endOfMonth() : now()->endOfMonth())],
            ]
        );

        $this->newLine();
    }
}
