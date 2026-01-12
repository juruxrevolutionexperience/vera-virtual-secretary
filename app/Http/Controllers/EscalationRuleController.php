<?php

namespace App\Http\Controllers;

use App\Models\EscalationRule;
use App\Services\EscalationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EscalationRuleController extends Controller
{
    protected EscalationService $escalationService;

    public function __construct(EscalationService $escalationService)
    {
        $this->escalationService = $escalationService;
    }

    public function index(Request $request)
    {
        $query = EscalationRule::where('user_id', Auth::id());

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->byPriority()->paginate($request->get('per_page', 20));

        return response()->json($rules);
    }

    public function show(EscalationRule $rule)
    {
        $this->authorize('view', $rule);
        return response()->json($rule);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'conditions' => 'required|array',
            'action_type' => 'required|in:create_alert,notify_email,change_priority,assign_to_folder,add_tag,mark_as_urgent,forward_email,custom',
            'action_config' => 'required|array',
            'max_executions_per_email' => 'nullable|integer|min:1',
            'cooldown_minutes' => 'nullable|integer|min:1',
        ]);

        $rule = $this->escalationService->createRule(Auth::user(), $validated);

        return response()->json($rule, 201);
    }

    public function update(Request $request, EscalationRule $rule)
    {
        $this->authorize('update', $rule);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'conditions' => 'sometimes|required|array',
            'action_type' => 'sometimes|required|in:create_alert,notify_email,change_priority,assign_to_folder,add_tag,mark_as_urgent,forward_email,custom',
            'action_config' => 'sometimes|required|array',
            'max_executions_per_email' => 'nullable|integer|min:1',
            'cooldown_minutes' => 'nullable|integer|min:1',
        ]);

        $rule = $this->escalationService->updateRule($rule, $validated);

        return response()->json($rule);
    }

    public function destroy(EscalationRule $rule)
    {
        $this->authorize('delete', $rule);
        $this->escalationService->deleteRule($rule);

        return response()->json(['message' => 'Regla eliminada correctamente']);
    }

    public function activate(EscalationRule $rule)
    {
        $this->authorize('update', $rule);
        $rule = $this->escalationService->activateRule($rule);

        return response()->json($rule);
    }

    public function deactivate(EscalationRule $rule)
    {
        $this->authorize('update', $rule);
        $rule = $this->escalationService->deactivateRule($rule);

        return response()->json($rule);
    }

    public function stats()
    {
        $stats = $this->escalationService->getRuleStats(Auth::user());
        return response()->json($stats);
    }

    public function processEscalations(Request $request)
    {
        $stats = $this->escalationService->processUserEscalations(
            Auth::user(),
            $request->get('limit', 100)
        );

        return response()->json([
            'message' => 'Escalados procesados correctamente',
            'stats' => $stats,
        ]);
    }
}
