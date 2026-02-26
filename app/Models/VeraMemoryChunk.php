<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class VeraMemoryChunk extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'content',
        'keywords_text',
        'relevance_tags',
        'source',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'relevance_tags' => 'array',
        'is_active'      => 'boolean',
        'last_used_at'   => 'datetime',
        'usage_count'    => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ─── Búsqueda por relevancia ─────────────────────────────────────────────

    /**
     * Buscar chunks relevantes para un email dado.
     * Usa FULLTEXT search en keywords_text + content.
     *
     * @param int    $userId
     * @param string $searchText  Asunto + cuerpo del email (primeros 300 chars)
     * @param int    $limit       Máximo de chunks a devolver
     * @return \Illuminate\Support\Collection
     */
    public static function findRelevant(int $userId, string $searchText, int $limit = 5): \Illuminate\Support\Collection
    {
        // Limpiar el texto de búsqueda para FULLTEXT
        $cleanText = self::cleanForFulltext($searchText);

        if (empty($cleanText)) {
            // Sin texto útil → devolver los más usados del usuario
            return self::forUser($userId)
                ->active()
                ->orderByDesc('usage_count')
                ->limit($limit)
                ->get();
        }

        try {
            // FULLTEXT MATCH AGAINST con modo BOOLEAN para más control
            $results = self::forUser($userId)
                ->active()
                ->selectRaw('*, MATCH(keywords_text, title) AGAINST(? IN BOOLEAN MODE) as relevance_kw, MATCH(content) AGAINST(? IN BOOLEAN MODE) as relevance_content', [
                    $cleanText,
                    $cleanText,
                ])
                ->havingRaw('(relevance_kw + relevance_content) > 0')
                ->orderByRaw('(relevance_kw * 2 + relevance_content) DESC') // keywords pesan el doble
                ->limit($limit)
                ->get();

            // Si no hay resultados por FULLTEXT, fallback a los más usados
            if ($results->isEmpty()) {
                return self::forUser($userId)
                    ->active()
                    ->orderByDesc('usage_count')
                    ->limit($limit)
                    ->get();
            }

            return $results;

        } catch (\Exception $e) {
            // Si FULLTEXT falla (ej: índice no listo), fallback silencioso
            return self::forUser($userId)
                ->active()
                ->orderByDesc('usage_count')
                ->limit($limit)
                ->get();
        }
    }

    /**
     * Marcar chunk como usado (actualiza estadísticas de uso)
     */
    public function markUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Limpiar texto para búsqueda FULLTEXT MySQL
     * Elimina palabras muy cortas y caracteres especiales
     */
    private static function cleanForFulltext(string $text): string
    {
        // Minúsculas
        $text = strtolower($text);

        // Eliminar HTML si quedara
        $text = strip_tags($text);

        // Eliminar caracteres especiales excepto espacios y letras
        $text = preg_replace('/[^a-záéíóúüñ\s]/u', ' ', $text);

        // Eliminar palabras de menos de 3 caracteres (stopwords cortas)
        $words = array_filter(explode(' ', $text), fn($w) => mb_strlen(trim($w)) >= 3);

        // Eliminar stopwords comunes en español
        $stopwords = ['que', 'los', 'las', 'una', 'uno', 'con', 'por', 'para', 'del', 'este', 'esta', 'ese', 'esa', 'hay', 'ser', 'han', 'era', 'fue', 'son', 'como', 'pero', 'más', 'sobre', 'también', 'cuando', 'donde', 'aunque'];
        $words = array_filter($words, fn($w) => !in_array(trim($w), $stopwords));

        // Limitar a 20 palabras clave para no saturar FULLTEXT
        $words = array_slice(array_unique($words), 0, 20);

        return implode(' ', $words);
    }
}
