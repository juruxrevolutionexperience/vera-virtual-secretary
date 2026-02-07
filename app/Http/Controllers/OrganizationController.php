<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the user's organizations.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Organizaciones del usuario (activas)
        $organizations = $user->activeOrganizations()
            ->withCount('activeUsers')
            ->with('owner')
            ->get();

        // Organizaciones donde es owner
        $ownedOrganizations = $user->ownedOrganizations()
            ->withCount('activeUsers')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'organizations' => $organizations,
                'owned_organizations' => $ownedOrganizations,
            ]);
        }

        return view('organizations.index', compact('organizations', 'ownedOrganizations'));
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create()
    {
        $this->authorize('create', Organization::class);

        return view('organizations.create');
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Organization::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:organizations,slug', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::beginTransaction();

            // Crear organización
            $organization = Organization::create([
                'owner_id' => $request->user()->id,
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'website' => $validated['website'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_active' => true,
            ]);

            // Agregar el owner como miembro con rol 'owner'
            $organization->users()->attach($request->user()->id, [
                'role' => 'owner',
                'is_primary' => true,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Asignar rol de Spatie al usuario en la organización
            $request->user()->assignRole('owner');

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Organización creada exitosamente',
                    'organization' => $organization->load('owner'),
                ], 201);
            }

            return redirect()->route('organizations.show', $organization)
                ->with('success', '✅ Organización creada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '❌ Error al crear la organización',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', '❌ Error al crear la organización: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified organization.
     */
    public function show(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        // Cargar miembros activos
        $members = $organization->activeUsers()
            ->withPivot(['role', 'joined_at', 'is_primary'])
            ->get();

        // Cargar historial de miembros inactivos (solo para Owner/Admin)
        $formerMembers = collect();
        if ($request->user()->can('viewMembers', $organization)) {
            $formerMembers = $organization->users()
                ->wherePivot('is_active', false)
                ->get();
        }

        // Obtener rol del usuario actual
        $userRole = $organization->getUserRole($request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'organization' => $organization->load('owner'),
                'members' => $members,
                'former_members' => $formerMembers,
                'user_role' => $userRole,
                'stats' => [
                    'total_members' => $members->count(),
                    'total_emails' => $organization->emails()->count(),
                    'total_folders' => $organization->emailFolders()->count(),
                    'total_templates' => $organization->emailTemplates()->count(),
                    'total_contacts' => $organization->contacts()->count(),
                ],
            ]);
        }

        return view('organizations.show', compact('organization', 'members', 'formerMembers', 'userRole'));
    }

    /**
     * Show the form for editing the organization.
     */
    public function edit(Organization $organization)
    {
        $this->authorize('update', $organization);

        return view('organizations.edit', compact('organization'));
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('organizations')->ignore($organization->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $organization->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Organización actualizada exitosamente',
                    'organization' => $organization->fresh(),
                ]);
            }

            return redirect()->route('organizations.show', $organization)
                ->with('success', '✅ Organización actualizada exitosamente');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '❌ Error al actualizar la organización',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', '❌ Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Request $request, Organization $organization)
    {
        $this->authorize('delete', $organization);

        try {
            DB::beginTransaction();

            // Remover todos los miembros (soft delete en pivot)
            $organization->users()->update([
                'is_active' => false,
                'left_at' => now(),
            ]);

            // Soft delete de la organización
            $organization->delete();

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Organización eliminada exitosamente',
                ]);
            }

            return redirect()->route('organizations.index')
                ->with('success', '✅ Organización eliminada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '❌ Error al eliminar la organización',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', '❌ Error al eliminar: ' . $e->getMessage());
        }
    }

    /**
     * Display members of the organization.
     */
    public function members(Request $request, Organization $organization)
    {
        $this->authorize('viewMembers', $organization);

        $members = $organization->activeUsers()
            ->withPivot(['role', 'joined_at', 'is_primary', 'invited_by'])
            ->with(['ownedOrganizations'])
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'members' => $members,
            ]);
        }

        return view('organizations.members', compact('organization', 'members'));
    }

    /**
     * Remove a member from the organization.
     */
    public function removeMember(Request $request, Organization $organization, User $user)
    {
        $this->authorize('removeMembers', $organization);

        // No se puede remover al owner
        if ($organization->isOwner($user)) {
            return response()->json([
                'message' => '❌ No puedes remover al propietario de la organización',
            ], 403);
        }

        try {
            // Obtener razón opcional de la request
            $reason = $request->input('reason');

            // Remover usuario guardando quién lo removió
            $organization->removeUser($user, $request->user(), $reason);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Miembro removido exitosamente',
                ]);
            }

            return back()->with('success', '✅ Miembro removido exitosamente');

        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al remover miembro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change a member's role in the organization.
     */
    public function changeMemberRole(Request $request, Organization $organization, User $user)
    {
        $this->authorize('changeMemberRoles', $organization);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['owner', 'admin', 'member'])],
        ]);

        // No se puede cambiar el rol del owner original
        if ($organization->isOwner($user)) {
            return response()->json([
                'message' => '❌ No puedes cambiar el rol del propietario',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Actualizar rol en pivot
            $organization->changeUserRole($user, $validated['role']);

            // Sincronizar rol de Spatie
            $user->syncRoles([$validated['role']]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Rol actualizado exitosamente',
                ]);
            }

            return back()->with('success', '✅ Rol actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => '❌ Error al cambiar el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Leave the organization (for members, not owner).
     */
    public function leave(Request $request, Organization $organization)
    {
        $this->authorize('leave', $organization);

        try {
            // Usuario se va voluntariamente (removed_by será él mismo)
            $organization->removeUser(
                $request->user(),
                $request->user(),
                'Salió voluntariamente de la organización'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Has salido de la organización',
                ]);
            }

            return redirect()->route('organizations.index')
                ->with('success', '✅ Has salido de la organización');

        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al salir de la organización',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
