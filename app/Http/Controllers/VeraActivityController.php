<?php

namespace App\Http\Controllers;

use App\Services\VeraActivityService;
use App\Services\VeraUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Controlador para el Registro de Actividad de Vera
 * INTEGRADO con: Tokens, Planes, Feedback
 */
class VeraActivityController extends Controller
{
    protected VeraActivityService $activityService;
    protected VeraUsageService $usageService;

    public function __construct()
    {
        $this->activityService = new VeraActivityService();
        $this->usageService = new VeraUsageService();
    }

    /**
     * Mostrar el registro de actividad principal
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Filtros del request
        $filters = [
            'type' => $request->get('type'),
            'feedback' => $request->get('feedback'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'confidence' => $request->get('confidence'),
        ];

        // Obtener actividades paginadas con filtros
        $activities = $this->activityService->getActivitiesPaginated($user, $filters, 20);

        // Estadísticas del período actual
        $stats = $this->activityService->getActivityStats($user);

        // INTEGRACIÓN: Uso actual del plan
        $currentUsage = $this->usageService->calculateMonthlyUsage($user);
        $alertLevel = $this->usageService->getAlertLevel($user);

        return view('vera.activity.index', compact(
            'activities',
            'stats',
            'filters',
            'currentUsage',
            'alertLevel'
        ));
    }

    /**
     * Ver detalles de una actividad específica
     */
    public function show($id)
    {
        $activity = \App\Models\VeraActivityLog::with([
            'email',
            'veraAction',
            'tokenUsage',
            'user'
        ])->findOrFail($id);

        // Verificar que pertenece al usuario
        $this->authorize('view', $activity);

        return view('vera.activity.show', compact('activity'));
    }

    /**
     * Actualizar feedback de una actividad
     */
    public function updateFeedback(Request $request, $id)
    {
        $validated = $request->validate([
            'feedback' => 'required|in:approved,rejected,modified',
            'notes' => 'nullable|string|max:500',
        ]);

        $activity = \App\Models\VeraActivityLog::findOrFail($id);

        // Verificar que pertenece al usuario
        $this->authorize('update', $activity);

        $updated = $this->activityService->updateFeedback(
            $id,
            $validated['feedback'],
            $validated['notes'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'activity' => $updated,
                'message' => 'Feedback actualizado correctamente'
            ]);
        }

        return back()->with('success', 'Feedback guardado correctamente');
    }

    /**
     * Obtener estadísticas en formato JSON (para gráficos)
     */
    public function stats(Request $request)
    {
        $user = Auth::user();

        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : null;

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : null;

        $stats = $this->activityService->getActivityStats($user, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Exportar actividades a CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'type' => $request->get('type'),
            'feedback' => $request->get('feedback'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'confidence' => $request->get('confidence'),
        ];

        $activities = \App\Models\VeraActivityLog::with(['email', 'veraAction', 'tokenUsage'])
            ->byUser($user->id)
            ->applyFilters($filters)
            ->orderByDesc('created_at')
            ->get();

        $filename = "vera-activity-" . now()->format('Y-m-d-His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($activities) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, [
                'Fecha',
                'Tipo',
                'Título',
                'Descripción',
                'Email De',
                'Email Asunto',
                'Tokens',
                'Costo',
                'Confianza',
                'Feedback',
                'Plan',
            ]);

            // Datos
            foreach ($activities as $activity) {
                fputcsv($file, [
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->activity_type,
                    $activity->title,
                    $activity->description,
                    $activity->email?->from_email ?? '-',
                    $activity->email?->subject ?? '-',
                    $activity->tokens_consumed,
                    number_format($activity->cost, 6),
                    $activity->confidence_score ?? '-',
                    $activity->user_feedback,
                    $activity->plan_type_at_time,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
