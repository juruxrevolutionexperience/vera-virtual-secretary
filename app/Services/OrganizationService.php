<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\OrganizationInvitation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class OrganizationService
{
    /**
     * Crear una nueva organización
     */
    public function create(User $owner, array $data): Organization
    {
        DB::beginTransaction();

        try {
            // Crear la organización
            $organization = Organization::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'settings' => $data['settings'] ?? null,
            ]);

            // Añadir el owner a la organización con rol organization_owner
            $organization->addUser($owner, 'organization_owner');

            // Asignar rol de Spatie al owner
            if (!$owner->hasRole('organization_owner')) {
                $owner->assignRole('organization_owner');
            }

            // Establecer como organización principal si es la primera
            if ($owner->organizations()->count() === 1) {
                $owner->setPrimaryOrganization($organization);
            }

            DB::commit();

            return $organization;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar una organización
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update(array_filter([
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? null,
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'settings' => $data['settings'] ?? null,
        ], fn($value) => $value !== null));

        return $organization->fresh();
    }

    /**
     * Eliminar una organización (soft delete)
     */
    public function delete(Organization $organization): bool
    {
        DB::beginTransaction();

        try {
            // Desactivar todos los miembros
            $organization->users()->updateExistingPivot(
                $organization->users()->pluck('user_id'),
                ['is_active' => false, 'left_at' => now()]
            );

            // Desactivar organización
            $organization->update(['is_active' => false]);

            // Soft delete
            $organization->delete();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Invitar un usuario a la organización
     */
    public function inviteUser(Organization $organization, User $inviter, string $email, string $role = 'employee', ?string $message = null): OrganizationInvitation
    {
        // Verificar que el email no esté ya en la organización
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $organization->hasUser($existingUser)) {
            throw new Exception('User is already a member of this organization.');
        }

        // Verificar si ya existe una invitación pendiente
        $existingInvitation = OrganizationInvitation::forOrganization($organization->id)
            ->forEmail($email)
            ->pending()
            ->first();

        if ($existingInvitation) {
            // Actualizar la invitación existente
            $existingInvitation->update([
                'role' => $role,
                'message' => $message,
                'expires_at' => now()->addDays(7),
            ]);

            return $existingInvitation;
        }

        // Crear nueva invitación
        return OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'invited_by' => $inviter->id,
            'role' => $role,
            'message' => $message,
        ]);
    }

    /**
     * Aceptar una invitación
     */
    public function acceptInvitation(OrganizationInvitation $invitation, User $user): Organization
    {
        if (!$invitation->isValid()) {
            throw new Exception('This invitation is no longer valid.');
        }

        if ($invitation->email !== $user->email) {
            throw new Exception('This invitation is for a different email address.');
        }

        DB::beginTransaction();

        try {
            $organization = $invitation->organization;

            // Añadir usuario a la organización
            $organization->addUser($user, $invitation->role, $invitation->inviter);

            // Asignar rol de Spatie
            if (!$user->hasRole($invitation->role)) {
                $user->assignRole($invitation->role);
            }

            // Marcar invitación como aceptada
            $invitation->accept();

            DB::commit();

            return $organization;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Rechazar una invitación
     */
    public function rejectInvitation(OrganizationInvitation $invitation): void
    {
        $invitation->reject();
    }

    /**
     * Remover un usuario de la organización
     */
    public function removeUser(Organization $organization, User $user): void
    {
        // No se puede remover al owner
        if ($organization->isOwner($user)) {
            throw new Exception('The organization owner cannot be removed.');
        }

        $organization->removeUser($user);
    }

    /**
     * Cambiar el rol de un usuario en la organización
     */
    public function changeUserRole(Organization $organization, User $user, string $newRole): void
    {
        // No se puede cambiar el rol del owner
        if ($organization->isOwner($user)) {
            throw new Exception('Cannot change the role of the organization owner.');
        }

        DB::beginTransaction();

        try {
            // Cambiar rol en la tabla pivot
            $organization->changeUserRole($user, $newRole);

            // Actualizar roles de Spatie
            $user->syncRoles([$newRole]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transferir propiedad de la organización
     */
    public function transferOwnership(Organization $organization, User $newOwner): void
    {
        if (!$organization->hasUser($newOwner)) {
            throw new Exception('The new owner must be a member of the organization.');
        }

        DB::beginTransaction();

        try {
            $oldOwner = $organization->owner;

            // Actualizar el propietario en la tabla organizations
            $organization->update(['owner_id' => $newOwner->id]);

            // Cambiar roles en la tabla pivot
            $organization->changeUserRole($oldOwner, 'admin');
            $organization->changeUserRole($newOwner, 'organization_owner');

            // Actualizar roles de Spatie
            $oldOwner->syncRoles(['admin']);
            $newOwner->syncRoles(['organization_owner']);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de la organización
     */
    public function getStatistics(Organization $organization): array
    {
        return [
            'members' => [
                'total' => $organization->users()->count(),
                'active' => $organization->activeUsers()->count(),
                'by_role' => $organization->users()
                    ->selectRaw('organization_user.role, COUNT(*) as count')
                    ->groupBy('organization_user.role')
                    ->pluck('count', 'organization_user.role')
                    ->toArray(),
            ],
            'emails' => [
                'total' => $organization->emails()->count(),
                'this_month' => $organization->emails()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'folders' => [
                'total' => $organization->emailFolders()->count(),
            ],
            'contacts' => [
                'total' => $organization->contacts()->count(),
            ],
            'alerts' => [
                'active' => $organization->emailAlerts()
                    ->where('is_resolved', false)
                    ->count(),
            ],
        ];
    }

    /**
     * Cancelar invitaciones pendientes
     */
    public function cancelInvitation(OrganizationInvitation $invitation): void
    {
        if ($invitation->status !== 'pending') {
            throw new Exception('Only pending invitations can be cancelled.');
        }

        $invitation->update(['status' => 'expired']);
    }

    /**
     * Marcar invitaciones expiradas
     */
    public function markExpiredInvitations(Organization $organization): int
    {
        return OrganizationInvitation::forOrganization($organization->id)
            ->expired()
            ->update(['status' => 'expired']);
    }
}
