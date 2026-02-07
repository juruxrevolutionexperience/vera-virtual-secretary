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
        ];
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
