<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailFolderShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_folder_id',
        'shared_with',
        'shared_by',
        'permission',
        'can_share',
        'notes',
        'expires_at',
    ];

    protected $casts = [
        'can_share' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Carpeta compartida
     */
    public function emailFolder(): BelongsTo
    {
        return $this->belongsTo(EmailFolder::class);
    }

    /**
     * Usuario con quien se compartió
     */
    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    /**
     * Usuario que compartió
     */
    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Scope: Compartidas con un usuario específico
     */
    public function scopeSharedWith($query, $userId)
    {
        return $query->where('shared_with', $userId);
    }

    /**
     * Scope: Compartidas por un usuario específico
     */
    public function scopeSharedBy($query, $userId)
    {
        return $query->where('shared_by', $userId);
    }

    /**
     * Scope: No expiradas
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Verificar si tiene permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'view' => ['view'],
            'edit' => ['view', 'edit'],
            'delete' => ['view', 'edit', 'delete'],
        ];

        return in_array($permission, $permissions[$this->permission] ?? []);
    }

    /**
     * Verificar si está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar si es válido (no expirado)
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}
