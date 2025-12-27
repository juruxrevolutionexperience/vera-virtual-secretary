<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\VeraUsageService;
use Symfony\Component\HttpFoundation\Response;

/**
 * VeraEmailLimitMiddleware
 *
 * Middleware para verificar que el usuario no ha excedido
 * su límite mensual de emails procesados
 */
class VeraEmailLimitMiddleware
{
    protected VeraUsageService $usageService;

    public function __construct()
    {
        $this->usageService = new VeraUsageService();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, continuar
        if (!$user) {
            return $next($request);
        }

        // Verificar si puede procesar más emails
        if (!$this->usageService->canProcessEmail($user)) {
            // Usuario ha alcanzado el límite
            $usage = $this->usageService->calculateMonthlyUsage($user);

            // Si es request JSON/API
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Usage limit exceeded',
                    'message' => "Has alcanzado el límite de tu plan {$user->plan_type}.",
                    'details' => [
                        'plan' => $user->plan_type,
                        'limit' => $user->monthly_email_limit,
                        'used' => $usage['emails_processed'],
                        'remaining' => 0,
                        'reset_date' => $usage['billing_cycle_end'],
                    ],
                    'actions' => [
                        'upgrade_url' => route('settings.plans'),
                        'usage_url' => route('settings.usage'),
                    ],
                ], 429); // 429 Too Many Requests
            }

            // Si es request web, redirigir a página de uso
            return redirect()
                ->route('settings.usage')
                ->with('error', "Has alcanzado el límite de tu plan {$user->plan_type}.")
                ->with('limit_exceeded', true)
                ->with('usage', $usage);
        }

        // Usuario tiene límite disponible, continuar
        return $next($request);
    }
}
