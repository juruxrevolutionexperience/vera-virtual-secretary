<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VeraTokenUsage;
use Carbon\Carbon;

class VeraTokensController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $now  = Carbon::now();

        $year  = (int) $request->get('year',  $now->year);
        $month = (int) $request->get('month', $now->month);

        // ── Stats básicas del mes ─────────────────────────────────────────────
        $stats = VeraTokenUsage::getStatsByUser($user->id, $year, $month);

        // ── Coste real recalculado (input vs output diferenciado) ─────────────
        $realCost = VeraTokenUsage::getRealCostByUser($user->id, $year, $month);

        // ── Proyección de coste con N usuarios ────────────────────────────────
        $projection = VeraTokenUsage::getCostProjection($user->id, $year, $month);

        // ── Calculadora de pricing por plan (emails/mes × tokens/email) ───────
        $planPricing = VeraTokenUsage::getPlanPricingData($user->id);

        // ── Historial paginado del mes ────────────────────────────────────────
        $history = VeraTokenUsage::byUser($user->id)
            ->forMonth($year, $month)
            ->with('email:id,subject,from_email')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // ── Consumo diario (para el gráfico) ─────────────────────────────────
        $dailyUsage = VeraTokenUsage::byUser($user->id)
            ->forMonth($year, $month)
            ->selectRaw('DATE(created_at) as day, SUM(total_tokens) as tokens, COUNT(*) as calls')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // ── Meses disponibles (selector) ──────────────────────────────────────
        $availableMonths = VeraTokenUsage::byUser($user->id)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at) DESC, MONTH(created_at) DESC')
            ->get();

        $actionLabels = [
            'email_analysis'      => 'Análisis de email',
            'response_generation' => 'Respuesta generada',
            'chat_interaction'    => 'Chat con Vera',
        ];

        return view('vera.tokens.index', compact(
            'user', 'stats', 'realCost', 'projection', 'planPricing',
            'history', 'dailyUsage', 'availableMonths',
            'actionLabels', 'year', 'month'
        ));
    }
}
