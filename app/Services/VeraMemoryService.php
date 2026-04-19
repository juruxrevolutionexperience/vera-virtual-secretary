<?php

namespace App\Services;

use App\Models\User;
use App\Models\VeraConfig;
use App\Models\VeraMemoryChunk;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * VeraMemoryService
 *
 * Gestiona la memoria de largo plazo de Vera por usuario.
 * Se encarga de indexar la configuración del usuario, aprender
 * de las respuestas exitosas y mantener la memoria actualizada.
 */
class VeraMemoryService
{
    /**
     * Indexar (o re-indexar) toda la configuración del usuario en memoria.
     * Se llama cuando el usuario guarda su config de Vera o desde el comando Artisan.
     */
    public function indexUserConfig(User $user): array
    {
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config) {
            Log::warning('VeraMemoryService: usuario sin VeraConfig', ['user_id' => $user->id]);
            return ['indexed' => 0, 'skipped' => 0];
        }

        $indexed = 0;
        $skipped = 0;

        // 1. Información de negocio (perfil del usuario)
        if (!empty($config->business_context)) {
            $this->upsertChunk($user->id, [
                'type'          => 'business_info',
                'title'         => 'Perfil del negocio y contexto',
                'content'       => $config->business_context,
                'keywords_text' => $this->extractKeywords($config->business_context),
                'relevance_tags'=> ['all'],
                'source'        => 'user_config',
            ]);
            $indexed++;
        }

        // 2. Estilo de comunicación
        $writingStyle = $config->writing_style;
        if (!empty($writingStyle)) {
            $styleText = $this->formatWritingStyle($writingStyle);
            $this->upsertChunk($user->id, [
                'type'          => 'communication_style',
                'title'         => 'Estilo de escritura del usuario',
                'content'       => $styleText,
                'keywords_text' => 'estilo tono formal informal saludo despedida comunicación',
                'relevance_tags'=> ['all'],
                'source'        => 'user_config',
            ]);
            $indexed++;
        }

        // 3. Prompt personalizado (si lo tiene)
        if (!empty($config->custom_system_prompt)) {
            $this->upsertChunk($user->id, [
                'type'          => 'business_info',
                'title'         => 'Instrucciones personalizadas de Vera',
                'content'       => $config->custom_system_prompt,
                'keywords_text' => $this->extractKeywords($config->custom_system_prompt),
                'relevance_tags'=> ['all'],
                'source'        => 'user_config',
            ]);
            $indexed++;
        }

        Log::info('✅ VeraMemoryService: config indexada', [
            'user_id' => $user->id,
            'indexed' => $indexed,
        ]);

        return ['indexed' => $indexed, 'skipped' => $skipped];
    }

    /**
     * Añadir un chunk de FAQ manualmente
     */
    public function addFAQ(int $userId, string $question, string $answer, array $keywords = []): VeraMemoryChunk
    {
        $content = "Pregunta: {$question}\nRespuesta: {$answer}";
        $content = mb_substr($content, 0, 2000);

        $kw = !empty($keywords)
            ? implode(' ', $keywords)
            : $this->extractKeywords($question . ' ' . $answer);

        return VeraMemoryChunk::create([
            'user_id'        => $userId,
            'type'           => 'faq',
            'title'          => mb_substr($question, 0, 100),
            'content'        => $content,
            'keywords_text'  => $kw,
            'relevance_tags' => [],
            'source'         => 'manual',
            'is_active'      => true,
        ]);
    }

    /**
     * Añadir info de producto/servicio
     */
    public function addProductInfo(int $userId, string $name, string $description, array $keywords = []): VeraMemoryChunk
    {
        $description = mb_substr($description, 0, 2000);

        $kw = !empty($keywords)
            ? implode(' ', $keywords)
            : $this->extractKeywords($name . ' ' . $description);

        return VeraMemoryChunk::create([
            'user_id'        => $userId,
            'type'           => 'product_info',
            'title'          => $name,
            'content'        => $description,
            'keywords_text'  => $kw,
            'relevance_tags' => [],
            'source'         => 'manual',
            'is_active'      => true,
        ]);
    }

    /**
     * Guardar una respuesta exitosa para que Vera aprenda el patrón.
     * Solo guarda las últimas 20 para no inflar ChromaDB.
     */
    public function savePastResponse(int $userId, string $trigger, string $response, string $context = ''): void
    {
        $count = VeraMemoryChunk::where('user_id', $userId)->where('type', 'past_response')->count();
        if ($count >= 20) {
            VeraMemoryChunk::where('user_id', $userId)
                ->where('type', 'past_response')
                ->orderBy('created_at')
                ->first()
                ?->delete();
        }

        $content = mb_substr("Situación: {$trigger}\nRespuesta exitosa: {$response}", 0, 2000);

        $chunk = VeraMemoryChunk::create([
            'user_id'        => $userId,
            'type'           => 'past_response',
            'title'          => mb_substr($trigger, 0, 100),
            'content'        => $content,
            'keywords_text'  => $this->extractKeywords($trigger . ' ' . $context),
            'relevance_tags' => $context ? [$context] : [],
            'source'         => 'auto_detected',
            'is_active'      => true,
        ]);

        $this->syncChunkToAiService($userId, $chunk);
    }

    /**
     * Guardar insight semanal generado automáticamente
     */
    public function saveWeeklyInsight(int $userId, string $insight, array $tags = []): VeraMemoryChunk
    {
        // Solo guardamos el insight más reciente (sobreescribir el anterior)
        return VeraMemoryChunk::updateOrCreate(
            ['user_id' => $userId, 'type' => 'weekly_insight', 'title' => 'Resumen semanal de Vera'],
            [
                'content'        => $insight,
                'keywords_text'  => $this->extractKeywords($insight),
                'relevance_tags' => $tags,
                'source'         => 'auto_detected',
                'is_active'      => true,
            ]
        );
    }

    /**
     * Obtener estadísticas de memoria del usuario
     */
    public function getStats(int $userId): array
    {
        $chunks = VeraMemoryChunk::forUser($userId)->active()->get();

        return [
            'total_chunks'    => $chunks->count(),
            'by_type'         => $chunks->groupBy('type')->map->count()->toArray(),
            'most_used'       => $chunks->sortByDesc('usage_count')->first()?->title,
            'total_uses'      => $chunks->sum('usage_count'),
        ];
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function upsertChunk(int $userId, array $data): VeraMemoryChunk
    {
        if (isset($data['content'])) {
            $data['content'] = mb_substr($data['content'], 0, 2000);
        }

        $chunk = VeraMemoryChunk::updateOrCreate(
            ['user_id' => $userId, 'type' => $data['type'], 'title' => $data['title']],
            array_merge($data, ['is_active' => true])
        );

        // Sincronizar a ChromaDB (ai-service) de forma silenciosa
        $this->syncChunkToAiService($userId, $chunk);

        return $chunk;
    }

    /**
     * Envía un chunk a ChromaDB vía ai-service.
     * Si ai-service no está disponible, MySQL sigue funcionando (fallback).
     */
    private function syncChunkToAiService(int $userId, VeraMemoryChunk $chunk): void
    {
        $aiServiceUrl = config('services.ai_service.url');
        if (!$aiServiceUrl) {
            return; // ai-service no configurado — modo fallback MySQL
        }

        try {
            Http::timeout(5)->withHeaders(['X-Service-Token' => config('services.ai_service.secret')])->post("{$aiServiceUrl}/memory/sync", [
                'user_id' => (string) $userId,
                'chunks'  => [[
                    'chunk_id'   => (string) $chunk->id,
                    'text'       => $chunk->title . "\n" . $chunk->content,
                    'chunk_type' => $chunk->type,
                    'source'     => $chunk->source,
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('VeraMemoryService: no se pudo sincronizar chunk a ChromaDB', [
                'chunk_id' => $chunk->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Desactiva un chunk en MySQL y en ChromaDB.
     */
    public function deactivateChunk(int $userId, int $chunkId): void
    {
        VeraMemoryChunk::where('user_id', $userId)->where('id', $chunkId)->update(['is_active' => false]);

        $aiServiceUrl = config('services.ai_service.url');
        if (!$aiServiceUrl) {
            return;
        }

        try {
            Http::timeout(5)->withHeaders(['X-Service-Token' => config('services.ai_service.secret')])->delete("{$aiServiceUrl}/memory/chunk/{$userId}/{$chunkId}");
        } catch (\Exception $e) {
            Log::warning('VeraMemoryService: no se pudo desactivar chunk en ChromaDB', [
                'chunk_id' => $chunkId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function formatWritingStyle(array $style): string
    {
        $lines = [];

        if (!empty($style['tone'])) {
            $lines[] = "Tono: {$style['tone']}";
        }

        if (isset($style['formality_score'])) {
            $level = $style['formality_score'] > 0.7 ? 'muy formal' : ($style['formality_score'] > 0.4 ? 'semi-formal' : 'casual');
            $lines[] = "Formalidad: {$level} ({$style['formality_score']})";
        }

        if (!empty($style['greetings'])) {
            $lines[] = 'Saludos típicos: ' . implode(', ', (array)$style['greetings']);
        }

        if (!empty($style['closings'])) {
            $lines[] = 'Despedidas típicas: ' . implode(', ', (array)$style['closings']);
        }

        if (isset($style['uses_emojis'])) {
            $lines[] = 'Usa emojis: ' . ($style['uses_emojis'] ? 'sí' : 'no');
        }

        if (!empty($style['avg_length'])) {
            $lines[] = "Longitud media de emails: {$style['avg_length']} caracteres";
        }

        return implode("\n", $lines);
    }

    private function extractKeywords(string $text): string
    {
        // Minúsculas y limpieza básica
        $text = strtolower(strip_tags($text));
        $text = preg_replace('/[^a-záéíóúüñ\s]/u', ' ', $text);

        // Eliminar stopwords
        $stopwords = ['que', 'los', 'las', 'una', 'uno', 'con', 'por', 'para', 'del', 'este', 'esta', 'ese', 'esa', 'hay', 'ser', 'han', 'era', 'fue', 'son', 'como', 'pero', 'más', 'sobre', 'también', 'cuando', 'donde', 'aunque', 'muy', 'bien', 'mal', 'así', 'sin', 'sus', 'les'];

        $words = array_filter(
            explode(' ', $text),
            fn($w) => mb_strlen(trim($w)) >= 4 && !in_array(trim($w), $stopwords)
        );

        return implode(' ', array_unique(array_slice($words, 0, 30)));
    }
}
