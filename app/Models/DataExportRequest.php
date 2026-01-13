<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DataExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'export_format',
        'data_types',
        'requested_at',
        'started_at',
        'completed_at',
        'file_path',
        'file_name',
        'file_size',
        'expires_at',
        'download_token',
        'download_count',
        'error_message',
    ];

    protected $casts = [
        'data_types' => 'array',
        'requested_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            // Generar token de descarga seguro
            $request->download_token = Str::random(64);
            $request->requested_at = now();
        });
    }

    /**
     * Usuario propietario de la solicitud
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Solicitudes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
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
     * Solicitudes fallidas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Solicitudes expiradas (archivo ya no disponible)
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'completed')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Solicitudes disponibles para descarga
     */
    public function scopeAvailableForDownload($query)
    {
        return $query->where('status', 'completed')
            ->whereNotNull('file_path')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

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
            'data_export_started',
            "Exportación de datos iniciada (ID: {$this->id})"
        );
    }

    /**
     * Marcar como completada
     */
    public function markAsCompleted(string $filePath, string $fileName, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'expires_at' => now()->addDays(7), // Expira en 7 días
        ]);

        AuditLog::logData(
            'data_export_completed',
            "Exportación de datos completada (ID: {$this->id}, Size: " . $this->getFileSizeFormatted() . ")"
        );
    }

    /**
     * Marcar como fallida
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        AuditLog::logData(
            'data_export_failed',
            "Exportación de datos falló (ID: {$this->id}): {$errorMessage}",
            'warning'
        );
    }

    /**
     * Incrementar contador de descargas
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');

        AuditLog::logData(
            'data_export_downloaded',
            "Archivo de exportación descargado (ID: {$this->id}, Descargas: {$this->download_count})"
        );
    }

    /**
     * Verificar si está disponible para descarga
     */
    public function isAvailableForDownload(): bool
    {
        return $this->status === 'completed'
            && !is_null($this->file_path)
            && file_exists($this->file_path)
            && (is_null($this->expires_at) || $this->expires_at > now());
    }

    /**
     * Verificar si ha expirado
     */
    public function isExpired(): bool
    {
        return $this->status === 'completed'
            && !is_null($this->expires_at)
            && $this->expires_at < now();
    }

    /**
     * Obtener tamaño de archivo formateado
     */
    public function getFileSizeFormatted(): string
    {
        if (is_null($this->file_size)) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Obtener URL de descarga
     */
    public function getDownloadUrl(): string
    {
        return route('privacy.data-export.download', ['token' => $this->download_token]);
    }

    /**
     * Limpiar archivos antiguos (llamar desde comando programado)
     */
    public static function cleanupExpiredFiles(): int
    {
        $expiredRequests = self::expired()->get();
        $count = 0;

        foreach ($expiredRequests as $request) {
            if ($request->file_path && file_exists($request->file_path)) {
                unlink($request->file_path);
                $count++;
            }
            $request->update(['file_path' => null]);
        }

        if ($count > 0) {
            AuditLog::logData(
                'data_export_cleanup',
                "Limpieza de archivos expirados: {$count} archivos eliminados"
            );
        }

        return $count;
    }
}
