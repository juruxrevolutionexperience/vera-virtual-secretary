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

        // ── Estado del plan actual (reuniones, no emails) ─────────────────────
        $meetingsUsed      = $user->monthlyMeetingsUsed();
        $meetingsLimit     = $user->planMeetingLimit();    // null = ilimitado
        $meetingsRemaining = $user->meetingsRemaining();   // null = ilimitado

        $usagePct = ($meetingsLimit && $meetingsLimit > 0)
            ? min(100, round($meetingsUsed / $meetingsLimit * 100))
            : 0;

        $planStatus = [
            'name'               => $user->planName(),
            'plan_type'          => $user->plan_type ?? 'basic',
            'meetings_used'      => $meetingsUsed,
            'meetings_limit'     => $meetingsLimit,
            'meetings_remaining' => $meetingsRemaining,
            'unlimited'          => $meetingsLimit === null,
            'usage_pct'          => $usagePct,
            'at_limit'           => $meetingsLimit !== null && $meetingsUsed >= $meetingsLimit,
        ];

        // ── Stats básicas del mes ─────────────────────────────────────────────
        $stats = VeraTokenUsage::getStatsByUser($user->id, $year, $month);

        // ── Coste real recalculado (input vs output diferenciado) ─────────────
        $realCost = VeraTokenUsage::getRealCostByUser($user->id, $year, $month);

        // ── Proyección de coste con N usuarios ────────────────────────────────
        $projection = VeraTokenUsage::getCostProjection($user->id, $year, $month);

        // ── Calculadora de pricing por plan ───────────────────────────────────
        $planPricing = VeraTokenUsage::getPlanPricingData($user->id);

        // ── Historial paginado del mes (sin eager load de Email) ──────────────
        $history = VeraTokenUsage::byUser($user->id)
            ->forMonth($year, $month)
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

        // Labels para los tipos de acción reales del nuevo Vera
        $actionLabels = [
            'intent_detection'    => 'Detección de intención',
            'response_generation' => 'Respuesta generada',
            'slot_proposal'       => 'Propuesta de horario',
            'chat_interaction'    => 'Chat con Vera',
            'quick_classify'      => 'Clasificación rápida',
        ];

        return view('vera.tokens.index', compact(
            'user', 'stats', 'realCost', 'projection', 'planPricing',
            'history', 'dailyUsage', 'availableMonths',
            'actionLabels', 'year', 'month', 'planStatus'
        ));
    }
}
