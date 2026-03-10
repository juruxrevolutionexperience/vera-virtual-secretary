<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    // NOTA: el gate real es hasEmailsAvailable() (sistema de emails).
    // Estos métodos delegan a él para mantener una sola fuente de verdad.
    // monthly_tokens_used / monthly_tokens_limit son datos informativos
    // que alimentan la calculadora de pricing en /vera/tokens.

    /**
     * ¿Tiene capacidad disponible para una llamada IA este mes?
     * Delega al sistema de emails (fuente única de verdad).
     */
    public function hasTokensAvailable(): bool
    {
        return $this->hasEmailsAvailable();
    }

    /**
     * Emails disponibles este mes. null = ilimitado (Enterprise).
     * Alias semántico para compatibilidad con código legacy.
     */
    public function tokensAvailable(): ?int
    {
        return $this->emailsRemaining();
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
     * Relación con el plan (foreign key plan_type → slug)
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_type', 'slug');
    }

    /**
     * Límite de tokens según plan (lee BD, fallback hardcoded)
     */
    public static function tokenLimitForPlan(string $plan): int
    {
        $p = \App\Models\Plan::findBySlug($plan);
        if ($p) {
            return $p->token_limit ?? 0; // null = ilimitado, tratamos como 0
        }

        return match ($plan) {
            'basic'      => 50000,
            'starter'    => 200000,
            'pro'        => 1000000,
            'enterprise' => 0,
            default      => 50000,
        };
    }

    // ── Límites de emails por plan ────────────────────────────────────────────

    /**
     * Emails/mes que permite el plan actual. null = ilimitado.
     * Lee de BD, fallback a hardcoded si la tabla está vacía.
     */
    public function planEmailLimit(): ?int
    {
        $p = $this->plan;
        if ($p) {
            return $p->email_limit; // null = ilimitado
        }

        return match ($this->plan_type ?? 'basic') {
            'free_trial' => 30,
            'basic'      => 100,
            'starter'    => 500,
            'pro'        => 2000,
            'enterprise' => null,
            default      => 100,
        };
    }

    /**
     * Emails procesados por Vera en el ciclo de facturación actual.
     * Usa billing_cycle_start si existe, si no, inicio del mes natural.
     */
    public function monthlyEmailsUsed(): int
    {
        $cycleDay = $this->billing_cycle_start ?? 1;
        $start    = \Carbon\Carbon::now()->startOfMonth()->setDay((int) $cycleDay);

        if ($start->isFuture()) {
            $start->subMonth();
        }

        return \App\Models\VeraTokenUsage::byUser($this->id)
            ->where('action_type', 'email_analysis')
            ->where('created_at', '>=', $start)
            ->count();
    }

    /**
     * ¿Puede Vera procesar más emails este mes?
     */
    public function hasEmailsAvailable(): bool
    {
        $limit = $this->planEmailLimit();
        if ($limit === null) {
            return true;
        }
        return $this->monthlyEmailsUsed() < $limit;
    }

    /**
     * Emails restantes este mes. null = ilimitado.
     */
    public function emailsRemaining(): ?int
    {
        $limit = $this->planEmailLimit();
        if ($limit === null) {
            return null;
        }
        return max(0, $limit - $this->monthlyEmailsUsed());
    }

    /**
     * Nombre legible del plan actual.
     * Lee de BD, fallback a ucfirst del slug.
     */
    public function planName(): string
    {
        return $this->plan?->name ?? ucfirst($this->plan_type ?? 'Basic');
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
