<?php

namespace App\Console\Commands;

use App\Models\VeraEmailAction;
use App\Services\GmailSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReviewVeraResponses extends Command
{
    protected $signature = 'vera:review-responses
                            {--user-id=1 : ID del usuario}
                            {--auto-approve : Aprobar automáticamente respuestas con alta confianza}
                            {--confidence-threshold=0.85 : Umbral de confianza para auto-aprobar}';

    protected $description = 'Revisar y aprobar respuestas automáticas generadas por Vera';

    public function handle()
    {
        $userId = $this->option('user-id');
        $autoApprove = $this->option('auto-approve');
        $threshold = (float) $this->option('confidence-threshold');

        $this->displayHeader();

        $pendingResponses = VeraEmailAction::where('user_id', $userId)
            ->where('action_type', 'replied')
            ->where('user_feedback', 'pending')
            ->where('response_sent', false)
            ->with('email')
            ->orderBy('confidence_score', 'desc')
            ->get();

        if ($pendingResponses->isEmpty()) {
            $this->info('✅ No hay respuestas pendientes de revisión');
            $this->newLine();
            $this->line('💡 Las respuestas se generan automáticamente cuando:');
            $this->line('   - Un email requiere respuesta (vera_action_required = reply)');
            $this->line('   - El email tiene prioridad alta o urgente');
            $this->line('   - Las respuestas automáticas están habilitadas en tu configuración');
            return Command::SUCCESS;
        }

        $this->info("📬 Respuestas pendientes: {$pendingResponses->count()}");
        $this->newLine();

        $approved = 0;
        $modified = 0;
        $rejected = 0;
        $skipped = 0;

        foreach ($pendingResponses as $response) {
            // Auto-aprobar si tiene alta confianza y está activado
            if ($autoApprove && $response->confidence_score >= $threshold) {
                $this->autoApproveResponse($response);
                $approved++;
                continue;
            }

            $action = $this->reviewResponse($response);

            match($action) {
                'aprobar' => $approved++,
                'modificar' => $modified++,
                'rechazar' => $rejected++,
                'saltar' => $skipped++,
                default => null,
            };
        }

        $this->displaySummary($approved, $modified, $rejected, $skipped);

        return Command::SUCCESS;
    }

    protected function displayHeader()
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════════════╗');
        $this->line('║                  REVISIÓN DE RESPUESTAS AUTOMÁTICAS                  ║');
        $this->line('║                            VERA AI                                   ║');
        $this->line('╚══════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function reviewResponse(VeraEmailAction $response): string
    {
        $email = $response->email;

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->line('');
        $this->info('📧 EMAIL ORIGINAL:');
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("<fg=cyan>De:</> {$email->from_email} ({$email->from_name})");
        $this->line("<fg=cyan>Asunto:</> {$email->subject}");
        $this->line("<fg=cyan>Prioridad:</> {$email->priority}");
        $this->newLine();
        $this->line("<fg=yellow>Contenido:</>");
        $excerpt = Str::limit($email->body_text ?? strip_tags($email->body_html ?? ''), 300);
        $this->line($excerpt);
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->newLine();
        $this->info('🤖 RESPUESTA GENERADA POR VERA:');
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line($response->generated_response);
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->newLine();
        $confidenceColor = $response->confidence_score >= 0.8 ? 'green' : ($response->confidence_score >= 0.6 ? 'yellow' : 'red');
        $this->line("<fg={$confidenceColor}>⚡ Confianza:</> {$response->confidence_score}");
        $this->line("<fg=gray>💭 Razón:</> {$response->reasoning}");

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $action = $this->choice(
            '¿Qué quieres hacer con esta respuesta?',
            [
                'aprobar' => '✅ Aprobar y enviar',
                'modificar' => '✏️  Modificar antes de enviar',
                'rechazar' => '❌ Rechazar (no enviar)',
                'saltar' => '⏭️  Saltar (decidir después)',
            ],
            'aprobar'
        );

        match($action) {
            'aprobar' => $this->approveResponse($response),
            'modificar' => $this->modifyResponse($response),
            'rechazar' => $this->rejectResponse($response),
            'saltar' => $this->skipResponse($response),
        };

        return $action;
    }

    protected function approveResponse(VeraEmailAction $response)
    {
        $response->update(['user_feedback' => 'approved']);

        // Enviar email
        if ($this->sendResponse($response)) {
            $this->info('✅ Respuesta aprobada y enviada');
        } else {
            $this->error('❌ Error al enviar la respuesta');
        }
    }

    protected function autoApproveResponse(VeraEmailAction $response)
    {
        $response->update(['user_feedback' => 'approved']);

        if ($this->sendResponse($response)) {
            $this->line("<fg=green>✅ Auto-aprobada y enviada</> (confianza: {$response->confidence_score})");
        } else {
            $this->line("<fg=red>❌ Error al auto-enviar</> - {$response->email->subject}");
        }
    }

    protected function modifyResponse(VeraEmailAction $response)
    {
        $this->newLine();
        $this->info('✏️  Escribe la respuesta modificada:');
        $this->line('<fg=gray>(Deja una línea vacía y escribe "END" para terminar)</>');
        $this->newLine();

        $modified = '';
        while (true) {
            $line = $this->ask('');
            if ($line === 'END') {
                break;
            }
            $modified .= $line . "\n";
        }

        if (empty(trim($modified))) {
            $this->warn('⚠️  No se ingresó ninguna modificación. Usando respuesta original.');
            $this->approveResponse($response);
            return;
        }

        $response->update([
            'generated_response' => trim($modified),
            'user_feedback' => 'modified',
        ]);

        // Enviar email con versión modificada
        if ($this->sendResponse($response)) {
            $this->info('✅ Respuesta modificada y enviada');
        } else {
            $this->error('❌ Error al enviar la respuesta');
        }
    }

    protected function rejectResponse(VeraEmailAction $response)
    {
        $note = $this->ask('¿Por qué rechazas esta respuesta? (opcional)');

        $response->update([
            'user_feedback' => 'rejected',
            'user_notes' => $note,
        ]);

        $this->warn('❌ Respuesta rechazada');
    }

    protected function skipResponse(VeraEmailAction $response)
    {
        $this->line('⏭️  Respuesta saltada, podrás revisarla después');
    }

    protected function sendResponse(VeraEmailAction $response): bool
    {
        try {
            $email = $response->email;
            $account = $email->emailAccount;

            if (!$account) {
                $this->error('Error: No se encontró la cuenta de email');
                return false;
            }

            $gmailService = new GmailSyncService();

            $subject = $email->subject;
            if (!str_starts_with(strtolower($subject), 're:')) {
                $subject = 'Re: ' . $subject;
            }

            $sent = $gmailService->sendEmail($account, [
                'from' => $account->email_address,
                'to' => $email->from_email,
                'subject' => $subject,
                'body' => $response->generated_response,
            ]);

            if ($sent) {
                $response->update([
                    'response_sent' => true,
                    'response_sent_at' => now(),
                ]);

                $email->update(['replied_at' => now()]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->error("Error al enviar: {$e->getMessage()}");
            return false;
        }
    }

    protected function displaySummary(int $approved, int $modified, int $rejected, int $skipped)
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════════════╗');
        $this->line('║                              RESUMEN                                 ║');
        $this->line('╚══════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Acción', 'Cantidad'],
            [
                ['✅ Aprobadas y enviadas', $approved],
                ['✏️  Modificadas y enviadas', $modified],
                ['❌ Rechazadas', $rejected],
                ['⏭️  Saltadas', $skipped],
            ]
        );

        $this->newLine();
        $this->info('💡 Tip: Puedes auto-aprobar respuestas con alta confianza usando:');
        $this->line('   ./vendor/bin/sail artisan vera:review-responses --auto-approve');
    }
}
