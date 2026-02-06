<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'email',
        'phone',
        'address',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Boot del modelo para generar slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            if (empty($organization->slug)) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    /**
     * Propietario de la organización
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Usuarios que pertenecen a esta organización
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot(['role', 'is_primary', 'invited_by', 'invited_at', 'joined_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Usuarios activos de la organización
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Usuarios por rol específico
     */
    public function usersByRole(string $role): BelongsToMany
    {
        return $this->users()->wherePivot('role', $role);
    }

    /**
     * Emails de esta organización
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Carpetas de email de esta organización
     */
    public function emailFolders(): HasMany
    {
        return $this->hasMany(EmailFolder::class);
    }

    /**
     * Contactos de esta organización
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Alertas de email de esta organización
     */
    public function emailAlerts(): HasMany
    {
        return $this->hasMany(EmailAlert::class);
    }

    /**
     * Reglas de escalado de esta organización
     */
    public function escalationRules(): HasMany
    {
        return $this->hasMany(EscalationRule::class);
    }

    /**
     * Recordatorios de seguimiento de esta organización
     */
    public function followUpReminders(): HasMany
    {
        return $this->hasMany(FollowUpReminder::class);
    }

    /**
     * Plantillas de email de esta organización
     */
    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Emails programados de esta organización
     */
    public function scheduledEmails(): HasMany
    {
        return $this->hasMany(ScheduledEmail::class);
    }

    /**
     * Auto-responders de esta organización
     */
    public function autoResponders(): HasMany
    {
        return $this->hasMany(AutoResponder::class);
    }

    /**
     * Categorías de email de esta organización
     */
    public function emailCategories(): HasMany
    {
        return $this->hasMany(EmailCategory::class);
    }

    /**
     * Reglas de email de esta organización
     */
    public function emailRules(): HasMany
    {
        return $this->hasMany(EmailRule::class);
    }

    /**
     * Scope: Solo organizaciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Buscar por slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Verificar si un usuario pertenece a esta organización
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Verificar si un usuario tiene un rol específico en esta organización
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * Obtener el rol de un usuario en esta organización
     */
    public function getUserRole(User $user): ?string
    {
        $pivot = $this->users()
            ->where('user_id', $user->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Añadir un usuario a la organización
     */
    public function addUser(User $user, string $role = 'employee', ?User $invitedBy = null): void
    {
        $this->users()->attach($user->id, [
            'role' => $role,
            'is_primary' => false,
            'invited_by' => $invitedBy?->id,
            'invited_at' => now(),
            'joined_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Remover un usuario de la organización
     */
    public function removeUser(User $user): void
    {
        // No se puede remover al owner
        if ($this->isOwner($user)) {
            throw new \Exception('No se puede remover al propietario de la organización');
        }

        $this->users()->updateExistingPivot($user->id, [
            'is_active' => false,
            'left_at' => now(),
        ]);
    }

    /**
     * Cambiar el rol de un usuario
     */
    public function changeUserRole(User $user, string $newRole): void
    {
        // No se puede cambiar el rol del owner
        if ($this->isOwner($user)) {
            throw new \Exception('No se puede cambiar el rol del propietario de la organización');
        }

        $this->users()->updateExistingPivot($user->id, [
            'role' => $newRole,
        ]);
    }

    /**
     * Contar miembros activos
     */
    public function getActiveMembersCount(): int
    {
        return $this->activeUsers()->count();
    }

    /**
     * Verificar si el usuario es propietario
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }
}
