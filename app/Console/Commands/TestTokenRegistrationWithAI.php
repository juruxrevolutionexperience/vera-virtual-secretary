<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Models\VeraTokenUsage;
use App\Services\AI\GeminiProvider;
use Illuminate\Console\Command;

class TestTokenRegistrationWithAI extends Command
{
    protected $signature = 'vera:test-token-ai';
    protected $description = 'Prueba el registro de tokens llamando directamente a Gemini AI';

    public function handle()
    {
        $this->info('🧪 Iniciando prueba de registro de tokens con IA...');
        $this->info('');

        // 1. Verificar estado inicial
        $initialCount = VeraTokenUsage::count();
        $this->info("📊 Registros actuales en vera_token_usage: {$initialCount}");
        $this->info('');

        // 2. Obtener un email
        $email = Email::whereNotNull('body_text')
            ->orWhereNotNull('body_html')
            ->first();

        if (!$email) {
            $this->error('❌ No hay emails disponibles');
            return 1;
        }

        $this->info("📧 Email seleccionado:");
        $this->info("   ID: {$email->id}");
        $this->info("   Subject: {$email->subject}");
        $this->info("   From: {$email->from_email}");
        $this->info("   User ID: {$email->user_id}");
        $this->info('');

        // 3. Verificar que Gemini está configurado
        $gemini = new GeminiProvider();

        if (!$gemini->isConfigured()) {
            $this->error('❌ Gemini no está configurado');
            $this->error('   Configura GEMINI_API_KEY en .env');
            return 1;
        }

        $this->info('✅ Gemini configurado correctamente');
        $this->info('');

        // 4. Llamar a Gemini directamente (FORZAR USO DE IA)
        $this->info('🤖 Llamando a Gemini AI (FORZADO)...');

        try {
            $systemPrompt = "Eres Vera, analiza el siguiente email.";
            $userPrompt = "Email: " . $email->subject . "\n\nContenido: " . substr($email->body_text ?? strip_tags($email->body_html), 0, 500);

            $response = $gemini->analyze(
                systemPrompt: $systemPrompt,
                userPrompt: $userPrompt,
                userId: $email->user_id,
                emailId: $email->id,
                actionType: 'email_analysis',
                metadata: [
                    'subject' => $email->subject,
                    'from' => $email->from_email,
                    'test' => true,
                ]
            );

            $this->info('✅ Gemini respondió correctamente');
            $this->info("   Tokens usados: {$response['tokens_used']}");
            $this->info("   Respuesta (primeros 200 chars): " . substr($response['content'], 0, 200) . '...');
            $this->info('');

        } catch (\Exception $e) {
            $this->error("❌ Error llamando a Gemini: {$e->getMessage()}");
            $this->error("   Stack trace: {$e->getTraceAsString()}");
            return 1;
        }

        // 5. Esperar que el listener procese (es async con cola)
        $this->info('⏳ Esperando 5 segundos para que el listener procese...');
        $this->info('   (El listener está en cola, puede tardar)');
        sleep(5);

        // 6. Verificar que se registró
        $finalCount = VeraTokenUsage::count();
        $newRecords = $finalCount - $initialCount;

        $this->info('');
        $this->info("📊 Registros después del procesamiento: {$finalCount}");
        $this->info("📈 Nuevos registros: {$newRecords}");
        $this->info('');

        if ($newRecords > 0) {
            $this->info('🎉 ¡ÉXITO! El sistema de registro de tokens funciona correctamente');
            $this->info('');

            // Mostrar el último registro
            $lastRecord = VeraTokenUsage::latest()->first();
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['User ID', $lastRecord->user_id],
                    ['Email ID', $lastRecord->email_id ?? 'NULL'],
                    ['Action Type', $lastRecord->action_type],
                    ['Prompt Tokens', $lastRecord->prompt_tokens],
                    ['Completion Tokens', $lastRecord->completion_tokens],
                    ['Total Tokens', $lastRecord->total_tokens],
                    ['Model', $lastRecord->model],
                    ['Cost', '$' . number_format($lastRecord->cost, 6)],
                    ['Created At', $lastRecord->created_at->format('Y-m-d H:i:s')],
                ]
            );

            if ($lastRecord->metadata) {
                $this->info('');
                $this->info('📋 Metadata:');
                $metadata = is_array($lastRecord->metadata) ? $lastRecord->metadata : json_decode($lastRecord->metadata, true);
                foreach ($metadata as $key => $value) {
                    $this->info("   {$key}: {$value}");
                }
            }

            $this->info('');
            $this->info('✅ FASE 1 COMPLETADA Y VERIFICADA');

        } else {
            $this->warn('⚠️  No se registraron tokens aún');
            $this->info('');
            $this->info('El listener está en cola. Verifica manualmente con:');
            $this->info('   ./vendor/bin/sail artisan queue:work');
            $this->info('');
            $this->info('O verifica los logs:');
            $this->info('   tail -f storage/logs/laravel.log');
        }

        return 0;
    }
}
