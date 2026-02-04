<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationInvitationController extends Controller
{
    /**
     * Send an invitation to join the organization.
     */
    public function store(Request $request, Organization $organization)
    {
        $this->authorize('inviteMembers', $organization);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::in(['admin', 'member'])], // No se puede invitar directamente como owner
        ]);

        try {
            // Verificar si el usuario ya existe y ya es miembro
            $existingUser = User::where('email', $validated['email'])->first();

            if ($existingUser && $organization->hasUser($existingUser)) {
                return response()->json([
                    'message' => '❌ Este usuario ya es miembro de la organización',
                ], 422);
            }

            // Verificar si ya hay una invitación pendiente
            $existingInvitation = OrganizationInvitation::where('organization_id', $organization->id)
                ->where('email', $validated['email'])
                ->where('status', 'pending')
                ->first();

            if ($existingInvitation) {
                return response()->json([
                    'message' => '❌ Ya existe una invitación pendiente para este email',
                ], 422);
            }

            // Crear invitación
            $invitation = OrganizationInvitation::create([
                'organization_id' => $organization->id,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'invited_by' => $request->user()->id,
                'token' => Str::random(64),
                'status' => 'pending',
                'expires_at' => now()->addDays(7), // Expira en 7 días
            ]);

            // TODO: Enviar email con la invitación
            // Mail::to($invitation->email)->send(new OrganizationInvitationMail($invitation));

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Invitación enviada exitosamente',
                    'invitation' => $invitation,
                    'invitation_url' => route('organizations.invitations.accept', ['token' => $invitation->token]),
                ], 201);
            }

            return back()->with('success', '✅ Invitación enviada exitosamente');

        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al enviar la invitación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with(['organization', 'invitedBy'])
            ->firstOrFail();

        // Verificar si la invitación expiró
        if ($invitation->status !== 'pending' || $invitation->expires_at->isPast()) {
            return view('organizations.invitations.expired', compact('invitation'));
        }

        return view('organizations.invitations.accept', compact('invitation'));
    }

    /**
     * Accept the invitation and join the organization.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with('organization')
            ->firstOrFail();

        // Verificar estado de la invitación
        if ($invitation->status !== 'pending') {
            return response()->json([
                'message' => '❌ Esta invitación ya fue procesada',
            ], 422);
        }

        if ($invitation->expires_at->isPast()) {
            return response()->json([
                'message' => '❌ Esta invitación ha expirado',
            ], 422);
        }

        // Verificar que el email coincide con el usuario autenticado
        if ($request->user()->email !== $invitation->email) {
            return response()->json([
                'message' => '❌ Esta invitación no es para tu email',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $organization = $invitation->organization;
            $user = $request->user();

            // Agregar usuario a la organización
            $organization->addUser($user, $invitation->role, User::find($invitation->invited_by));

            // Asignar rol de Spatie
            $user->assignRole($invitation->role);

            // Marcar invitación como aceptada
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Te has unido a la organización exitosamente',
                    'organization' => $organization,
                ]);
            }

            return redirect()->route('organizations.show', $organization)
                ->with('success', '✅ Te has unido a la organización exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => '❌ Error al aceptar la invitación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decline the invitation.
     */
    public function decline(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)->firstOrFail();

        if ($invitation->status !== 'pending') {
            return response()->json([
                'message' => '❌ Esta invitación ya fue procesada',
            ], 422);
        }

        try {
            $invitation->update([
                'status' => 'declined',
                'declined_at' => now(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Invitación rechazada',
                ]);
            }

            return redirect()->route('home')->with('info', 'Invitación rechazada');

        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al rechazar la invitación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a pending invitation (only by inviter or organization owner).
     */
    public function destroy(Request $request, Organization $organization, OrganizationInvitation $invitation)
    {
        $this->authorize('inviteMembers', $organization);

        // Verificar que la invitación pertenece a esta organización
        if ($invitation->organization_id !== $organization->id) {
            return response()->json([
                'message' => '❌ Invitación no encontrada',
            ], 404);
        }

        try {
            $invitation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '✅ Invitación cancelada exitosamente',
                ]);
            }

            return back()->with('success', '✅ Invitación cancelada exitosamente');

        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al cancelar la invitación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List pending invitations for the organization.
     */
    public function index(Request $request, Organization $organization)
    {
        $this->authorize('viewMembers', $organization);

        $invitations = OrganizationInvitation::where('organization_id', $organization->id)
            ->with('invitedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'invitations' => $invitations,
            ]);
        }

        return view('organizations.invitations.index', compact('organization', 'invitations'));
    }
}
