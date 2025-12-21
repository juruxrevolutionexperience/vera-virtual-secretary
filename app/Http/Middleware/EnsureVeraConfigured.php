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

        // Verificar si el usuario tiene VeraConfig
        $hasVeraConfig = VeraConfig::where('user_id', $user->id)->exists();

        if (!$hasVeraConfig) {
            // No tiene Vera configurada → Redirigir a onboarding
            return redirect()->route('vera.onboarding')
                ->with('warning', '⚠️ Debes configurar Vera para acceder a tus emails');
        }

        // Tiene Vera configurada → Permitir acceso
        return $next($request);
    }
}
