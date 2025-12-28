<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VeraConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_type',
        'title',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relaciones
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(VeraConversationMessage::class, 'conversation_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('conversation_type', $type);
    }

    /**
     * Helpers
     */

    /**
     * Agregar un mensaje a la conversación
     */
    public function addMessage(string $role, string $content, array $metadata = []): VeraConversationMessage
    {
        return $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Obtener el último mensaje
     */
    public function getLastMessage(): ?VeraConversationMessage
    {
        return $this->messages()->orderByDesc('created_at')->first();
    }

    /**
     * Obtener total de tokens consumidos en la conversación
     */
    public function getTotalTokens(): int
    {
        return $this->messages()->sum('tokens_consumed');
    }

    /**
     * Obtener costo total de la conversación
     */
    public function getTotalCost(): float
    {
        return $this->messages()->sum('cost');
    }

    /**
     * Archivar conversación
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Generar título automático basado en el primer mensaje
     */
    public function generateTitle(): void
    {
        $firstUserMessage = $this->messages()
            ->where('role', 'user')
            ->orderBy('created_at')
            ->first();

        if ($firstUserMessage) {
            $title = substr($firstUserMessage->content, 0, 50);
            if (strlen($firstUserMessage->content) > 50) {
                $title .= '...';
            }
            $this->update(['title' => $title]);
        }
    }
}
