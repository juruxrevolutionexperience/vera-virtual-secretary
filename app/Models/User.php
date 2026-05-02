<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail
{
    use HasFactory, Notifiable, Billable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'timezone',
        'is_dev',
        // Billing Stripe
        'is_paid',
        'stripe_customer_id',
        'stripe_subscription_id',
        'current_period_end',
        // Plan
        'plan_type',
        'monthly_meeting_limit',
        'meetings_this_month',
        'billing_cycle_start',
        'plan_metadata',
        'plan_upgraded_at',
        // Tokens IA
        'monthly_tokens_used',
        'monthly_tokens_limit',
        'tokens_reset_at',
        // 2FA
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
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'is_paid'                => 'boolean',
            'is_dev'                 => 'boolean',
            'current_period_end'     => 'datetime',
            'two_factor_enabled'     => 'boolean',
            'two_factor_enabled_at'  => 'datetime',
            'two_factor_confirmed_at'=> 'datetime',
            'tokens_reset_at'        => 'date',
            'monthly_tokens_used'    => 'integer',
            'monthly_tokens_limit'   => 'integer',
            'monthly_meeting_limit'  => 'integer',
            'meetings_this_month'    => 'integer',
        ];
    }

    // ─── Dev Mode ─────────────────────────────────────────────────────────────

    public function isDev(): bool
    {
        return (bool) $this->is_dev;
    }

    // ─── Tokens IA ────────────────────────────────────────────────────────────

    /**
     * ¿Tiene capacidad disponible para una llamada IA este mes?
     */
    public function hasTokensAvailable(): bool
    {
        if ($this->monthly_tokens_limit === 0) {
            return true; // 0 = ilimitado (Enterprise)
        }

        return $this->monthly_tokens_used < $this->monthly_tokens_limit;
    }

    /**
     * Tokens restantes este mes. null = ilimitado.
     */
    public function tokensAvailable(): ?int
    {
        if ($this->monthly_tokens_limit === 0) {
            return null;
        }

        return max(0, $this->monthly_tokens_limit - $this->monthly_tokens_used);
    }

    /**
     * Descontar tokens usados del saldo mensual.
     */
    public function consumeTokens(int $tokens): void
    {
        $this->resetTokensIfNewMonth();
        $this->increment('monthly_tokens_used', $tokens);
    }

    /**
     * Porcentaje de tokens usados este mes (0-100).
     */
    public function tokensUsagePercent(): float
    {
        if (!$this->monthly_tokens_limit) {
            return 0;
        }

        return min(100, round(($this->monthly_tokens_used / $this->monthly_tokens_limit) * 100, 1));
    }

    private function resetTokensIfNewMonth(): void
    {
        $now = now()->startOfMonth()->toDateString();

        if ($this->tokens_reset_at === null || $this->tokens_reset_at->toDateString() !== $now) {
            $this->update([
                'monthly_tokens_used' => 0,
                'tokens_reset_at'     => $now,
            ]);
            $this->refresh();
        }
    }

    // ─── Límites de reuniones por plan ───────────────────────────────────────

    /**
     * Reuniones/mes que permite el plan actual. null = ilimitado.
     * Lee de BD (tabla plans), fallback a hardcoded según Plan Maestro:
     * Básica 30/mes | Pro: ilimitado | Equipo: ilimitado
     */
    public function planMeetingLimit(): ?int
    {
        $p = $this->plan;
        if ($p && isset($p->meeting_limit)) {
            return $p->meeting_limit; // null = ilimitado
        }

        return match ($this->plan_type ?? 'basic') {
            'basic'      => 30,
            'pro'        => null,
            'team'       => null,
            'enterprise' => null,
            default      => 30,
        };
    }

    /**
     * Reuniones confirmadas en el ciclo de facturación actual.
     */
    public function monthlyMeetingsUsed(): int
    {
        return (int) $this->meetings_this_month;
    }

    /**
     * ¿Puede Vera confirmar más reuniones este mes?
     */
    public function hasMeetingsAvailable(): bool
    {
        $limit = $this->planMeetingLimit();
        if ($limit === null) {
            return true;
        }

        return $this->monthlyMeetingsUsed() < $limit;
    }

    /**
     * Reuniones restantes este mes. null = ilimitado.
     */
    public function meetingsRemaining(): ?int
    {
        $limit = $this->planMeetingLimit();
        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->monthlyMeetingsUsed());
    }

    /**
     * Nombre legible del plan actual.
     */
    public function planName(): string
    {
        return $this->plan?->name ?? ucfirst($this->plan_type ?? 'Basic');
    }

    // ─── Acceso activo ───────────────────────────────────────────────────────

    public function getHasActiveAccessAttribute(): bool
    {
        return $this->is_paid && (
            is_null($this->current_period_end) || $this->current_period_end->isFuture()
        );
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_type', 'slug');
    }

    public function veraConfig()
    {
        return $this->hasOne(VeraConfig::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot(['role', 'is_primary', 'invited_by', 'invited_at', 'joined_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    public function activeOrganizations()
    {
        return $this->organizations()->wherePivot('is_active', true);
    }

    public function primaryOrganization()
    {
        return $this->organizations()->wherePivot('is_primary', true)->first();
    }

    public function ownedOrganizations()
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()->where('organization_id', $organization->id)->exists();
    }

    public function getRoleInOrganization(Organization $organization): ?string
    {
        return $this->organizations()
            ->where('organization_id', $organization->id)
            ->first()?->pivot?->role;
    }

    public function hasRoleInOrganization(Organization $organization, string $role): bool
    {
        return $this->getRoleInOrganization($organization) === $role;
    }

    public function isOwnerOf(Organization $organization): bool
    {
        return $organization->owner_id === $this->id;
    }

    public function setPrimaryOrganization(Organization $organization): void
    {
        $this->organizations()->updateExistingPivot(
            $this->organizations()->get()->pluck('id'),
            ['is_primary' => false]
        );
        $this->organizations()->updateExistingPivot($organization->id, ['is_primary' => true]);
    }

    public function hasOrganizationAccess(): bool
    {
        return $this->plan_type === 'enterprise';
    }

    public function canAccessOrganizations(): bool
    {
        return $this->hasOrganizationAccess();
    }

    public function isEnterprise(): bool
    {
        return $this->plan_type === 'enterprise';
    }

    // ─── Límite de tokens según plan (para /vera/tokens) ─────────────────────

    public static function tokenLimitForPlan(string $plan): int
    {
        $p = Plan::findBySlug($plan);
        if ($p) {
            return $p->token_limit ?? 0;
        }

        return match ($plan) {
            'basic'      => 50000,
            'pro'        => 1000000,
            'team'       => 5000000,
            'enterprise' => 0,
            default      => 50000,
        };
    }
}
