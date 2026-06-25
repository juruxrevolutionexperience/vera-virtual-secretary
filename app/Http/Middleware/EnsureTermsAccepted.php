<?php

namespace App\Http\Middleware;

use App\Models\UserConsent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    // Incrementar esta version cuando los ToS cambien sustancialmente.
    // Todos los usuarios tendran que volver a aceptar.
    public const CURRENT_VERSION = '1.0';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $accepted = UserConsent::where('user_id', $user->id)
            ->where('consent_type', 'terms_of_service')
            ->where('consent_version', self::CURRENT_VERSION)
            ->where('is_granted', true)
            ->exists();

        if (!$accepted) {
            return redirect()->route('terms.accept');
        }

        return $next($request);
    }
}
