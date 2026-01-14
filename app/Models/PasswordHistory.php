<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class PasswordHistory extends Model
{
    public $timestamps = false; // Solo created_at

    protected $fillable = [
        'user_id',
        'password',
        'ip_address',
        'user_agent',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Registrar cambio de contraseña
     */
    public static function record(
        int $userId,
        string $hashedPassword,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $changedBy = 'user'
    ): self {
        return static::create([
            'user_id' => $userId,
            'password' => $hashedPassword,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);
    }

    /**
     * Verificar si una contraseña ya fue usada previamente
     */
    public static function wasUsedBefore(int $userId, string $plainPassword, int $count = 5): bool
    {
        $recentPasswords = static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($count)
            ->get();

        foreach ($recentPasswords as $history) {
            if (Hash::check($plainPassword, $history->password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limpiar historial antiguo (mantener solo los últimos X)
     */
    public static function cleanOldHistory(int $userId, int $keepCount = 10): int
    {
        $historyIds = static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($keepCount)
            ->pluck('id');

        return static::where('user_id', $userId)
            ->whereNotIn('id', $historyIds)
            ->delete();
    }

    /**
     * Obtener historial de contraseñas de un usuario
     */
    public static function getUserHistory(int $userId, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($history) {
                return [
                    'changed_at' => $history->created_at,
                    'changed_by' => $history->changed_by,
                    'ip_address' => $history->ip_address,
                ];
            });
    }

    /**
     * Obtener estadísticas de cambios de contraseña
     */
    public static function getStats(int $userId): array
    {
        $history = static::where('user_id', $userId)->get();

        $bySource = $history->groupBy('changed_by')->map->count();

        return [
            'total_changes' => $history->count(),
            'by_source' => $bySource->toArray(),
            'last_change' => $history->first()?->created_at,
            'average_days_between_changes' => static::getAverageDaysBetweenChanges($userId),
        ];
    }

    /**
     * Calcular promedio de días entre cambios de contraseña
     */
    private static function getAverageDaysBetweenChanges(int $userId): ?float
    {
        $changes = static::where('user_id', $userId)
            ->orderBy('created_at')
            ->pluck('created_at');

        if ($changes->count() < 2) {
            return null;
        }

        $daysDiffs = [];
        for ($i = 1; $i < $changes->count(); $i++) {
            $daysDiffs[] = $changes[$i]->diffInDays($changes[$i - 1]);
        }

        return count($daysDiffs) > 0 ? array_sum($daysDiffs) / count($daysDiffs) : null;
    }
}
