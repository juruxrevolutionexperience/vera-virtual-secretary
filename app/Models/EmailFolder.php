<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'parent_folder_id',
        'name',
        'icon',
        'color',
        'description',
        'order',
        'is_system_folder',
        'is_shared',
        'settings',
    ];

    protected $casts = [
        'is_system_folder' => 'boolean',
        'is_shared' => 'boolean',
        'settings' => 'array',
        'order' => 'integer',
    ];

    /**
     * Usuario propietario de la carpeta
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Carpeta padre (para jerarquía)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(EmailFolder::class, 'parent_folder_id');
    }

    /**
     * Subcarpetas (hijas)
     */
    public function children(): HasMany
    {
        return $this->hasMany(EmailFolder::class, 'parent_folder_id')->orderBy('order');
    }

    /**
     * Todas las subcarpetas recursivamente
     */
    public function descendantsRecursive(): HasMany
    {
        return $this->children()->with('descendantsRecursive');
    }

    /**
     * Emails en esta carpeta (relación many-to-many)
     */
    public function emails(): BelongsToMany
    {
        return $this->belongsToMany(Email::class, 'email_folder_assignments')
            ->withPivot('assigned_by', 'assigned_at', 'notes')
            ->withTimestamps();
    }

    /**
     * Scope: Solo carpetas raíz (sin padre)
     */
    public function scopeRootFolders($query)
    {
        return $query->whereNull('parent_folder_id');
    }

    /**
     * Scope: Solo carpetas del usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Solo carpetas no del sistema
     */
    public function scopeCustomFolders($query)
    {
        return $query->where('is_system_folder', false);
    }

    /**
     * Obtener la ruta completa de la carpeta (ej: "Trabajo/Proyectos/Proyecto A")
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    /**
     * Obtener nivel de profundidad en el árbol
     */
    public function getDepthLevel(): int
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    /**
     * Verificar si esta carpeta es ancestro de otra
     */
    public function isAncestorOf(EmailFolder $folder): bool
    {
        $parent = $folder->parent;

        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Verificar si esta carpeta es descendiente de otra
     */
    public function isDescendantOf(EmailFolder $folder): bool
    {
        return $folder->isAncestorOf($this);
    }

    /**
     * Contar emails en esta carpeta
     */
    public function getEmailsCount(): int
    {
        return $this->emails()->count();
    }

    /**
     * Contar emails en esta carpeta y todas las subcarpetas
     */
    public function getEmailsCountRecursive(): int
    {
        $count = $this->getEmailsCount();

        foreach ($this->children as $child) {
            $count += $child->getEmailsCountRecursive();
        }

        return $count;
    }

    // ==================== SCOPES DE MULTI-TENANCY ====================

    /**
     * Relación con organización
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope: Filtrar por organización
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: Carpetas compartidas con un usuario
     */
    public function scopeSharedWith($query, $userId)
    {
        return $query->whereHas('shares', function ($q) use ($userId) {
            $q->where('shared_with', $userId)
              ->where(function ($subQ) {
                  $subQ->whereNull('expires_at')
                       ->orWhere('expires_at', '>', now());
              });
        });
    }

    /**
     * Relación con shares (carpetas compartidas)
     */
    public function shares(): HasMany
    {
        return $this->hasMany(EmailFolderShare::class);
    }

    /**
     * Verificar si está compartida con un usuario
     */
    public function isSharedWith(User $user): bool
    {
        return $this->shares()
            ->where('shared_with', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
