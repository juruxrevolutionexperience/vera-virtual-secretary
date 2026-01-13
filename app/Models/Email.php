<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
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
        // Fase 2: Control de límites
        'processed_this_billing_cycle',
        'last_processed_at',
        'vera_ai_skipped',
        // Fase 1: Workflow y gestión avanzada
        'workflow_status',
        'is_draft',
        'is_deleted',
        'deleted_at_custom',
        'due_date',
        'snoozed_until',
        'last_activity_at',
        'requires_action',
        'action_notes',
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
        // Fase 2: Control de límites
        'processed_this_billing_cycle' => 'boolean',
        'last_processed_at' => 'datetime',
        'vera_ai_skipped' => 'boolean',
        // Fase 1: Workflow y gestión avanzada
        'is_draft' => 'boolean',
        'is_deleted' => 'boolean',
        'deleted_at_custom' => 'datetime',
        'due_date' => 'datetime',
        'snoozed_until' => 'datetime',
        'last_activity_at' => 'datetime',
        'requires_action' => 'boolean',
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

    /**
     * Relación con auto-responder history (Fase 1B)
     */
    public function autoResponderHistory(): HasMany
    {
        return $this->hasMany(AutoResponderHistory::class);
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

    // ==========================================
    // FASE 1: WORKFLOW Y CARPETAS
    // ==========================================

    /**
     * Carpetas asignadas a este email (many-to-many)
     */
    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(EmailFolder::class, 'email_folder_assignments')
            ->withPivot('assigned_by', 'assigned_at', 'notes')
            ->withTimestamps();
    }

    // ==========================================
    // SCOPES PARA WORKFLOW
    // ==========================================

    /**
     * Emails en un estado específico de workflow
     */
    public function scopeInWorkflow($query, string $status)
    {
        return $query->where('workflow_status', $status);
    }

    /**
     * Emails pendientes de validación
     */
    public function scopePendingValidation($query)
    {
        return $query->where('workflow_status', 'pending_validation');
    }

    /**
     * Emails pendientes de respuesta
     */
    public function scopePendingResponse($query)
    {
        return $query->where('workflow_status', 'pending_response');
    }

    /**
     * Emails en progreso
     */
    public function scopeInProgress($query)
    {
        return $query->where('workflow_status', 'in_progress');
    }

    /**
     * Emails que requieren acción
     */
    public function scopeRequiringAction($query)
    {
        return $query->where('requires_action', true);
    }

    /**
     * Emails con fecha de vencimiento vencida
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('workflow_status', '!=', 'completed')
            ->where('workflow_status', '!=', 'archived');
    }

    /**
     * Emails con fecha de vencimiento próxima (próximas 24 horas)
     */
    public function scopeDueSoon($query, int $hours = 24)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addHours($hours))
            ->where('workflow_status', '!=', 'completed')
            ->where('workflow_status', '!=', 'archived');
    }

    /**
     * Emails en modo snooze (pospuestos)
     */
    public function scopeSnoozed($query)
    {
        return $query->whereNotNull('snoozed_until')
            ->where('snoozed_until', '>', now());
    }

    /**
     * Emails que deben "despertar" del snooze
     */
    public function scopeReadyToWakeup($query)
    {
        return $query->whereNotNull('snoozed_until')
            ->where('snoozed_until', '<=', now());
    }

    /**
     * Borradores
     */
    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Emails eliminados (papelera)
     */
    public function scopeDeleted($query)
    {
        return $query->where('is_deleted', true);
    }

    /**
     * Emails activos (no eliminados, no borradores)
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false)
            ->where('is_draft', false);
    }

    // ==========================================
    // MÉTODOS DE WORKFLOW
    // ==========================================

    /**
     * Cambiar estado del workflow
     */
    public function setWorkflowStatus(string $status): void
    {
        $validStatuses = [
            'unread', 'pending_validation', 'pending_response',
            'in_progress', 'in_review', 'approved', 'completed',
            'archived', 'no_action_needed'
        ];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid workflow status: {$status}");
        }

        $this->update([
            'workflow_status' => $status,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Mover a una carpeta
     */
    public function moveToFolder(EmailFolder $folder, ?int $assignedBy = null, ?string $notes = null): void
    {
        $this->folders()->syncWithoutDetaching([
            $folder->id => [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
                'notes' => $notes,
            ]
        ]);

        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Remover de una carpeta
     */
    public function removeFromFolder(EmailFolder $folder): void
    {
        $this->folders()->detach($folder->id);
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Posponer email hasta una fecha (snooze)
     */
    public function snoozeUntil(\DateTimeInterface $dateTime): void
    {
        $this->update([
            'snoozed_until' => $dateTime,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * "Despertar" del snooze
     */
    public function wakeup(): void
    {
        $this->update([
            'snoozed_until' => null,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Establecer fecha de vencimiento
     */
    public function setDueDate(\DateTimeInterface $dueDate): void
    {
        $this->update([
            'due_date' => $dueDate,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Marcar como borrador
     */
    public function markAsDraft(): void
    {
        $this->update([
            'is_draft' => true,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Publicar borrador (convertir en email normal)
     */
    public function publishDraft(): void
    {
        $this->update([
            'is_draft' => false,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Mover a papelera (soft delete custom)
     */
    public function moveToTrash(): void
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at_custom' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Restaurar de la papelera
     */
    public function restoreFromTrash(): void
    {
        $this->update([
            'is_deleted' => false,
            'deleted_at_custom' => null,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Marcar como requiere acción
     */
    public function markAsRequiringAction(string $notes = null): void
    {
        $this->update([
            'requires_action' => true,
            'action_notes' => $notes,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Marcar como acción completada
     */
    public function markActionCompleted(): void
    {
        $this->update([
            'requires_action' => false,
            'action_notes' => null,
            'workflow_status' => 'completed',
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Verificar si está vencido
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date < now() &&
               !in_array($this->workflow_status, ['completed', 'archived']);
    }

    /**
     * Verificar si está en snooze
     */
    public function isSnoozed(): bool
    {
        return $this->snoozed_until && $this->snoozed_until > now();
    }

    // ==================== SCOPES DE MULTI-TENANCY ====================

    /**
     * Relación con organización
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope: Filtrar por organización
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: Emails compartidos con un usuario
     */
    public function scopeSharedWith($query, $userId)
    {
        return $query->whereHas('shares', function ($q) use ($userId) {
            $q->where('shared_with', $userId)
              ->where(function ($subQ) {
                  $subQ->whereNull('expires_at')
                       ->orWhere('expires_at', '>', now());
              });
        });
    }

    /**
     * Relación con shares (emails compartidos)
     */
    public function shares(): HasMany
    {
        return $this->hasMany(EmailShare::class);
    }

    /**
     * Verificar si está compartido con un usuario
     */
    public function isSharedWith(User $user): bool
    {
        return $this->shares()
            ->where('shared_with', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
