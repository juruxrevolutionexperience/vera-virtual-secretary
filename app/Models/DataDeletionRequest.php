<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataDeletionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'deletion_type',
        'data_types',
        'requested_at',
        'approved_at',
        'approved_by',
        'started_at',
        'completed_at',
        'reason',
        'admin_notes',
        'deletion_summary',
        'error_message',
    ];

    protected $casts = [
        'data_types' => 'array',
        'deletion_summary' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            $request->requested_at = now();
        });
    }

    /**
     * Usuario que solicita la eliminación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Administrador que aprobó la solicitud
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Solicitudes pendientes de aprobación
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Solicitudes aprobadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Solicitudes en proceso
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Solicitudes completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Solicitudes canceladas
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Eliminación completa de cuenta
     */
    public function scopeFullAccount($query)
    {
        return $query->where('deletion_type', 'full_account');
    }

    /**
     * Eliminación parcial de datos
     */
    public function scopePartial($query)
    {
        return $query->where('deletion_type', 'partial');
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Aprobar solicitud
     */
    public function approve(int $approvedBy, ?string $adminNotes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'admin_notes' => $adminNotes,
        ]);

        AuditLog::logData(
            'data_deletion_approved',
            "Solicitud de eliminación aprobada (ID: {$this->id}, User: {$this->user->email})",
            'warning'
        );
    }

    /**
     * Marcar como en proceso
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        AuditLog::logData(
            'data_deletion_started',
            "Eliminación de datos iniciada (ID: {$this->id}, Type: {$this->deletion_type})",
            'critical'
        );
    }

    /**
     * Marcar como completada
     */
    public function markAsCompleted(array $deletionSummary): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'deletion_summary' => $deletionSummary,
        ]);

        AuditLog::logData(
            'data_deletion_completed',
            "Eliminación de datos completada (ID: {$this->id})",
            'critical'
        );
    }

    /**
     * Cancelar solicitud
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'admin_notes' => $reason,
        ]);

        AuditLog::logData(
            'data_deletion_cancelled',
            "Solicitud de eliminación cancelada (ID: {$this->id})"
        );
    }

    /**
     * Marcar como fallida
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'pending', // Volver a pendiente para reintentar
            'error_message' => $errorMessage,
        ]);

        AuditLog::logData(
            'data_deletion_failed',
            "Eliminación de datos falló (ID: {$this->id}): {$errorMessage}",
            'critical'
        );
    }

    /**
     * Verificar si requiere aprobación de admin
     */
    public function requiresApproval(): bool
    {
        // Eliminaciones completas siempre requieren aprobación
        return $this->deletion_type === 'full_account';
    }

    /**
     * Verificar si puede ser procesada
     */
    public function canBeProcessed(): bool
    {
        return in_array($this->status, ['approved', 'pending'])
            && (!$this->requiresApproval() || $this->status === 'approved');
    }

    /**
     * Obtener lista de datos a eliminar
     */
    public function getDataTypesToDelete(): array
    {
        if ($this->deletion_type === 'full_account') {
            return ['emails', 'contacts', 'folders', 'templates', 'schedules', 'auto_responders', 'vera_data', 'attachments'];
        }

        return $this->data_types ?? [];
    }

    /**
     * Tipos de eliminación disponibles
     */
    public static function deletionTypes(): array
    {
        return [
            'partial' => 'Eliminación Parcial',
            'full_account' => 'Eliminación Completa de Cuenta',
        ];
    }

    /**
     * Tipos de datos disponibles para eliminar
     */
    public static function availableDataTypes(): array
    {
        return [
            'emails' => 'Emails',
            'contacts' => 'Contactos',
            'folders' => 'Carpetas',
            'templates' => 'Plantillas',
            'schedules' => 'Emails Programados',
            'auto_responders' => 'Auto-respuestas',
            'vera_data' => 'Datos de Vera AI',
            'attachments' => 'Adjuntos',
        ];
    }
}
