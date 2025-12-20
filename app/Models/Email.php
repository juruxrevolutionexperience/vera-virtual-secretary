<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'email_account_id',
        'email_category_id',
        'message_id',
        'thread_id',
        'provider_message_id',  // ← IMPORTANTE: ID del proveedor
        'provider_thread_id',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'to',
        'cc',
        'bcc',
        'subject',
        'snippet',  // ← NUEVO: Preview del email (primeros 250 caracteres)
        'body_html',  // Por ahora lo mantenemos, luego lo quitaremos
        'body_text',  // Por ahora lo mantenemos, luego lo quitaremos
        'priority',
        'is_read',
        'is_starred',
        'is_archived',
        'has_attachments',
        'attachments_count',
        'received_at',
        'read_at',
        'replied_at',
        'archived_at',
        'vera_summary',
        'vera_tags',
        'vera_sentiment_score',
        'vera_action_required',
        'vera_processed',
        'vera_processed_at',
        'provider',
        'provider_metadata',
        'status',
        'metadata',
    ];

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_archived' => 'boolean',
        'has_attachments' => 'boolean',
        'vera_processed' => 'boolean',
        'vera_tags' => 'array',
        'provider_metadata' => 'array',
        'metadata' => 'array',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'archived_at' => 'datetime',
        'vera_processed_at' => 'datetime',
        'vera_sentiment_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmailCategory::class, 'email_category_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    public function veraActions(): HasMany
    {
        return $this->hasMany(VeraEmailAction::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeInbox($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
                ->orWhere('body_text', 'like', "%{$search}%")
                ->orWhere('from_email', 'like', "%{$search}%")
                ->orWhere('from_name', 'like', "%{$search}%");
        });
    }

    public function scopeProcessedByVera($query)
    {
        return $query->where('vera_processed', true);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAsUnread(): void
    {
        $this->update(['is_read' => false, 'read_at' => null]);
    }

    public function archive(): void
    {
        $this->update(['is_archived' => true, 'archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['is_archived' => false, 'archived_at' => null]);
    }

    public function toggleStar(): void
    {
        $this->update(['is_starred' => !$this->is_starred]);
    }

    public function getExcerptAttribute(): string
    {
        $text = strip_tags($this->body_text ?? $this->body_html ?? '');
        return \Illuminate\Support\Str::limit($text, 150);
    }

    public function hasVeraResponse(): bool
    {
        return $this->veraActions()->where('action_type', 'replied')->exists();
    }

    public function getLastVeraAction()
    {
        return $this->veraActions()->latest()->first();
    }
}
