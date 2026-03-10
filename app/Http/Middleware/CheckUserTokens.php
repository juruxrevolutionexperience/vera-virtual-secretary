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

        // Fuente única de verdad: límite por emails (igual que vera.limit middleware)
        if (!$user->hasEmailsAvailable()) {
            $used  = $user->monthlyEmailsUsed();
            $limit = $user->planEmailLimit();

            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'limite_alcanzado',
                    'message' => 'Has alcanzado el límite mensual de emails de tu plan.',
                    'plan'    => $user->plan_type,
                    'used'    => $used,
                    'limit'   => $limit,
                ], 402);
            }

            return redirect()->back()->with(
                'error',
                'Has alcanzado el límite mensual de emails de tu plan. Actualiza tu plan para continuar.'
            );
        }

        return $next($request);
    }
}
