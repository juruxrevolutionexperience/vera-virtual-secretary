<?php

namespace App\Policies;

use App\Models\EmailFolder;
use App\Models\User;
use App\Models\EmailFolderShare;

class EmailFolderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('folders.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailFolder $folder): bool
    {
        // 1. Si es el propietario de la carpeta
        if ($folder->user_id === $user->id && $user->hasPermissionTo('folders.view')) {
            return true;
        }

        // 2. Si la carpeta está compartida con el usuario
        if ($this->isSharedWith($user, $folder)) {
            return true;
        }

        // 3. Si es una carpeta de la organización y el usuario pertenece a ella
        if ($this->sameOrganization($user, $folder)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('folders.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailFolder $folder): bool
    {
        // No se pueden editar carpetas del sistema
        if ($folder->is_system_folder) {
            return false;
        }

        // 1. Si tiene permiso edit_all en la organización
        if ($user->hasPermissionTo('folders.edit_all') && $this->sameOrganization($user, $folder)) {
            return true;
        }

        // 2. Si es el propietario y tiene permiso edit_own
        if ($folder->user_id === $user->id && $user->hasPermissionTo('folders.edit_own')) {
            return true;
        }

        // 3. Si está compartida con permiso de edición
        if ($this->hasSharedPermission($user, $folder, 'edit')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailFolder $folder): bool
    {
        // No se pueden eliminar carpetas del sistema
        if ($folder->is_system_folder) {
            return false;
        }

        // 1. Si tiene permiso delete_all en la organización
        if ($user->hasPermissionTo('folders.delete_all') && $this->sameOrganization($user, $folder)) {
            return true;
        }

        // 2. Si es el propietario y tiene permiso delete_own
        if ($folder->user_id === $user->id && $user->hasPermissionTo('folders.delete_own')) {
            return true;
        }

        // 3. Si está compartida con permiso de eliminación
        if ($this->hasSharedPermission($user, $folder, 'delete')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmailFolder $folder): bool
    {
        return $this->delete($user, $folder);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmailFolder $folder): bool
    {
        // Solo organization owners y admins pueden forzar eliminación
        if ($user->hasPermissionTo('folders.delete_all') && $this->sameOrganization($user, $folder)) {
            return $user->hasAnyRole(['organization_owner', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the user can share the folder.
     */
    public function share(User $user, EmailFolder $folder): bool
    {
        if (!$user->hasPermissionTo('folders.share')) {
            return false;
        }

        // Solo puede compartir si es propietario o tiene permiso edit_all
        if ($folder->user_id === $user->id) {
            return true;
        }

        if ($user->hasPermissionTo('folders.edit_all') && $this->sameOrganization($user, $folder)) {
            return true;
        }

        // O si tiene la carpeta compartida con can_share = true
        $share = EmailFolderShare::where('email_folder_id', $folder->id)
            ->where('shared_with', $user->id)
            ->where('can_share', true)
            ->notExpired()
            ->first();

        return $share !== null;
    }

    /**
     * Verificar si el usuario y la carpeta están en la misma organización
     */
    protected function sameOrganization(User $user, EmailFolder $folder): bool
    {
        if (!$folder->organization_id) {
            return false;
        }

        return $user->organizations()
            ->where('organization_id', $folder->organization_id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Verificar si la carpeta está compartida con el usuario
     */
    protected function isSharedWith(User $user, EmailFolder $folder): bool
    {
        return EmailFolderShare::where('email_folder_id', $folder->id)
            ->where('shared_with', $user->id)
            ->notExpired()
            ->exists();
    }

    /**
     * Verificar si el usuario tiene un permiso específico sobre la carpeta compartida
     */
    protected function hasSharedPermission(User $user, EmailFolder $folder, string $requiredPermission): bool
    {
        $share = EmailFolderShare::where('email_folder_id', $folder->id)
            ->where('shared_with', $user->id)
            ->notExpired()
            ->first();

        if (!$share) {
            return false;
        }

        return $share->hasPermission($requiredPermission);
    }
}
