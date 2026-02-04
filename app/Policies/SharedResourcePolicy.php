<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy para gestionar recursos compartidos de organización
 * (Carpetas, Templates y Contactos compartidos)
 */
class SharedResourcePolicy
{
    /**
     * Determine whether the user can view shared resources.
     */
    public function viewShared(User $user, Model $resource): bool
    {
        // Si el recurso no tiene organización, no es compartido
        if (!$resource->organization_id) {
            return false;
        }

        $organization = Organization::find($resource->organization_id);

        if (!$organization) {
            return false;
        }

        // Debe pertenecer a la organización
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        // Verificar permiso según tipo de recurso
        $resourceType = $this->getResourceType($resource);

        return $user->hasPermissionTo("shared.{$resourceType}.view");
    }

    /**
     * Determine whether the user can create shared resources.
     */
    public function createShared(User $user, Organization $organization, string $resourceType): bool
    {
        // Debe pertenecer a la organización
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        // Solo owner o admin pueden crear recursos compartidos
        return $user->hasPermissionTo("shared.{$resourceType}.create");
    }

    /**
     * Determine whether the user can update shared resources.
     */
    public function updateShared(User $user, Model $resource): bool
    {
        // Si el recurso no tiene organización, no es compartido
        if (!$resource->organization_id) {
            return false;
        }

        $organization = Organization::find($resource->organization_id);

        if (!$organization) {
            return false;
        }

        // Debe pertenecer a la organización
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        // Verificar permiso según tipo de recurso
        $resourceType = $this->getResourceType($resource);

        return $user->hasPermissionTo("shared.{$resourceType}.update");
    }

    /**
     * Determine whether the user can delete shared resources.
     */
    public function deleteShared(User $user, Model $resource): bool
    {
        // Si el recurso no tiene organización, no es compartido
        if (!$resource->organization_id) {
            return false;
        }

        $organization = Organization::find($resource->organization_id);

        if (!$organization) {
            return false;
        }

        // Debe pertenecer a la organización
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        // Verificar permiso según tipo de recurso
        $resourceType = $this->getResourceType($resource);

        return $user->hasPermissionTo("shared.{$resourceType}.delete");
    }

    /**
     * Determine whether the user can manage their own resources.
     * (Recursos propios siempre pueden ser gestionados por el usuario)
     */
    public function manageOwn(User $user, Model $resource): bool
    {
        // Si tiene organization_id, es compartido (no propio)
        if ($resource->organization_id) {
            return false;
        }

        // Verificar que el recurso pertenece al usuario
        return $resource->user_id === $user->id;
    }

    /**
     * Obtener el tipo de recurso desde el modelo
     */
    private function getResourceType(Model $resource): string
    {
        $className = class_basename($resource);

        return match ($className) {
            'EmailFolder' => 'folders',
            'EmailTemplate' => 'templates',
            'Contact' => 'contacts',
            default => throw new \InvalidArgumentException("Tipo de recurso no soportado: {$className}"),
        };
    }

    /**
     * Helper: Verificar si el usuario puede usar un recurso compartido
     * (Para members que solo tienen lectura)
     */
    public function useShared(User $user, Model $resource): bool
    {
        // Los members pueden usar (leer) recursos compartidos
        return $this->viewShared($user, $resource);
    }
}
