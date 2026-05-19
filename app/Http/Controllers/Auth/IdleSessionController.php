<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IdleSessionController extends Controller
{
    /**
     * Logout por inactividad. A diferencia de AuthenticatedSessionController::destroy,
     * NO llama Auth::logout() — eso ciclaría el remember_token en BD y rompería el
     * "recuérdame". Solo invalida la sesión PHP: la cookie laravel_session deja de
     * ser válida, pero la cookie remember_* sobrevive y al volver Laravel re-loguea.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'redirect' => route('login'),
        ]);
    }

    /**
     * Heartbeat: el simple hecho de pasar por el middleware web/StartSession ya
     * refresca el TTL de la sesión. No hace falta tocar nada más.
     */
    public function heartbeat(): Response
    {
        return response()->noContent();
    }
}
