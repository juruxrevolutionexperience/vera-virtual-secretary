<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserTokens
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->hasTokensAvailable()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'sin_tokens',
                    'message' => 'Has alcanzado el límite mensual de tokens IA de tu plan.',
                    'plan'    => $user->plan_type,
                    'used'    => $user->monthly_tokens_used,
                    'limit'   => $user->monthly_tokens_limit,
                ], 402);
            }

            return redirect()->back()->with(
                'error',
                'Has alcanzado el límite mensual de tokens IA. Actualiza tu plan para continuar.'
            );
        }

        return $next($request);
    }
}
