<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VeraConfig;
use Symfony\Component\HttpFoundation\Response;

class EnsureVeraConfigured
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Verificar si el usuario tiene VeraConfig y si está completado
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config || !$config->onboarding_completed) {
            // No tiene Vera configurada o no completó onboarding
            // → Redirigir al DASHBOARD donde aparecerá el modal con la explicación
            return redirect()->route('dashboard')
                ->with('info', '💡 Por favor, configura Vera primero. El asistente te guiará.');
        }

        // Tiene Vera configurada y completada → Permitir acceso
        return $next($request);
    }
}
