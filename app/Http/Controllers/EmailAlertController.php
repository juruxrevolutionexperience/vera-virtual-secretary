<?php

namespace App\Http\Controllers;

use App\Models\EmailAlert;
use App\Services\EmailAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailAlertController extends Controller
{
    protected EmailAlertService $alertService;

    public function __construct(EmailAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(Request $request)
    {
        $query = EmailAlert::where('user_id', Auth::id())->with('email');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        if ($request->has('severity')) {
            $query->bySeverity($request->severity);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $alerts = $query->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
                        ->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 20));

        // Si es petición AJAX, devolver JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($alerts);
        }

        // Si es petición normal del navegador, devolver vista
        return view('email-alerts.index');
    }

    public function show(EmailAlert $alert)
    {
        $this->authorize('view', $alert);
        return response()->json($alert->load('email'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email_id' => 'nullable|exists:emails,id',
            'type' => 'required|in:deadline,no_response,follow_up,escalation,overdue,high_priority,custom',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'metadata' => 'nullable|array',
            'alert_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        $alert = $this->alertService->createAlert(Auth::user(), $validated);

        return response()->json($alert, 201);
    }

    public function acknowledge(EmailAlert $alert)
    {
        $this->authorize('update', $alert);
        $alert = $this->alertService->acknowledgeAlert($alert);

        return response()->json($alert);
    }

    public function resolve(Request $request, EmailAlert $alert)
    {
        $this->authorize('update', $alert);
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $alert = $this->alertService->resolveAlert($alert, $validated['notes'] ?? null);

        return response()->json($alert);
    }

    public function dismiss(EmailAlert $alert)
    {
        $this->authorize('update', $alert);
        $alert = $this->alertService->dismissAlert($alert);

        return response()->json($alert);
    }

    public function stats()
    {
        $stats = $this->alertService->getStats(Auth::user());
        return response()->json($stats);
    }

    public function runChecks()
    {
        $stats = $this->alertService->runAllChecks(Auth::user());

        return response()->json([
            'message' => 'Verificación de alertas completada',
            'stats' => $stats,
        ]);
    }
}
