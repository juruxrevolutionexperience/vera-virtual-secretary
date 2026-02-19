<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_density',
        'timezone', // Timezone del usuario
        // === campos de billing añadidos a la tabla users ===
        'is_paid',
        'stripe_customer_id',
        'stripe_subscription_id',
        'current_period_end',
        // === campos de plan ===
        'plan_type',
        'monthly_email_limit',
        'billing_cycle_start',
        'plan_metadata',
        'plan_upgraded_at',
        // === tokens IA ===
        'monthly_tokens_used',
        'monthly_tokens_limit',
        'tokens_reset_at',
        // === 2FA ===
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_enabled_at',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_paid' => 'boolean',
            'current_period_end' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_enabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'tokens_reset_at' => 'date',
            'monthly_tokens_used' => 'integer',
            'monthly_tokens_limit' => 'integer',
        ];
    }

    // ─── Métodos de control de tokens IA ─────────────────────────────────────

    /**
     * Tokens disponibles este mes (null = ilimitado en Enterprise)
     */
    public function tokensAvailable(): ?int
    {
        if ($this->plan_type === 'enterprise') {
            return null; // ilimitado
        }

        if ($this->plan_type === 'pro') {
            return null; // cuota muy alta, tratar como ilimitado en UI
        }

        $this->resetTokensIfNewMonth();

        $available = $this->monthly_tokens_limit - $this->monthly_tokens_used;
        return max(0, $available);
    }

    /**
     * ¿Tiene tokens disponibles para hacer una llamada IA?
     */
    public function hasTokensAvailable(): bool
    {
        if (in_array($this->plan_type, ['enterprise', 'pro'])) {
            return true; // pro y enterprise tienen cuota muy alta o ilimitada
        }

        return $this->tokensAvailable() > 0;
    }

    /**
     * Descontar tokens usados del saldo mensual
     */
    public function consumeTokens(int $tokens): void
    {
        $this->resetTokensIfNewMonth();
        $this->increment('monthly_tokens_used', $tokens);
    }

    /**
     * Porcentaje de tokens usados este mes (0-100)
     */
    public function tokensUsagePercent(): float
    {
        if ($this->monthly_tokens_limit === 0) {
            return 0;
        }

        return min(100, round(($this->monthly_tokens_used / $this->monthly_tokens_limit) * 100, 1));
    }

    /**
     * Resetear contador si empezó un nuevo mes
     */
    private function resetTokensIfNewMonth(): void
    {
        $now = now()->startOfMonth()->toDateString();

        if ($this->tokens_reset_at === null || $this->tokens_reset_at->toDateString() !== $now) {
            $this->update([
                'monthly_tokens_used' => 0,
                'tokens_reset_at' => $now,
            ]);
            $this->refresh();
        }
    }

    /**
     * Límite de tokens según plan
     */
    public static function tokenLimitForPlan(string $plan): int
    {
        return match ($plan) {
            'basic'      => 50000,
            'starter'    => 200000,
            'pro'        => 1000000,
            'enterprise' => 0, // ilimitado
            default      => 50000,
        };
    }

    // Acceso activo al app (flag + periodo vigente)
    public function getHasActiveAccessAttribute(): bool
    {
        return $this->is_paid && (
            is_null($this->current_period_end) || $this->current_period_end->isFuture()
        );
    }

    /**
     * Relación con la configuración de Vera
     */
    public function veraConfig()
    {
        return $this->hasOne(\App\Models\VeraConfig::class);
    }

    /**
     * Organizaciones a las que pertenece el usuario
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot(['role', 'is_primary', 'invited_by', 'invited_at', 'joined_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Organizaciones activas del usuario
     */
    public function activeOrganizations()
    {
        return $this->organizations()->wherePivot('is_active', true);
    }

    /**
     * Organización principal del usuario
     */
    public function primaryOrganization()
    {
        return $this->organizations()->wherePivot('is_primary', true)->first();
    }

    /**
     * Organizaciones donde el usuario es propietario
     */
    public function ownedOrganizations()
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /**
     * Verificar si el usuario pertenece a una organización
     */
    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()->where('organization_id', $organization->id)->exists();
    }

    /**
     * Obtener el rol del usuario en una organización
     */
    public function getRoleInOrganization(Organization $organization): ?string
    {
        $pivot = $this->organizations()
            ->where('organization_id', $organization->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Verificar si el usuario tiene un rol específico en una organización
     */
    public function hasRoleInOrganization(Organization $organization, string $role): bool
    {
        return $this->getRoleInOrganization($organization) === $role;
    }

    /**
     * Verificar si el usuario es propietario de una organización
     */
    public function isOwnerOf(Organization $organization): bool
    {
        return $organization->owner_id === $this->id;
    }

    /**
     * Establecer organización principal
     */
    public function setPrimaryOrganization(Organization $organization): void
    {
        // Remover organización principal anterior
        $this->organizations()->updateExistingPivot(
            $this->organizations()->get()->pluck('id'),
            ['is_primary' => false]
        );

        // Establecer nueva organización principal
        $this->organizations()->updateExistingPivot($organization->id, [
            'is_primary' => true,
        ]);
    }

    /**
     * Verificar si el usuario tiene acceso a funcionalidades de organizaciones
     * Solo el plan Enterprise tiene acceso a organizaciones
     */
    public function hasOrganizationAccess(): bool
    {
        return $this->plan_type === 'enterprise';
    }

    /**
     * Alias de hasOrganizationAccess()
     */
    public function canAccessOrganizations(): bool
    {
        return $this->hasOrganizationAccess();
    }

    /**
     * Verificar si el plan es Enterprise
     */
    public function isEnterprise(): bool
    {
        return $this->plan_type === 'enterprise';
    }
}
