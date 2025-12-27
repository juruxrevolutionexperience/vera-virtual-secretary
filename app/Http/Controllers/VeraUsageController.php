<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $plans = [
            'basic' => [
                'name' => 'Basic',
                'price' => 5,
                'email_limit' => 100,
                'token_limit' => $this->usageService->emailsToTokens(100),
                'features' => [
                    'Análisis automático de emails',
                    'Respuestas sugeridas',
                    'Reglas básicas de filtrado',
                    'Soporte por email (48h)',
                ],
                'is_current' => $user->plan_type === 'basic',
            ],
            'starter' => [
                'name' => 'Starter',
                'price' => 15,
                'email_limit' => 250,
                'token_limit' => $this->usageService->emailsToTokens(250),
                'features' => [
                    'Todo de Basic',
                    'Respuestas automáticas',
                    'Análisis semanal',
                    'Perfiles de comunicación',
                    'Soporte prioritario (24h)',
                ],
                'is_current' => $user->plan_type === 'starter',
                'popular' => true,
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => 30,
                'email_limit' => 450,
                'token_limit' => $this->usageService->emailsToTokens(450),
                'features' => [
                    'Todo de Starter',
                    'Reglas ilimitadas',
                    'API access',
                    'Analytics avanzados',
                    'Soporte 24/7',
                ],
                'is_current' => $user->plan_type === 'pro',
            ],
        ];

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
        $validated = $request->validate([
            'plan' => 'required|in:basic,starter,pro',
        ]);

        $user = Auth::user();
        $newPlan = $validated['plan'];

        // Obtener límite del nuevo plan
        $emailLimits = [
            'basic' => 100,
            'starter' => 250,
            'pro' => 450,
        ];

        // Actualizar plan
        $user->update([
            'plan_type' => $newPlan,
            'monthly_email_limit' => $emailLimits[$newPlan],
            'plan_upgraded_at' => now(),
            'plan_metadata' => array_merge(
                $user->plan_metadata ?? [],
                [
                    'previous_plan' => $user->plan_type,
                    'upgraded_at' => now()->toDateTimeString(),
                    'upgraded_from_usage' => true,
                ]
            ),
        ]);

        // Resetear ciclo de facturación inmediatamente
        $this->usageService->resetBillingCycle($user);

        return response()->json([
            'success' => true,
            'message' => "Plan actualizado a {$newPlan} exitosamente",
            'plan' => $newPlan,
            'new_limit' => $emailLimits[$newPlan],
        ]);
    }
}
