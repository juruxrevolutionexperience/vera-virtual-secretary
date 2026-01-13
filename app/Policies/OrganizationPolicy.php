<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario autenticado puede ver sus organizaciones
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        // Puede ver la organización si pertenece a ella
        return $user->belongsToOrganization($organization);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Cualquier usuario puede crear su propia organización
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        // Solo el owner o usuarios con permiso manage_settings
        if ($organization->isOwner($user)) {
            return true;
        }

        return $user->hasPermissionTo('organization.manage_settings')
            && $user->belongsToOrganization($organization);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        // Solo el propietario puede eliminar la organización
        return $organization->isOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $organization->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return $organization->isOwner($user);
    }

    /**
     * Determine whether the user can view settings.
     */
    public function viewSettings(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermissionTo('organization.view_settings');
    }

    /**
     * Determine whether the user can manage settings.
     */
    public function manageSettings(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermissionTo('organization.manage_settings');
    }

    /**
     * Determine whether the user can view members.
     */
    public function viewMembers(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermissionTo('organization.view_members');
    }

    /**
     * Determine whether the user can invite members.
     */
    public function inviteMembers(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermissionTo('organization.invite_members');
    }

    /**
     * Determine whether the user can remove members.
     */
    public function removeMembers(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        // Solo owner, admin o quien tenga el permiso específico
        return $organization->isOwner($user)
            || $user->hasPermissionTo('organization.remove_members');
    }

    /**
     * Determine whether the user can change member roles.
     */
    public function changeMemberRoles(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermissionTo('organization.change_member_roles');
    }

    /**
     * Determine whether the user can view analytics.
     */
    public function viewAnalytics(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermissionTo('organization.view_analytics');
    }

    /**
     * Determine whether the user can manage billing.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        if (!$user->belongsToOrganization($organization)) {
            return false;
        }

        // Solo el owner o quien tenga permiso explícito
        return $organization->isOwner($user)
            || $user->hasPermissionTo('organization.manage_billing');
    }

    /**
     * Determine whether the user can leave the organization.
     */
    public function leave(User $user, Organization $organization): bool
    {
        // El owner no puede abandonar su propia organización
        if ($organization->isOwner($user)) {
            return false;
        }

        return $user->belongsToOrganization($organization);
    }
}
