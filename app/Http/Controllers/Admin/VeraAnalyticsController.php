<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VeraTokenUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * VeraAnalyticsController
 *
 * Panel de analytics para administradores
 * Muestra métricas detalladas de consumo de tokens
 */
class VeraAnalyticsController extends Controller
{
    /**
     * Dashboard principal de analytics
     */
    public function index()
    {
        // Totales generales
        $totalTokens = VeraTokenUsage::sum('total_tokens');
        $totalCost = VeraTokenUsage::sum('cost');
        $totalRequests = VeraTokenUsage::count();
        $totalUsers = VeraTokenUsage::distinct('user_id')->count();

        // Consumo por tipo de acción
        $byActionType = VeraTokenUsage::select('action_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('action_type')
            ->get();

        // Top 10 usuarios por consumo
        $topUsers = VeraTokenUsage::select(
                'user_id',
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(cost) as total_cost')
            )
            ->groupBy('user_id')
            ->orderBy('total_tokens', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->user_id);
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $user ? $user->name : 'Usuario eliminado',
                    'user_email' => $user ? $user->email : 'N/A',
                    'plan_type' => $user ? $user->plan_type : 'N/A',
                    'total_tokens' => $item->total_tokens,
                    'requests' => $item->requests,
                    'total_cost' => $item->total_cost,
                    'avg_tokens_per_request' => round($item->total_tokens / $item->requests, 2),
                ];
            });

        // Consumo últimos 30 días
        $last30Days = VeraTokenUsage::where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('COUNT(*) as requests')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Estadísticas por modelo
        $byModel = VeraTokenUsage::select('model', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('model')
            ->get();

        // Promedios
        $avgTokensPerRequest = $totalRequests > 0 ? round($totalTokens / $totalRequests, 2) : 0;
        $avgCostPerRequest = $totalRequests > 0 ? round($totalCost / $totalRequests, 6) : 0;

        return view('admin.vera.analytics', [
            'totalTokens' => $totalTokens,
            'totalCost' => $totalCost,
            'totalRequests' => $totalRequests,
            'totalUsers' => $totalUsers,
            'avgTokensPerRequest' => $avgTokensPerRequest,
            'avgCostPerRequest' => $avgCostPerRequest,
            'byActionType' => $byActionType,
            'topUsers' => $topUsers,
            'last30Days' => $last30Days,
            'byModel' => $byModel,
        ]);
    }

    /**
     * Analytics de un usuario específico
     */
    public function user($userId)
    {
        $user = User::findOrFail($userId);

        $usage = VeraTokenUsage::where('user_id', $userId)
            ->select(
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('AVG(total_tokens) as avg_tokens')
            )
            ->first();

        $byActionType = VeraTokenUsage::where('user_id', $userId)
            ->select('action_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('action_type')
            ->get();

        $recentActivity = VeraTokenUsage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.vera.user-analytics', [
            'user' => $user,
            'usage' => $usage,
            'byActionType' => $byActionType,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Exportar datos a CSV
     */
    public function export()
    {
        $data = VeraTokenUsage::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'vera-token-usage-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'ID',
                'Usuario',
                'Email',
                'Plan',
                'Acción',
                'Prompt Tokens',
                'Completion Tokens',
                'Total Tokens',
                'Modelo',
                'Costo',
                'Fecha',
            ]);

            // Data
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->user->name ?? 'N/A',
                    $row->user->email ?? 'N/A',
                    $row->user->plan_type ?? 'N/A',
                    $row->action_type,
                    $row->prompt_tokens,
                    $row->completion_tokens,
                    $row->total_tokens,
                    $row->model,
                    $row->cost,
                    $row->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
