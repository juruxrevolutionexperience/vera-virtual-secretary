<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Services\VeraUsageService;
use Illuminate\Support\Facades\Auth;

/**
 * VeraUsageController
 *
 * Controlador para gestionar la vista de uso de Vera
 * y visualización de límites para los usuarios
 */
class VeraUsageController extends Controller
{
    protected VeraUsageService $usageService;

    public function __construct()
    {
        $this->usageService = new VeraUsageService();
    }

    /**
     * Mostrar página de uso
     */
    public function index()
    {
        $user = Auth::user();
        $usage = $this->usageService->calculateMonthlyUsage($user);
        $alertLevel = $this->usageService->getAlertLevel($user);

        return view('settings.usage', [
            'usage' => $usage,
            'user' => $user,
            'alertLevel' => $alertLevel,
        ]);
    }

    /**
     * Obtener uso actual (API)
     */
    public function current()
    {
        $user = Auth::user();
        $usage = $this->usageService->calculateMonthlyUsage($user);

        return response()->json([
            'success' => true,
            'usage' => $usage,
            'alert_level' => $this->usageService->getAlertLevel($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan_type' => $user->plan_type,
                'monthly_email_limit' => $user->monthly_email_limit,
            ],
        ]);
    }

    /**
     * Mostrar página de planes
     */
    public function plans()
    {
        $user = Auth::user();
        $currentUsage = $this->usageService->calculateMonthlyUsage($user);

        $dbPlans = Plan::active()->orderBy('price_eur')->get();

        if ($dbPlans->isNotEmpty()) {
            $plans = $dbPlans->keyBy('slug')->map(function (Plan $plan) use ($user) {
                return [
                    'name'       => $plan->name,
                    'price'      => (float) $plan->price_eur,
                    'email_limit'=> $plan->email_limit,
                    'token_limit'=> $plan->token_limit ?? 0,
                    'features'   => $plan->features ?? [],
                    'is_current' => $user->plan_type === $plan->slug,
                    'unlimited'  => $plan->isUnlimited(),
                ];
            })->toArray();
        } else {
            // Fallback hardcoded si la tabla está vacía
            $plans = [
                'basic' => [
                    'name' => 'Basic', 'price' => 5, 'email_limit' => 100,
                    'token_limit' => $this->usageService->emailsToTokens(100),
                    'features' => ['Análisis automático de emails', 'Respuestas sugeridas'],
                    'is_current' => $user->plan_type === 'basic', 'unlimited' => false,
                ],
                'starter' => [
                    'name' => 'Starter', 'price' => 15, 'email_limit' => 500,
                    'token_limit' => $this->usageService->emailsToTokens(500),
                    'features' => ['Todo de Basic', 'Respuestas automáticas', 'Análisis semanal'],
                    'is_current' => $user->plan_type === 'starter', 'unlimited' => false, 'popular' => true,
                ],
                'pro' => [
                    'name' => 'Pro', 'price' => 30, 'email_limit' => 2000,
                    'token_limit' => $this->usageService->emailsToTokens(2000),
                    'features' => ['Todo de Starter', 'Analytics avanzados', 'Soporte 24/7'],
                    'is_current' => $user->plan_type === 'pro', 'unlimited' => false,
                ],
            ];
        }

        return view('settings.plans', [
            'plans' => $plans,
            'user' => $user,
            'currentUsage' => $currentUsage,
        ]);
    }

    /**
     * Actualizar plan del usuario
     */
    public function updatePlan(Request $request)
    {
        $validSlugs = Plan::active()->pluck('slug')->toArray() ?: ['basic', 'starter', 'pro', 'enterprise'];

        $validated = $request->validate([
            'plan' => ['required', 'in:' . implode(',', $validSlugs)],
        ]);

        $user    = Auth::user();
        $newPlan = $validated['plan'];

        $plan = Plan::findBySlug($newPlan);
        abort_unless($plan, 422, 'Plan no válido');

        $user->update([
            'plan_type'            => $plan->slug,
            'monthly_email_limit'  => $plan->email_limit ?? 999999,
            'monthly_tokens_limit' => $plan->token_limit ?? 0,
            'plan_upgraded_at'     => now(),
            'plan_metadata'        => array_merge(
                $user->plan_metadata ?? [],
                [
                    'previous_plan'       => $user->plan_type,
                    'upgraded_at'         => now()->toDateTimeString(),
                    'upgraded_from_usage' => true,
                ]
            ),
        ]);

        $this->usageService->resetBillingCycle($user);

        return response()->json([
            'success'   => true,
            'message'   => "Plan actualizado a {$plan->name} exitosamente",
            'plan'      => $plan->slug,
            'new_limit' => $plan->email_limit,
        ]);
    }
}
