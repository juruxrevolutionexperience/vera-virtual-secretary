<?php

namespace App\Policies;

use App\Models\Email;
use App\Models\User;
use App\Models\EmailShare;
use Illuminate\Auth\Access\Response;

class EmailPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Puede ver lista de emails si tiene permiso view_own, view_shared o view_all
        return $user->hasAnyPermission(['emails.view_own', 'emails.view_shared', 'emails.view_all']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Email $email): bool
    {
        // 1. Si tiene permiso view_all en la organización del email
        if ($user->hasPermissionTo('emails.view_all') && $this->sameOrganization($user, $email)) {
            return true;
        }

        // 2. Si es el propietario del email y tiene permiso view_own
        if ($email->user_id === $user->id && $user->hasPermissionTo('emails.view_own')) {
            return true;
        }

        // 3. Si el email está compartido con el usuario y tiene permiso view_shared
        if ($user->hasPermissionTo('emails.view_shared') && $this->isSharedWith($user, $email)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('emails.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Email $email): bool
    {
        // 1. Si tiene permiso edit_all en la organización del email
        if ($user->hasPermissionTo('emails.edit_all') && $this->sameOrganization($user, $email)) {
            return true;
        }

        // 2. Si es el propietario del email y tiene permiso edit_own
        if ($email->user_id === $user->id && $user->hasPermissionTo('emails.edit_own')) {
            return true;
        }

        // 3. Si el email está compartido con permiso de edición
        if ($this->hasSharedPermission($user, $email, 'edit')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Email $email): bool
    {
        // 1. Si tiene permiso delete_all en la organización del email
        if ($user->hasPermissionTo('emails.delete_all') && $this->sameOrganization($user, $email)) {
            return true;
        }

        // 2. Si es el propietario del email y tiene permiso delete_own
        if ($email->user_id === $user->id && $user->hasPermissionTo('emails.delete_own')) {
            return true;
        }

        // 3. Si el email está compartido con permiso de eliminación
        if ($this->hasSharedPermission($user, $email, 'delete')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Email $email): bool
    {
        // Solo pueden restaurar quienes pueden eliminar
        return $this->delete($user, $email);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Email $email): bool
    {
        // Solo organization owners y admins pueden forzar eliminación
        if ($user->hasPermissionTo('emails.delete_all') && $this->sameOrganization($user, $email)) {
            return $user->hasAnyRole(['organization_owner', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the user can manage workflow status.
     */
    public function manageWorkflow(User $user, Email $email): bool
    {
        if (!$user->hasPermissionTo('emails.manage_workflow')) {
            return false;
        }

        // Puede gestionar workflow si puede ver el email
        return $this->view($user, $email);
    }

    /**
     * Determine whether the user can share the email.
     */
    public function share(User $user, Email $email): bool
    {
        // Solo puede compartir si es propietario o tiene permiso edit_all
        if ($email->user_id === $user->id) {
            return true;
        }

        if ($user->hasPermissionTo('emails.edit_all') && $this->sameOrganization($user, $email)) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario y el email están en la misma organización
     */
    protected function sameOrganization(User $user, Email $email): bool
    {
        if (!$email->organization_id) {
            return false;
        }

        return $user->organizations()
            ->where('organization_id', $email->organization_id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Verificar si el email está compartido con el usuario
     */
    protected function isSharedWith(User $user, Email $email): bool
    {
        return EmailShare::where('email_id', $email->id)
            ->where('shared_with', $user->id)
            ->notExpired()
            ->exists();
    }

    /**
     * Verificar si el usuario tiene un permiso específico sobre el email compartido
     */
    protected function hasSharedPermission(User $user, Email $email, string $requiredPermission): bool
    {
        $share = EmailShare::where('email_id', $email->id)
            ->where('shared_with', $user->id)
            ->notExpired()
            ->first();

        if (!$share) {
            return false;
        }

        return $share->hasPermission($requiredPermission);
    }
}
