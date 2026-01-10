<?php

namespace App\Http\Controllers;

use App\Models\AutoResponder;
use App\Services\AutoResponderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoResponderController extends Controller
{
    protected $autoResponderService;

    public function __construct(AutoResponderService $autoResponderService)
    {
        $this->autoResponderService = $autoResponderService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $activeOnly = $request->has('active') ? $request->boolean('active') : null;

        $autoResponders = $this->autoResponderService->getUserAutoResponders($user, $activeOnly);

        return response()->json([
            'success' => true,
            'auto_responders' => $autoResponders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:out_of_office,confirmation,thank_you,custom',
            'email_account_id' => 'nullable|exists:email_accounts,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'is_active' => 'boolean',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after:active_from',
            'sender_filters' => 'nullable|array',
            'subject_filters' => 'nullable|array',
            'only_first_contact' => 'boolean',
            'max_responses_per_sender' => 'nullable|integer|min:1',
            'max_responses_per_day' => 'nullable|integer|min:1',
            'delay_minutes' => 'nullable|integer|min:0',
            'schedule' => 'nullable|array',
            'reply_to_all' => 'boolean',
            'skip_auto_replies' => 'boolean',
        ]);

        $autoResponder = $this->autoResponderService->createAutoResponder(Auth::user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Auto-responder creado exitosamente',
            'auto_responder' => $autoResponder,
        ], 201);
    }

    public function show(AutoResponder $autoResponder)
    {
        if ($autoResponder->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'auto_responder' => $autoResponder->load(['template', 'emailAccount']),
        ]);
    }

    public function update(Request $request, AutoResponder $autoResponder)
    {
        if ($autoResponder->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:out_of_office,confirmation,thank_you,custom',
            'email_account_id' => 'nullable|exists:email_accounts,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'is_active' => 'boolean',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date',
            'sender_filters' => 'nullable|array',
            'subject_filters' => 'nullable|array',
            'only_first_contact' => 'boolean',
            'max_responses_per_sender' => 'nullable|integer|min:1',
            'max_responses_per_day' => 'nullable|integer|min:1',
            'delay_minutes' => 'nullable|integer|min:0',
            'schedule' => 'nullable|array',
            'reply_to_all' => 'boolean',
            'skip_auto_replies' => 'boolean',
        ]);

        $autoResponder = $this->autoResponderService->updateAutoResponder($autoResponder, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Auto-responder actualizado exitosamente',
            'auto_responder' => $autoResponder,
        ]);
    }

    public function destroy(AutoResponder $autoResponder)
    {
        if ($autoResponder->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $this->autoResponderService->deleteAutoResponder($autoResponder);

        return response()->json([
            'success' => true,
            'message' => 'Auto-responder eliminado exitosamente',
        ]);
    }

    public function toggleActive(AutoResponder $autoResponder)
    {
        if ($autoResponder->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $autoResponder = $this->autoResponderService->toggleActive($autoResponder);

        return response()->json([
            'success' => true,
            'auto_responder' => $autoResponder,
        ]);
    }

    public function stats()
    {
        $stats = $this->autoResponderService->getStats(Auth::user());

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
