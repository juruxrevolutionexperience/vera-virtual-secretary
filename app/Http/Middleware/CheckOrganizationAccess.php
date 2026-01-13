<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Organization;

class CheckOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el usuario tiene acceso a la organización especificada.
     * La organización puede venir de:
     * - Parámetro de ruta: {organization}
     * - Query string: ?organization_id=X
     * - Header: X-Organization-Id
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, dejar que el auth middleware lo maneje
        if (!$user) {
            return $next($request);
        }

        // Obtener organization_id de diferentes fuentes
        $organizationId = $this->getOrganizationId($request);

        // Si no se especificó organización, usar la principal del usuario si existe
        if (!$organizationId) {
            $primaryOrg = $user->primaryOrganization();
            if ($primaryOrg) {
                $organizationId = $primaryOrg->id;
                // Añadir al request para uso posterior
                $request->merge(['organization_id' => $organizationId]);
            }
        }

        // Si hay organization_id, verificar acceso
        if ($organizationId) {
            $organization = Organization::find($organizationId);

            // Verificar que la organización existe
            if (!$organization) {
                return response()->json([
                    'error' => 'Organization not found',
                    'message' => 'The specified organization does not exist.'
                ], 404);
            }

            // Verificar que la organización está activa
            if (!$organization->is_active) {
                return response()->json([
                    'error' => 'Organization inactive',
                    'message' => 'This organization is not active.'
                ], 403);
            }

            // Verificar que el usuario pertenece a la organización
            if (!$user->belongsToOrganization($organization)) {
                return response()->json([
                    'error' => 'Access denied',
                    'message' => 'You do not have access to this organization.'
                ], 403);
            }

            // Verificar que el usuario está activo en la organización
            $membership = $user->organizations()
                ->where('organization_id', $organizationId)
                ->first();

            if ($membership && !$membership->pivot->is_active) {
                return response()->json([
                    'error' => 'Membership inactive',
                    'message' => 'Your membership in this organization is not active.'
                ], 403);
            }

            // Añadir organización al request para uso en controllers
            $request->attributes->add(['organization' => $organization]);
            $request->merge(['current_organization' => $organization]);
        }

        return $next($request);
    }

    /**
     * Obtener organization_id de diferentes fuentes
     */
    protected function getOrganizationId(Request $request): ?int
    {
        // 1. De parámetro de ruta
        if ($request->route('organization')) {
            $org = $request->route('organization');
            return is_numeric($org) ? (int) $org : $org->id ?? null;
        }

        // 2. De query string
        if ($request->has('organization_id')) {
            return (int) $request->query('organization_id');
        }

        // 3. De body (para POST/PUT)
        if ($request->has('organization_id')) {
            return (int) $request->input('organization_id');
        }

        // 4. De header personalizado
        if ($request->header('X-Organization-Id')) {
            return (int) $request->header('X-Organization-Id');
        }

        return null;
    }
}
