<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\AuditService;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Vista principal del historial de actividad
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filtro por categoría
        if ($request->filled('category')) {
            $query->where('event_category', $request->category);
        }

        // Filtro por severidad
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filtro por fecha desde
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro por fecha hasta
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtro por búsqueda libre
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(25)->withQueryString();

        // Estadísticas del usuario
        $stats = [
            'total'       => AuditLog::where('user_id', $user->id)->count(),
            'today'       => AuditLog::where('user_id', $user->id)->whereDate('created_at', today())->count(),
            'this_week'   => AuditLog::where('user_id', $user->id)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'critical'    => AuditLog::where('user_id', $user->id)->where('severity', 'critical')->count(),
            'failed'      => AuditLog::where('user_id', $user->id)->where('action_result', 'failed')->count(),
        ];

        // Categorías disponibles para filtrar
        $categories = AuditLog::where('user_id', $user->id)
            ->selectRaw('event_category, count(*) as total')
            ->groupBy('event_category')
            ->orderByDesc('total')
            ->get();

        return view('security.audit-logs', compact('logs', 'stats', 'categories'));
    }

    /**
     * Ver detalle de un log específico
     */
    public function show(Request $request, AuditLog $auditLog)
    {
        if ($auditLog->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($auditLog);
    }

    /**
     * Exportar logs a CSV
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $query = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('category')) {
            $query->where('event_category', $request->category);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->limit(1000)->get();

        $filename = 'actividad-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Fecha', 'Categoría', 'Tipo de Evento', 'Descripción', 'Resultado', 'Severidad', 'IP', 'Navegador']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('d/m/Y H:i:s'),
                    $log->event_category ?? '-',
                    $log->event_type ?? '-',
                    $log->description ?? '-',
                    $log->action_result ?? '-',
                    $log->severity ?? '-',
                    $log->ip_address ?? '-',
                    substr($log->user_agent ?? '-', 0, 80),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
