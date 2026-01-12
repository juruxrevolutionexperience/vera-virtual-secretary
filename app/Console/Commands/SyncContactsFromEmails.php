<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ContactService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncContactsFromEmails extends Command
{
    protected $signature = 'contacts:sync-from-emails
                           {--user= : ID del usuario específico (opcional)}
                           {--dry-run : Modo simulación sin sincronizar contactos}';

    protected $description = 'Sincronizar libreta de contactos desde emails recibidos y enviados';

    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        parent::__construct();
        $this->contactService = $contactService;
    }

    public function handle(): int
    {
        $this->info('📇 Sincronizando contactos desde emails...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se sincronizarán contactos realmente');
            return Command::SUCCESS;
        }

        $totalStats = [
            'users_processed' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            // Obtener usuarios
            $users = $this->option('user')
                ? User::where('id', $this->option('user'))->get()
                : User::all();

            if ($users->isEmpty()) {
                $this->error('❌ No se encontraron usuarios');
                return Command::FAILURE;
            }

            $this->info("📊 Procesando {$users->count()} usuario(s)...\n");

            $progressBar = $this->output->createProgressBar($users->count());
            $progressBar->start();

            foreach ($users as $user) {
                try {
                    $stats = $this->contactService->syncFromEmails($user);

                    $totalStats['users_processed']++;
                    $totalStats['processed'] += $stats['processed'];
                    $totalStats['created'] += $stats['created'];
                    $totalStats['updated'] += $stats['updated'];
                    $totalStats['errors'] += $stats['errors'];

                    $progressBar->advance();
                } catch (\Exception $e) {
                    $totalStats['errors']++;
                    Log::error('Error syncing contacts for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            // Mostrar resumen
            $this->info('✅ Sincronización completada');
            $this->newLine();
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Usuarios procesados', $totalStats['users_processed']],
                    ['Emails procesados', $totalStats['processed']],
                    ['Contactos creados', $totalStats['created']],
                    ['Contactos actualizados', $totalStats['updated']],
                    ['Errores', $totalStats['errors']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error in SyncContactsFromEmails command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
