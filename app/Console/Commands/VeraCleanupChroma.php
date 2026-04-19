<?php

namespace App\Console\Commands;

use App\Models\VeraMemoryChunk;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VeraCleanupChroma extends Command
{
    protected $signature = 'vera:cleanup-chroma
                            {--user= : Limpiar solo un usuario (por ID)}
                            {--dry-run : Mostrar qué se borraría sin borrar nada}';

    protected $description = 'Elimina físicamente de ChromaDB los chunks marcados como inactivos en MySQL';

    public function handle(): int
    {
        $aiServiceUrl = config('services.ai_service.url');

        if (!$aiServiceUrl) {
            $this->error('AI_SERVICE_URL no está configurado en .env');
            return 1;
        }

        $dryRun = $this->option('dry-run');
        $userId = $this->option('user');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No se borrará nada de ChromaDB');
        }

        $query = VeraMemoryChunk::where('is_active', false);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $chunks = $query->get();

        if ($chunks->isEmpty()) {
            $this->info('No hay chunks inactivos que limpiar.');
            return 0;
        }

        $this->info("Chunks inactivos encontrados: {$chunks->count()}");
        $this->newLine();

        $deleted = 0;
        $errors  = [];

        foreach ($chunks as $chunk) {
            $label = "chunk #{$chunk->id} [user {$chunk->user_id}] {$chunk->title}";

            if ($dryRun) {
                $this->line("  [DRY-RUN] borraría {$label}");
                $deleted++;
                continue;
            }

            try {
                $response = Http::timeout(10)
                    ->withHeaders(['X-Service-Token' => config('services.ai_service.secret')])
                    ->delete("{$aiServiceUrl}/memory/chunk/{$chunk->user_id}/{$chunk->id}");

                if ($response->successful()) {
                    $chunk->delete(); // borrar también de MySQL una vez limpio en ChromaDB
                    $this->line("  ✅ {$label}");
                    $deleted++;
                } else {
                    $errors[] = "{$label}: HTTP {$response->status()}";
                    $this->error("  ❌ {$label}: HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                $errors[] = "{$label}: {$e->getMessage()}";
                $this->error("  ❌ {$label}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Chunks eliminados: {$deleted}");

        if (!empty($errors)) {
            $this->error('Errores: ' . count($errors));
            foreach ($errors as $err) {
                $this->error("  {$err}");
            }
            return 1;
        }

        $this->info('Limpieza completada.');
        return 0;
    }
}
