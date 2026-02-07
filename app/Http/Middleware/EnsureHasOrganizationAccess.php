<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Verificar que el usuario tenga plan Enterprise
        if (!auth()->user()->hasOrganizationAccess()) {
            // Si es una petición AJAX, retornar JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Acceso denegado',
                    'message' => 'Las funcionalidades de Organizaciones solo están disponibles en el plan Enterprise.',
                    'upgrade_url' => route('billing'),
                ], 403);
            }

            // Si es petición web, redirigir con mensaje
            return redirect()->route('emails.index')
                ->with('error', 'Las funcionalidades de Organizaciones solo están disponibles en el plan Enterprise. Actualiza tu plan para acceder.');
        }

        return $next($request);
    }
}
