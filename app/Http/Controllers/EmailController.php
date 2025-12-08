<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailCategory;
use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    /**
     * Display a listing of emails
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Verificar si hay alguna cuenta sincronizando
        $syncingAccount = null;

        // Primero verificar si viene de un redirect con syncing_account_id
        if (session()->has('syncing_account_id')) {
            $syncingAccount = EmailAccount::find(session('syncing_account_id'));
            session()->forget('syncing_account_id'); // Limpiar para que no persista
        }

        // Si no, buscar cuentas actualmente sincronizando
        if (!$syncingAccount) {
            $syncingAccount = EmailAccount::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('sync_status', 'initial_sync_running')
                        ->orWhere('sync_status', 'incremental_sync_running');
                })
                ->first();
        }

        // Verificar si el usuario tiene cuentas de email conectadas
        $hasEmailAccounts = EmailAccount::where('user_id', $user->id)->exists();

        // Obtener categorías del usuario
        $categories = EmailCategory::where('user_id', $user->id)
            ->active()
            ->ordered()
            ->get();

        // Query base de emails - Solo cargar category para la lista
        $query = Email::where('user_id', $user->id)
            ->with('category')
            ->orderBy('received_at', 'desc');

        // Filtros
        // Filtro por categoría (solo IDs numéricos desde sidebar)
        if ($request->has('category') && $request->category && is_numeric($request->category)) {
            $query->where('email_category_id', $request->category);
        }

        // Filtros de estado (unread, starred, archived)
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'unread':
                    $query->unread();
                    break;
                case 'starred':
                    $query->starred();
                    break;
                case 'archived':
                    $query->archived();
                    break;
            }
        }
        
        // NUEVOS FILTROS DE BÚSQUEDA AVANZADA

        // Filtro por adjuntos
        if ($request->has('has_attachments') && $request->has_attachments == '1') {
            $query->where('has_attachments', true);
        }

        // Filtro por rango de fechas - Desde
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('received_at', '>=', $request->date_from);
        }

        // Filtro por rango de fechas - Hasta
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('received_at', '<=', $request->date_to);
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }



        // Paginación
        $emails = $query->paginate(20);

        // Estadísticas - Query optimizada con una sola consulta
        $stats = Email::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
            ')
            ->first();

        $stats->urgent = 0; // Campo no disponible en DB

        return view('emails.index', compact('emails', 'categories', 'stats', 'hasEmailAccounts', 'syncingAccount'));
    }

    /**
     * Show a single email
     */
    public function show(Email $email)
    {
        // Verificar que el email pertenece al usuario autenticado
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        // Marcar como leído si no lo está
        if (!$email->is_read) {
            $email->markAsRead();
        }

        // Cargar relaciones
        $email->load(['category', 'attachments', 'veraActions.user']);

        // Obtener categorías para el sidebar
        $categories = EmailCategory::where('user_id', Auth::id())
            ->active()
            ->ordered()
            ->get();

        // Estadísticas para el sidebar
        $stats = Email::where('user_id', Auth::id())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
            ')
            ->first();

        $stats->urgent = 0; // Campo no disponible en DB

        return view('emails.show', compact('email', 'categories', 'stats'));
    }

    /**
     * Mark email as read
     */
    public function markAsRead(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark email as unread
     */
    public function markAsUnread(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->markAsUnread();

        return response()->json(['success' => true]);
    }

    /**
     * Toggle star on email
     */
    public function toggleStar(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->toggleStar();

        return response()->json(['success' => true, 'is_starred' => $email->is_starred]);
    }

    /**
     * Archive email
     */
    public function archive(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->archive();

        return response()->json(['success' => true]);
    }

    /**
     * Unarchive email
     */
    public function unarchive(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->unarchive();

        return response()->json(['success' => true]);
    }

    /**
     * Delete email (soft delete)
     */
    public function destroy(Email $email)
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Bulk actions on emails
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:read,unread,star,unstar,archive,delete',
            'email_ids' => 'required|array',
            'email_ids.*' => 'exists:emails,id',
        ]);

        $emails = Email::whereIn('id', $request->email_ids)
            ->where('user_id', Auth::id())
            ->get();

        foreach ($emails as $email) {
            match ($request->action) {
                'read' => $email->markAsRead(),
                'unread' => $email->markAsUnread(),
                'star' => $email->update(['is_starred' => true]),
                'unstar' => $email->update(['is_starred' => false]),
                'archive' => $email->archive(),
                'delete' => $email->delete(),
            };
        }

        return response()->json(['success' => true, 'count' => $emails->count()]);
    }

    /**
     * API: Obtener un email individual para vista previa (sin reload)
     */
    public function apiShow(Email $email)
    {
        // Verificar que el email pertenece al usuario autenticado
        if ($email->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Marcar como leído si no lo está
        if (!$email->is_read) {
            $email->markAsRead();
        }

        // Cargar relaciones necesarias
        $email->load(['category', 'attachments', 'veraActions.user']);

        // Devolver JSON con todos los datos necesarios
        return response()->json([
            'success' => true,
            'email' => [
                'id' => $email->id,
                'from_name' => $email->from_name,
                'from_email' => $email->from_email,
                'to' => $email->to,
                'cc' => $email->cc,
                'bcc' => $email->bcc,
                'subject' => $email->subject,
                'body_html' => $email->body_html,
                'body_text' => $email->body_text,
                'category' => $email->category,
                'received_at' => $email->received_at,
                'is_read' => $email->is_read,
                'is_starred' => $email->is_starred,
                'is_archived' => $email->is_archived,
                'has_attachments' => $email->has_attachments,
                'attachments' => $email->attachments,
                'attachments_count' => $email->attachments_count ?? 0,
                'vera_processed' => $email->vera_processed ?? false,
                'vera_summary' => $email->vera_summary,
                'vera_tags' => $email->vera_tags ?? [],
                'vera_sentiment_score' => $email->vera_sentiment_score,
                'vera_action_required' => $email->vera_action_required,
                'vera_actions' => $email->veraActions,
                'priority' => $email->priority ?? 'normal',
            ]
        ]);
    }

    /**
     * API: Listar emails para AJAX (sin reload)
     */
    public function apiList(Request $request)
    {
        $user = Auth::user();

        // Query base - igual que en index()
        $query = Email::where('user_id', $user->id)
            ->with('category')
            ->orderBy('received_at', 'desc');

        // Filtros
        // Filtro por categoría (solo IDs numéricos desde sidebar)
        if ($request->has('category') && $request->category && is_numeric($request->category)) {
            $query->where('email_category_id', $request->category);
        }

        // Filtros de estado (unread, starred, archived)
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'unread':
                    $query->unread();
                    break;
                case 'starred':
                    $query->starred();
                    break;
                case 'archived':
                    $query->archived();
                    break;
            }
        }

        // FILTROS DE BÚSQUEDA AVANZADA

        // Filtro por adjuntos
        if ($request->has('has_attachments') && $request->has_attachments == '1') {
            $query->where('has_attachments', true);
        }

        // Filtro por rango de fechas - Desde
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('received_at', '>=', $request->date_from);
        }

        // Filtro por rango de fechas - Hasta
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('received_at', '<=', $request->date_to);
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Paginación
        $emails = $query->paginate(20);

        // Estadísticas
        $stats = Email::where('user_id', $user->id)
            ->selectRaw('
                  COUNT(*) as total,
                  SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                  SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
              ')
            ->first();

        // Transformar emails para incluir todos los campos necesarios
        $transformedEmails = $emails->map(function ($email) {
            return [
                'id' => $email->id,
                'from_name' => $email->from_name,
                'from_email' => $email->from_email,
                'subject' => $email->subject,
                'excerpt' => $email->excerpt,
                'category' => $email->category,
                'received_at' => $email->received_at,
                'is_read' => $email->is_read,
                'is_starred' => $email->is_starred,
                'has_attachments' => $email->has_attachments,
                'attachments_count' => $email->attachments_count ?? 0,
                'vera_processed' => $email->vera_processed ?? false,
                'vera_tags' => $email->vera_tags ?? [],
                'priority' => $email->priority ?? 'normal',
            ];
        });

        // Devolver JSON
        return response()->json([
            'success' => true,
            'emails' => $transformedEmails,
            'pagination' => [
                'current_page' => $emails->currentPage(),
                'last_page' => $emails->lastPage(),
                'per_page' => $emails->perPage(),
                'total' => $emails->total(),
            ],
            'stats' => [
                'total' => $stats->total ?? 0,
                'unread' => $stats->unread ?? 0,
                'starred' => $stats->starred ?? 0,
            ],
        ]);
    }
}
