<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'subject',
        'body',
        'description',
        'type',
        'placeholders',
        'is_active',
        'is_default',
        'category',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * Usuario propietario del template
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Emails programados que usan este template
     */
    public function scheduledEmails(): HasMany
    {
        return $this->hasMany(ScheduledEmail::class);
    }

    /**
     * Auto-responders que usan este template
     */
    public function autoResponders(): HasMany
    {
        return $this->hasMany(AutoResponder::class);
    }

    /**
     * Scope: Solo templates activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Por tipo de template
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Templates del usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Solo templates propios del usuario (no compartidos)
     */
    public function scopeOwnedByUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->whereNull('organization_id');
    }

    /**
     * Scope: Solo templates compartidos de la organización
     */
    public function scopeSharedInOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: Templates accesibles por el usuario (propios + compartidos de su organización)
     */
    public function scopeAccessibleByUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Templates propios
            $q->where('user_id', $user->id);

            // Templates compartidos de organizaciones del usuario
            $userOrganizations = $user->activeOrganizations()->pluck('organization_id');
            if ($userOrganizations->isNotEmpty()) {
                $q->orWhereIn('organization_id', $userOrganizations);
            }
        });
    }

    /**
     * Verificar si es un template compartido
     */
    public function isShared(): bool
    {
        return !is_null($this->organization_id);
    }

    /**
     * Verificar si el usuario puede editar este template
     */
    public function canBeEditedBy(User $user): bool
    {
        // Templates propios siempre se pueden editar
        if ($this->user_id === $user->id && !$this->isShared()) {
            return true;
        }

        // Templates compartidos solo por owner/admin
        if ($this->isShared()) {
            return $user->hasPermissionTo('shared.templates.update');
        }

        return false;
    }

    /**
     * Scope: Template por defecto para un tipo
     */
    public function scopeDefaultFor($query, string $type)
    {
        return $query->where('type', $type)->where('is_default', true);
    }

    /**
     * Renderizar el template reemplazando placeholders
     *
     * @param array $data Datos para reemplazar placeholders
     * @return array ['subject' => string, 'body' => string]
     */
    public function render(array $data = []): array
    {
        $subject = $this->subject;
        $body = $this->body;

        // Reemplazar placeholders
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Obtener placeholders disponibles parseados del body
     */
    public function getAvailablePlaceholders(): array
    {
        if ($this->placeholders) {
            return $this->placeholders;
        }

        // Extraer placeholders del body usando regex
        preg_match_all('/\{\{([^}]+)\}\}/', $this->body, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Establecer como template por defecto para su tipo
     */
    public function setAsDefault(): void
    {
        // Remover el default de otros templates del mismo tipo
        static::where('user_id', $this->user_id)
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Establecer este como default
        $this->update(['is_default' => true]);
    }
}
