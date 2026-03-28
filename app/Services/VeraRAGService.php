<?php

namespace App\Services;

use App\Models\VeraMemoryChunk;
use Illuminate\Support\Facades\Log;

/**
 * VeraRAGService — Retrieval Augmented Generation
 *
 * Busca en la memoria del usuario solo el contexto relevante
 * para el email actual. Así el prompt a Gemini pasa de 1.000 tokens
 * a ~300-400 tokens, con mayor precisión.
 *
 * Interfaz diseñada para poder sustituir MySQL por ChromaDB
 * o Pinecone en el futuro sin cambiar el resto del código.
 */
class VeraRAGService
{
    // Máximo de chunks que inyectamos en el prompt
    private const MAX_CHUNKS = 4;

    // Máximo de caracteres por chunk en el prompt (para controlar tokens)
    private const MAX_CHUNK_CHARS = 400;

    /**
     * Obtener contexto relevante para un mensaje/conversación.
     *
     * @param int    $userId
     * @param string $searchText  Texto libre para buscar en memoria (asunto, cuerpo, intención, etc.)
     * @return array  [
     *   'context_text'  => string  (listo para inyectar en el prompt),
     *   'chunks_used'   => int,
     *   'chunk_ids'     => array   (para marcarlos como usados),
     *   'token_estimate'=> int,
     * ]
     */
    public function getRelevantContext(int $userId, string $searchText): array
    {
        Log::debug('🔍 RAG buscando contexto relevante', [
            'user_id'     => $userId,
            'search_text' => substr($searchText, 0, 100) . '...',
        ]);

        // Buscar chunks relevantes
        $chunks = VeraMemoryChunk::findRelevant($userId, $searchText, self::MAX_CHUNKS);

        if ($chunks->isEmpty()) {
            Log::debug('📭 RAG: sin chunks relevantes', ['user_id' => $userId]);
            return $this->emptyContext();
        }

        // Construir el texto de contexto para el prompt
        $contextText = $this->buildContextText($chunks);

        // Estimar tokens (1 token ≈ 4 caracteres)
        $tokenEstimate = (int) ceil(strlen($contextText) / 4);

        Log::info('✅ RAG: contexto construido', [
            'user_id'        => $userId,
            'chunks_used'    => $chunks->count(),
            'token_estimate' => $tokenEstimate,
            'chunk_types'    => $chunks->pluck('type')->toArray(),
        ]);

        // Marcar chunks como usados (estadísticas)
        $chunks->each(fn($chunk) => $chunk->markUsed());

        return [
            'context_text'   => $contextText,
            'chunks_used'    => $chunks->count(),
            'chunk_ids'      => $chunks->pluck('id')->toArray(),
            'token_estimate' => $tokenEstimate,
        ];
    }

    /**
     * Construir el bloque de texto de contexto para el prompt
     */
    private function buildContextText(\Illuminate\Support\Collection $chunks): string
    {
        $lines = [];
        $lines[] = 'CONTEXTO RELEVANTE DE TU MEMORIA:';
        $lines[] = '─────────────────────────────────';

        foreach ($chunks as $chunk) {
            $label   = $this->typeLabel($chunk->type);
            $content = mb_substr($chunk->content, 0, self::MAX_CHUNK_CHARS);

            // Si se truncó, añadir elipsis
            if (mb_strlen($chunk->content) > self::MAX_CHUNK_CHARS) {
                $content .= '...';
            }

            $lines[] = "[{$label}] {$chunk->title}:";
            $lines[] = $content;
            $lines[] = '';
        }

        $lines[] = '─────────────────────────────────';

        return implode("\n", $lines);
    }

    /**
     * Etiqueta legible por tipo de chunk
     */
    private function typeLabel(string $type): string
    {
        return match ($type) {
            'business_info'       => 'EMPRESA',
            'faq'                 => 'FAQ',
            'communication_style' => 'ESTILO',
            'past_response'       => 'RESPUESTA PREVIA',
            'weekly_insight'      => 'INSIGHT SEMANAL',
            'contact_profile'     => 'PERFIL CONTACTO',
            'product_info'        => 'PRODUCTO',
            'policy'              => 'POLÍTICA',
            'user_profile'        => 'PERFIL USUARIO',
            default               => strtoupper($type),
        };
    }

    private function emptyContext(): array
    {
        return [
            'context_text'   => '',
            'chunks_used'    => 0,
            'chunk_ids'      => [],
            'token_estimate' => 0,
        ];
    }
}
