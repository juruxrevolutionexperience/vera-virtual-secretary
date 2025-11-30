<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'email_address',
        'display_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'sync_enabled',
        'sync_frequency',
        'last_sync_at',
        'last_sync_attempt_at',
        'sync_status',
        'sync_error',
        'total_emails_synced',
        'provider_user_id',
        'provider_metadata',
        'sync_sent',
        'sync_drafts',
        'sync_trash',
        'sync_from_date',
        'initial_sync_completed_at',
        'total_expected_emails',
        'synced_emails_count',
        'last_history_id',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'sync_sent' => 'boolean',
        'sync_drafts' => 'boolean',
        'sync_trash' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
        'sync_from_date' => 'date',
        'provider_metadata' => 'array',
        'sync_frequency' => 'integer',
        'total_emails_synced' => 'integer',
        'initial_sync_completed_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Usuario propietario de la cuenta
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Logs de sincronización
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    /**
     * Emails sincronizados desde esta cuenta
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'email_account_id');
    }

    /**
     * Encriptar access token al guardar
     */
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    /**
     * Desencriptar access token al leer
     */
    public function getAccessTokenAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encriptar refresh token al guardar
     */
    public function setRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Desencriptar refresh token al leer
     */
    public function getRefreshTokenAttribute($value)
    {
        if (!$value)
            return null;

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verificar si el token ha expirado
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at)
            return false;

        return $this->token_expires_at->isPast();
    }

    /**
     * Verificar si necesita sincronización
     */
    public function needsSync(): bool
    {
        if (!$this->sync_enabled)
            return false;
        if ($this->sync_status === 'paused')
            return false;
        if (!$this->last_sync_at)
            return true;

        $minutesSinceLastSync = now()->diffInMinutes($this->last_sync_at);
        return $minutesSinceLastSync >= $this->sync_frequency;
    }

    /**
     * Marcar sincronización como exitosa
     */
    public function markSyncSuccess(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_sync_attempt_at' => now(),
            'sync_status' => 'active',
            'sync_error' => null,
        ]);
    }

    /**
     * Marcar sincronización como fallida
     */
    public function markSyncFailed(string $error): void
    {
        $this->update([
            'last_sync_attempt_at' => now(),
            'sync_status' => 'error',
            'sync_error' => $error,
        ]);
    }

    /**
     * Scope para cuentas activas
     */
    public function scopeActive($query)
    {
        return $query->where('sync_enabled', true)
            ->where('sync_status', 'active');
    }

    /**
     * Scope por proveedor
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Obtener el nombre del proveedor formateado
     */
    public function getProviderNameAttribute(): string
    {
        return match ($this->provider) {
            'gmail' => 'Gmail',
            'outlook' => 'Outlook',
            default => ucfirst($this->provider),
        };
    }

    /**
     * Obtener color del proveedor para UI
     */
    public function getProviderColorAttribute(): string
    {
        return match ($this->provider) {
            'gmail' => '#EA4335',
            'outlook' => '#0078D4',
            default => '#6366f1',
        };
    }

    /**
     * Verificar si está en sincronización inicial
     */
    public function isInitialSyncing(): bool
    {
        return $this->sync_status === 'initial_sync_running';
    }

    /**
     * Verificar si está en sincronización incremental
     */
    public function isIncrementalSyncing(): bool
    {
        return $this->sync_status === 'incremental_sync_running';
    }

    /**
     * Verificar si está sincronizando (cualquier tipo)
     */
    public function isSyncing(): bool
    {
        return in_array($this->sync_status, ['initial_sync_running', 'incremental_sync_running']);
    }

    /**
     * Verificar si completó la sincronización inicial
     */
    public function hasCompletedInitialSync(): bool
    {
        return !is_null($this->initial_sync_completed_at);
    }

    /**
     * Obtener porcentaje de progreso de sincronización
     */
    public function getSyncProgressPercentage(): float
    {
        if (!$this->isSyncing()) {
            return 100.0;
        }

        if ($this->total_expected_emails <= 0) {
            return 0.0;
        }

        $percentage = ($this->synced_emails_count / $this->total_expected_emails) * 100;
        return round(min($percentage, 100), 2);
    }

    /**
     * Obtener datos de progreso para API
     */
    public function getSyncProgressData(): array
    {
        return [
            'is_syncing' => $this->isSyncing(),
            'sync_status' => $this->sync_status,
            'sync_type' => $this->isInitialSyncing() ? 'initial' : ($this->isIncrementalSyncing() ? 'incremental' : 'none'),
            'has_completed_initial_sync' => $this->hasCompletedInitialSync(),
            'initial_sync_completed_at' => $this->initial_sync_completed_at?->toISOString(),
            'synced_count' => $this->synced_emails_count ?? 0,
            'total_expected' => $this->total_expected_emails ?? 0,
            'progress_percentage' => $this->getSyncProgressPercentage(),
            'last_sync_at' => $this->last_sync_at?->toISOString(),
            'sync_error' => $this->sync_error,
        ];
    }
}
