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

        // Filtro por status (default: pending y processing)
        $statusFilter = $request->get('status', 'pending');

        $query = ScheduledEmail::where('user_id', $user->id);

        if ($statusFilter === 'pending') {
            // Emails pendientes de enviar
            $query->whereIn('status', ['pending', 'processing']);
        } elseif ($statusFilter === 'failed') {
            // Emails fallidos
            $query->where('status', 'failed');
        } elseif ($statusFilter === 'sent') {
            // Emails enviados
            $query->where('status', 'sent');
        } elseif ($statusFilter === 'cancelled') {
            // Emails cancelados
            $query->where('status', 'cancelled');
        }

        // Obtener emails programados con paginación
        $scheduledEmails = $query->orderBy('scheduled_at', 'desc')
            ->with(['emailAccount', 'template', 'originalEmail'])
            ->paginate(20);

        // Si es una petición AJAX, retornar JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'scheduled_emails' => $scheduledEmails,
            ]);
        }

        // Cargar stats para el sidebar (necesario para que no falle)
        $statsObj = \App\Models\Email::where('user_id', $user->id)
            ->where('status', 'received')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
            ')
            ->first();

        $baseQuery = \App\Models\Email::where('user_id', $user->id)->where('status', 'received');
        $overdue = (clone $baseQuery)->overdue()->count();
        $dueToday = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', today())
            ->where('workflow_status', '!=', 'completed')
            ->where('workflow_status', '!=', 'archived')
            ->count();
        $dueThisWeek = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('workflow_status', '!=', 'completed')
            ->where('workflow_status', '!=', 'archived')
            ->count();
        $snoozed = (clone $baseQuery)->snoozed()->count();

        $stats = [
            'total' => $statsObj->total ?? 0,
            'unread' => $statsObj->unread ?? 0,
            'starred' => $statsObj->starred ?? 0,
            'urgent' => 0,
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'due_this_week' => $dueThisWeek,
            'snoozed' => $snoozed,
        ];

        // Cargar categorías para el sidebar
        $categories = \App\Models\EmailCategory::withCount([
            'emails as unread_count' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_read', false);
            }
        ])->get();

        // Si es una petición normal, retornar vista Blade
        return view('emails.scheduled.index', compact('scheduledEmails', 'stats', 'categories'));
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

    public function show(Request $request, ScheduledEmail $scheduledEmail)
    {
        if ($scheduledEmail->user_id !== Auth::id()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }
            abort(403, 'No autorizado');
        }

        $scheduledEmail->load(['emailAccount', 'template', 'originalEmail']);

        // Si es petición AJAX, retornar JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'scheduled_email' => $scheduledEmail,
            ]);
        }

        // Cargar stats y categorías para el sidebar
        $user = Auth::user();
        $statsObj = \App\Models\Email::where('user_id', $user->id)
            ->where('status', 'received')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
            ')
            ->first();

        $baseQuery = \App\Models\Email::where('user_id', $user->id)->where('status', 'received');
        $overdue = (clone $baseQuery)->overdue()->count();
        $dueToday = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', today())
            ->where('workflow_status', '!=', 'completed')
            ->where('workflow_status', '!=', 'archived')
            ->count();
        $dueThisWeek = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('workflow_status', '!=', 'completed')
            ->where('workflow_status', '!=', 'archived')
            ->count();
        $snoozed = (clone $baseQuery)->snoozed()->count();

        $stats = [
            'total' => $statsObj->total ?? 0,
            'unread' => $statsObj->unread ?? 0,
            'starred' => $statsObj->starred ?? 0,
            'urgent' => 0,
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'due_this_week' => $dueThisWeek,
            'snoozed' => $snoozed,
        ];

        $categories = \App\Models\EmailCategory::withCount([
            'emails as unread_count' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_read', false);
            }
        ])->get();

        // Retornar vista Blade para edición
        return view('emails.scheduled.show', compact('scheduledEmail', 'stats', 'categories'));
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
