<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\FollowUpReminder;
use App\Services\FollowUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowUpReminderController extends Controller
{
    protected FollowUpService $followUpService;

    public function __construct(FollowUpService $followUpService)
    {
        $this->followUpService = $followUpService;
    }

    public function index(Request $request)
    {
        $query = FollowUpReminder::where('user_id', Auth::id())->with('email');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        $reminders = $query->orderBy('remind_at')->paginate($request->get('per_page', 20));

        // Si es petición AJAX, devolver JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($reminders);
        }

        // Si es petición normal del navegador, devolver vista
        return view('follow-up-reminders.index');
    }

    public function show(FollowUpReminder $reminder)
    {
        $this->authorize('view', $reminder);
        return response()->json($reminder->load('email'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email_id' => 'required|exists:emails,id',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'remind_at' => 'required|date|after:now',
            'is_recurring' => 'nullable|boolean',
            'recurrence_type' => 'nullable|in:daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_end_date' => 'nullable|date',
            'notification_channels' => 'nullable|array',
        ]);

        $email = Email::findOrFail($validated['email_id']);
        $reminder = $this->followUpService->createReminder(Auth::user(), $email, $validated);

        return response()->json($reminder, 201);
    }

    public function update(Request $request, FollowUpReminder $reminder)
    {
        $this->authorize('update', $reminder);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'notes' => 'nullable|string',
            'remind_at' => 'sometimes|required|date',
            'is_recurring' => 'nullable|boolean',
            'recurrence_type' => 'nullable|in:daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_end_date' => 'nullable|date',
            'notification_channels' => 'nullable|array',
        ]);

        $reminder = $this->followUpService->updateReminder($reminder, $validated);

        return response()->json($reminder);
    }

    public function destroy(FollowUpReminder $reminder)
    {
        $this->authorize('delete', $reminder);
        $this->followUpService->deleteReminder($reminder);

        return response()->json(['message' => 'Recordatorio eliminado correctamente']);
    }

    public function snooze(Request $request, FollowUpReminder $reminder)
    {
        $this->authorize('update', $reminder);

        $validated = $request->validate([
            'minutes' => 'required|integer|min:1|max:10080', // max 1 semana
        ]);

        $reminder = $this->followUpService->snoozeReminder($reminder, $validated['minutes']);

        return response()->json($reminder);
    }

    public function complete(FollowUpReminder $reminder)
    {
        $this->authorize('update', $reminder);
        $reminder = $this->followUpService->markAsCompleted($reminder);

        return response()->json($reminder);
    }

    public function cancel(FollowUpReminder $reminder)
    {
        $this->authorize('update', $reminder);
        $reminder = $this->followUpService->cancelReminder($reminder);

        return response()->json($reminder);
    }

    public function stats()
    {
        $stats = $this->followUpService->getStats(Auth::user());
        return response()->json($stats);
    }

    public function upcoming(Request $request)
    {
        $days = $request->get('days', 7);
        $reminders = $this->followUpService->getUpcomingReminders(Auth::user(), $days);

        return response()->json($reminders);
    }
}
