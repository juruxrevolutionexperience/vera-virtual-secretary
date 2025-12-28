<?php

namespace App\Http\Controllers;

use App\Services\VeraAnalyticsService;
use App\Services\VeraUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Controlador para Métricas y Analytics de Vera
 * FASE 2: Dashboard con gráficos y análisis avanzados
 */
class VeraAnalyticsController extends Controller
{
    protected VeraAnalyticsService $analyticsService;
    protected VeraUsageService $usageService;

    public function __construct()
    {
        $this->analyticsService = new VeraAnalyticsService();
        $this->usageService = new VeraUsageService();
    }

    /**
     * Mostrar dashboard de analytics
     */
    public function index()
    {
        $user = Auth::user();

        // Obtener resumen ejecutivo completo
        $summary = $this->analyticsService->getExecutiveSummary($user);

        // Uso actual del plan
        $currentUsage = $this->usageService->calculateMonthlyUsage($user);
        $alertLevel = $this->usageService->getAlertLevel($user);

        return view('vera.analytics.index', compact(
            'summary',
            'currentUsage',
            'alertLevel'
        ));
    }

    /**
     * API: Obtener datos de tendencia de actividad
     */
    public function apiActivityTrend(Request $request)
    {
        $user = Auth::user();
        $days = $request->get('days', 30);

        $data = $this->analyticsService->getActivityTrend($user, $days);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener distribución de actividades por tipo
     */
    public function apiDistribution(Request $request)
    {
        $user = Auth::user();

        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : null;

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : null;

        $data = $this->analyticsService->getActivityDistribution($user, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener comparativa entre meses
     */
    public function apiMonthComparison()
    {
        $user = Auth::user();
        $data = $this->analyticsService->getMonthComparison($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener costos por día de la semana
     */
    public function apiCostByDayOfWeek(Request $request)
    {
        $user = Auth::user();
        $weeks = $request->get('weeks', 4);

        $data = $this->analyticsService->getCostByDayOfWeek($user, $weeks);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener top categorías de emails
     */
    public function apiTopCategories(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 10);

        $data = $this->analyticsService->getTopEmailCategories($user, $limit);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener predicción de consumo mensual
     */
    public function apiPrediction()
    {
        $user = Auth::user();
        $data = $this->analyticsService->predictMonthlyUsage($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener análisis de feedback
     */
    public function apiFeedbackAnalysis()
    {
        $user = Auth::user();
        $data = $this->analyticsService->getFeedbackAnalysis($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Obtener resumen ejecutivo completo
     */
    public function apiExecutiveSummary()
    {
        $user = Auth::user();
        $data = $this->analyticsService->getExecutiveSummary($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
