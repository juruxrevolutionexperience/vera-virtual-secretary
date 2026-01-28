<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Services\EmailWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailWorkflowController extends Controller
{
    protected $workflowService;

    public function __construct(EmailWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Obtener resumen de workflow del usuario
     */
    public function summary()
    {
        $user = Auth::user();
        $summary = $this->workflowService->getWorkflowSummary($user);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Cambiar estado de workflow de un email
     */
    public function changeStatus(Request $request, Email $email)
    {
        // Verificar que pertenece al usuario
        if ($email->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:unread,pending_validation,pending_response,in_progress,in_review,approved,completed,archived,no_action_needed',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $email = $this->workflowService->changeWorkflowStatus(
                $email,
                $validated['status'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cambiar estado en masa
     */
    public function bulkChangeStatus(Request $request)
    {
        $validated = $request->validate([
            'email_ids' => 'required|array',
            'email_ids.*' => 'exists:emails,id',
            'status' => 'required|string|in:unread,pending_validation,pending_response,in_progress,in_review,approved,completed,archived,no_action_needed',
        ]);

        $user = Auth::user();

        try {
            $updatedCount = $this->workflowService->bulkChangeWorkflowStatus(
                $validated['email_ids'],
                $validated['status'],
                $user
            );

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} emails actualizados",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Marcar como requiere acción
     */
    public function markAsRequiringAction(Request $request, Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $email = $this->workflowService->markAsRequiringAction($email, $validated['notes']);

        return response()->json([
            'success' => true,
            'message' => 'Email marcado como requiere acción',
            'email' => $email,
        ]);
    }

    /**
     * Completar acción
     */
    public function completeAction(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $email = $this->workflowService->completeAction($email);

        return response()->json([
            'success' => true,
            'message' => 'Acción completada',
            'email' => $email,
        ]);
    }

    /**
     * Posponer email (snooze)
     */
    public function snooze(Request $request, Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'snoozed_until' => 'required|date|after:now',
        ]);

        $email = $this->workflowService->snoozeEmail($email, new \DateTime($validated['snoozed_until']));

        return response()->json([
            'success' => true,
            'message' => 'Email pospuesto',
            'email' => $email,
        ]);
    }

    /**
     * Despertar del snooze
     */
    public function wakeup(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $email = $this->workflowService->wakeupEmail($email);

        return response()->json([
            'success' => true,
            'message' => 'Email despertado',
            'email' => $email,
        ]);
    }

    /**
     * Establecer fecha de vencimiento
     */
    public function setDueDate(Request $request, Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'due_date' => 'nullable|date|after:now',
        ]);

        $dueDate = $validated['due_date'] ? new \DateTime($validated['due_date']) : null;
        $email = $this->workflowService->setDueDate($email, $dueDate);

        return response()->json([
            'success' => true,
            'message' => $dueDate ? 'Fecha de vencimiento establecida' : 'Fecha de vencimiento eliminada',
            'email' => $email,
        ]);
    }

    /**
     * Obtener emails vencidos
     */
    public function overdue()
    {
        $user = Auth::user();
        $emails = $this->workflowService->getOverdueEmails($user);

        return response()->json([
            'success' => true,
            'count' => $emails->count(),
            'emails' => $emails,
        ]);
    }

    /**
     * Obtener emails que vencen pronto
     */
    public function dueSoon(Request $request)
    {
        $hours = $request->input('hours', 24);
        $user = Auth::user();
        $emails = $this->workflowService->getDueSoonEmails($user, $hours);

        return response()->json([
            'success' => true,
            'count' => $emails->count(),
            'emails' => $emails,
        ]);
    }

    /**
     * Mover a papelera
     */
    public function moveToTrash(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $email = $this->workflowService->moveToTrash($email);

        return response()->json([
            'success' => true,
            'message' => 'Email movido a papelera',
            'email' => $email,
        ]);
    }

    /**
     * Mover múltiples a papelera
     */
    public function bulkMoveToTrash(Request $request)
    {
        $validated = $request->validate([
            'email_ids' => 'required|array',
            'email_ids.*' => 'exists:emails,id',
        ]);

        $user = Auth::user();

        $movedCount = $this->workflowService->bulkMoveToTrash($validated['email_ids'], $user);

        return response()->json([
            'success' => true,
            'message' => "{$movedCount} emails movidos a papelera",
            'moved_count' => $movedCount,
        ]);
    }

    /**
     * Restaurar de papelera
     */
    public function restoreFromTrash(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            return back()->with('error', 'No autorizado');
        }

        $email = $this->workflowService->restoreFromTrash($email);

        return redirect()->route('emails.trash')->with('success', 'Email restaurado correctamente');
    }

    /**
     * Vaciar papelera
     */
    public function emptyTrash()
    {
        $user = Auth::user();
        $deletedCount = $this->workflowService->emptyTrash($user);

        return redirect()->route('emails.trash')->with('success', "Papelera vaciada: {$deletedCount} emails eliminados permanentemente");
    }
}
