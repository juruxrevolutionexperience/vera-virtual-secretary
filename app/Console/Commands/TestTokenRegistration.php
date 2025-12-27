<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Models\VeraTokenUsage;
use App\Services\VeraService;
use Illuminate\Console\Command;

class TestTokenRegistration extends Command
{
    protected $signature = 'vera:test-token-registration';
    protected $description = 'Prueba el sistema de registro de tokens procesando un email';

    public function handle()
    {
        $this->info('🧪 Iniciando prueba de registro de tokens...');
        $this->info('');

        // 1. Verificar estado inicial
        $initialCount = VeraTokenUsage::count();
        $this->info("📊 Registros actuales en vera_token_usage: {$initialCount}");

        // 2. Obtener un email sin procesar (o el primero disponible)
        $email = Email::whereNotNull('body_text')
            ->orWhereNotNull('body_html')
            ->first();

        if (!$email) {
            $this->error('❌ No hay emails disponibles para procesar');
            return 1;
        }

        $this->info("📧 Email seleccionado:");
        $this->info("   ID: {$email->id}");
        $this->info("   Subject: {$email->subject}");
        $this->info("   From: {$email->from_email}");
        $this->info("   User ID: {$email->user_id}");
        $this->info('');

        // 3. Verificar que Vera está habilitada
        $veraService = new VeraService();
        if (!$veraService->isEnabled()) {
            $this->warn('⚠️  Vera está deshabilitada en config. Habilitando temporalmente...');
            config(['vera.enabled' => true]);
        }

        // 4. Procesar el email con Vera
        $this->info('🤖 Procesando email con Vera...');

        try {
            $analysis = $veraService->analyzeEmail($email);

            $this->info('✅ Email procesado exitosamente');
            $this->info("   Método usado: {$analysis['method']}");
            $this->info("   Summary: {$analysis['summary']}");

            if (isset($analysis['tokens_used'])) {
                $this->info("   Tokens usados: {$analysis['tokens_used']}");
            }
            $this->info('');

        } catch (\Exception $e) {
            $this->error("❌ Error procesando email: {$e->getMessage()}");
            return 1;
        }

        // 5. Esperar un momento para que el listener procese (es async)
        $this->info('⏳ Esperando que el listener procese el evento...');
        sleep(3);

        // 6. Verificar que se registró
        $finalCount = VeraTokenUsage::count();
        $newRecords = $finalCount - $initialCount;

        $this->info('');
        $this->info("📊 Registros después del procesamiento: {$finalCount}");
        $this->info("📈 Nuevos registros: {$newRecords}");
        $this->info('');

        if ($newRecords > 0) {
            $this->info('✅ ¡ÉXITO! Se registraron tokens correctamente');
            $this->info('');

            // Mostrar el último registro
            $lastRecord = VeraTokenUsage::latest()->first();
            $this->info('📋 Último registro:');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['User ID', $lastRecord->user_id],
                    ['Email ID', $lastRecord->email_id],
                    ['Action Type', $lastRecord->action_type],
                    ['Prompt Tokens', $lastRecord->prompt_tokens],
                    ['Completion Tokens', $lastRecord->completion_tokens],
                    ['Total Tokens', $lastRecord->total_tokens],
                    ['Model', $lastRecord->model],
                    ['Cost', '$' . number_format($lastRecord->cost, 6)],
                    ['Created At', $lastRecord->created_at],
                ]
            );

            // Mostrar metadata si existe
            if ($lastRecord->metadata) {
                $this->info('');
                $this->info('📋 Metadata:');
                foreach ($lastRecord->metadata as $key => $value) {
                    $this->info("   {$key}: {$value}");
                }
            }

        } else {
            $this->warn('⚠️  No se registraron tokens nuevos');
            $this->warn('');
            $this->warn('Posibles causas:');
            $this->warn('1. El email se procesó solo con reglas (sin IA)');
            $this->warn('2. El listener está en cola y aún no procesó el evento');
            $this->warn('3. Gemini no está configurado correctamente');
            $this->info('');
            $this->info('Verifica los logs con: tail -f storage/logs/laravel.log');
        }

        $this->info('');
        $this->info('🎯 Prueba completada');

        return 0;
    }
}
