<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VeraMemoryChunk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VeraSyncMemoryToChroma extends Command
{
    protected $signature = 'vera:sync-memory-to-chroma
                            {--user= : Sincronizar solo un usuario (por ID)}
                            {--dry-run : Mostrar qué se sincronizaría sin enviar nada}';

    protected $description = 'Sincroniza todos los chunks de vera_memory_chunks (MySQL) → ChromaDB (ai-service)';

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
            $this->warn('[DRY-RUN] No se enviará nada al ai-service');
        }

        // Verificar que ai-service responde antes de empezar
        if (!$dryRun && !$this->checkAiServiceHealth($aiServiceUrl)) {
            $this->error("ai-service no responde en {$aiServiceUrl}/health");
            $this->error('Arranca el servicio con: cd ai-service && bash start.sh');
            return 1;
        }

        // Cargar usuarios
        $usersQuery = User::query();
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->warn('No se encontraron usuarios.');
            return 0;
        }

        $this->info("Usuarios a procesar: {$users->count()}");
        $this->newLine();

        $totalSynced = 0;
        $errors = [];

        foreach ($users as $user) {
            $chunks = VeraMemoryChunk::where('user_id', $user->id)
                ->where('is_active', true)
                ->get();

            $label = $user->name ?? $user->email;
            $this->line("Usuario #{$user->id} ({$label}) — {$chunks->count()} chunks");

            if ($chunks->isEmpty()) {
                $this->line('  (sin chunks, saltando)');
                continue;
            }

            if ($dryRun) {
                foreach ($chunks as $chunk) {
                    $this->line("  [DRY-RUN] chunk #{$chunk->id} [{$chunk->type}] {$chunk->title}");
                }
                $totalSynced += $chunks->count();
                continue;
            }

            // Enviar todos los chunks del usuario en una sola llamada
            $payload = [
                'user_id' => (string) $user->id,
                'chunks'  => $chunks->map(fn ($c) => [
                    'chunk_id'   => (string) $c->id,
                    'text'       => $c->title . "\n" . $c->content,
                    'chunk_type' => $c->type,
                    'source'     => $c->source,
                ])->values()->toArray(),
            ];

            try {
                $response = Http::timeout(30)->post("{$aiServiceUrl}/memory/sync", $payload);

                if ($response->successful()) {
                    $synced = $response->json('synced', 0);
                    $totalSynced += $synced;
                    $this->info("  ✅ {$synced} chunks sincronizados");
                } else {
                    $errors[] = "Usuario #{$user->id}: HTTP {$response->status()} — {$response->body()}";
                    $this->error("  ❌ Error HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                $errors[] = "Usuario #{$user->id}: {$e->getMessage()}";
                $this->error("  ❌ {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("RESUMEN");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Chunks sincronizados: {$totalSynced}");

        if (!empty($errors)) {
            $this->error('Errores: ' . count($errors));
            foreach ($errors as $err) {
                $this->error("  {$err}");
            }
            return 1;
        }

        $this->info('Sync completado.');
        return 0;
    }

    private function checkAiServiceHealth(string $url): bool
    {
        try {
            $response = Http::timeout(5)->get("{$url}/health");
            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }
}
