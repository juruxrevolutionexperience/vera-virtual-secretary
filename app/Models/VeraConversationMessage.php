<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_consumed',
        'cost',
        'metadata',
        'user_feedback',
    ];

    protected $casts = [
        'tokens_consumed' => 'integer',
        'cost' => 'decimal:6',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relaciones
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(VeraConversation::class, 'conversation_id');
    }

    /**
     * Scopes
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Accessors
     */
    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    public function isSystemMessage(): bool
    {
        return $this->role === 'system';
    }
}
