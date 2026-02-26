<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VeraMemoryService;
use Illuminate\Console\Command;

class IndexVeraMemory extends Command
{
    protected $signature = 'vera:index-memory
                            {user_id? : ID del usuario (vacío = todos los usuarios)}
                            {--flush : Borrar memoria existente antes de re-indexar}';

    protected $description = 'Indexa la configuración de Vera del usuario en la memoria RAG';

    public function handle(VeraMemoryService $memoryService): int
    {
        $userId = $this->argument('user_id');
        $flush  = $this->option('flush');

        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->error('No se encontraron usuarios.');
            return Command::FAILURE;
        }

        $this->info("Indexando memoria Vera para {$users->count()} usuario(s)...");
        $this->newLine();

        $totalIndexed = 0;

        foreach ($users as $user) {
            $this->line("→ Usuario: {$user->name} (ID: {$user->id})");

            // Limpiar memoria previa si --flush
            if ($flush) {
                $deleted = \App\Models\VeraMemoryChunk::forUser($user->id)
                    ->where('source', 'user_config')
                    ->delete();
                $this->line("  ✗ Borrados {$deleted} chunks previos de user_config");
            }

            // Indexar config
            $result = $memoryService->indexUserConfig($user);

            $this->line("  ✓ Indexados: {$result['indexed']} chunks");

            // Mostrar estadísticas
            $stats = $memoryService->getStats($user->id);
            $this->line("  📊 Total en memoria: {$stats['total_chunks']} chunks");

            if (!empty($stats['by_type'])) {
                foreach ($stats['by_type'] as $type => $count) {
                    $this->line("     - {$type}: {$count}");
                }
            }

            $totalIndexed += $result['indexed'];
            $this->newLine();
        }

        $this->info("✅ Completado. Total indexado: {$totalIndexed} chunks.");

        return Command::SUCCESS;
    }
}
