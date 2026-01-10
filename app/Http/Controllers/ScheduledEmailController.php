<?php

namespace App\Http\Controllers;

use App\Models\ScheduledEmail;
use App\Services\ScheduledEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduledEmailController extends Controller
{
    protected $scheduledEmailService;

    public function __construct(ScheduledEmailService $scheduledEmailService)
    {
        $this->scheduledEmailService = $scheduledEmailService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $scheduledEmails = $this->scheduledEmailService->getUserScheduledEmails(
            $user,
            $request->status
        );

        return response()->json([
            'success' => true,
            'scheduled_emails' => $scheduledEmails,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email_account_id' => 'required|exists:email_accounts,id',
            'original_email_id' => 'nullable|exists:emails,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'to' => 'required', // string o array
            'cc' => 'nullable',
            'bcc' => 'nullable',
            'subject' => 'required|string',
            'body' => 'required|string',
            'body_html' => 'nullable|string',
            'attachments' => 'nullable|array',
            'scheduled_at' => 'required|date|after:now',
            'metadata' => 'nullable|array',
            'template_data' => 'nullable|array',
        ]);

        $scheduledEmail = $this->scheduledEmailService->scheduleEmail(Auth::user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Email programado exitosamente',
            'scheduled_email' => $scheduledEmail,
        ], 201);
    }

    public function show(ScheduledEmail $scheduledEmail)
    {
        if ($scheduledEmail->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'scheduled_email' => $scheduledEmail->load(['emailAccount', 'template', 'originalEmail']),
        ]);
    }

    public function update(Request $request, ScheduledEmail $scheduledEmail)
    {
        if ($scheduledEmail->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'to' => 'sometimes',
            'cc' => 'nullable',
            'bcc' => 'nullable',
            'subject' => 'sometimes|string',
            'body' => 'sometimes|string',
            'body_html' => 'nullable|string',
            'attachments' => 'nullable|array',
            'scheduled_at' => 'sometimes|date|after:now',
            'metadata' => 'nullable|array',
        ]);

        $scheduledEmail = $this->scheduledEmailService->updateScheduledEmail($scheduledEmail, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Email programado actualizado',
            'scheduled_email' => $scheduledEmail,
        ]);
    }

    public function cancel(ScheduledEmail $scheduledEmail)
    {
        if ($scheduledEmail->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $scheduledEmail = $this->scheduledEmailService->cancelScheduledEmail($scheduledEmail);

        return response()->json([
            'success' => true,
            'message' => 'Email programado cancelado',
            'scheduled_email' => $scheduledEmail,
        ]);
    }

    public function retry(ScheduledEmail $scheduledEmail)
    {
        if ($scheduledEmail->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $success = $this->scheduledEmailService->retryFailedEmail($scheduledEmail);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Email reenviado exitosamente' : 'Error al reenviar email',
            'scheduled_email' => $scheduledEmail->fresh(),
        ]);
    }

    public function stats()
    {
        $stats = $this->scheduledEmailService->getStats(Auth::user());

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
