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
            ->where('status', 'received') // Solo emails recibidos en inbox
            ->with('category')
            ->orderBy('received_at', 'desc');

        // Filtros
        // Filtro por categoría (solo IDs numéricos desde sidebar)
        if ($request->has('category') && $request->category && is_numeric($request->category)) {
            $query->where('email_category_id', $request->category);
        }

        // Filtro por carpeta
        if ($request->has('folder') && $request->folder && is_numeric($request->folder)) {
            $query->whereHas('folders', function ($q) use ($request) {
                $q->where('email_folders.id', $request->folder);
            });
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

        // Búsqueda                                                                                                                        
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // ============================================                                                                                    
        // NUEVOS FILTROS DE BÚSQUEDA AVANZADA                                                                                             
        // ============================================                                                                                    

        // Filtro por campo específico de búsqueda                                                                                         
        if ($request->has('search') && $request->search && $request->has('search_field')) {
            // Eliminar el scope genérico anterior                                                                                         
            $query->getQuery()->wheres = array_filter($query->getQuery()->wheres, function ($where) {
                return !isset($where['query']) || strpos($where['query'], 'subject') === false;
            });

            // Aplicar búsqueda específica                                                                                                 
            switch ($request->search_field) {
                case 'subject':
                    $query->bySubject($request->search);
                    break;
                case 'from':
                    $query->byFrom($request->search);
                    break;
                case 'content':
                    $query->byContent($request->search);
                    break;
                default:
                    // Si no se especifica, usar búsqueda general (ya aplicada arriba)                                                     
                    break;
            }
        }

        // Filtros de fecha                                                                                                                
        if ($request->has('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->today();
                    break;
                case 'this_week':
                    $query->thisWeek();
                    break;
                case 'this_month':
                    $query->thisMonth();
                    break;
            }
        }

        // Rango de fechas personalizado                                                                                                   
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Filtro de emails con adjuntos                                                                                                   
        if ($request->has('has_attachments') && $request->has_attachments == '1') {
            $query->withAttachments();
        }

        // Paginación                                                                                                                      
        $emails = $query->paginate(20);

        // Estadísticas - Query optimizada con una sola consulta (solo emails recibidos)
        $statsObj = Email::where('user_id', $user->id)
            ->where('status', 'received') // Solo contar emails recibidos
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
            ')
            ->first();

        // Convertir a array para compatibilidad con sidebar
        $stats = [
            'total' => $statsObj->total ?? 0,
            'unread' => $statsObj->unread ?? 0,
            'starred' => $statsObj->starred ?? 0,
            'urgent' => 0, // Campo no disponible en DB
        ];

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
            ->where('status', 'received') // Solo emails recibidos en inbox
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


        // Búsqueda                                                                                                                        
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // ============================================                                                                                    
        // NUEVOS FILTROS DE BÚSQUEDA AVANZADA (AJAX)                                                                                      
        // ============================================                                                                                    

        // Filtro por campo específico de búsqueda                                                                                         
        if ($request->has('search') && $request->search && $request->has('search_field')) {
            // Aplicar búsqueda específica según el campo                                                                                  
            $query = Email::where('user_id', $user->id)
                ->where('status', 'received')
                ->with('category')
                ->orderBy('received_at', 'desc');

            // Reaplicar filtros anteriores                                                                                                
            if ($request->has('category') && $request->category && is_numeric($request->category)) {
                $query->where('email_category_id', $request->category);
            }

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

            // Aplicar búsqueda específica                                                                                                 
            switch ($request->search_field) {
                case 'subject':
                    $query->bySubject($request->search);
                    break;
                case 'from':
                    $query->byFrom($request->search);
                    break;
                case 'content':
                    $query->byContent($request->search);
                    break;
                default:
                    $query->search($request->search);
                    break;
            }
        }

        // Filtros de fecha                                                                                                                
        if ($request->has('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->today();
                    break;
                case 'this_week':
                    $query->thisWeek();
                    break;
                case 'this_month':
                    $query->thisMonth();
                    break;
            }
        }

        // Rango de fechas personalizado                                                                                                   
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Filtro de emails con adjuntos                                                                                                   
        if ($request->has('has_attachments') && $request->has_attachments == '1') {
            $query->withAttachments();
        }

        // Filtro por carpeta (moverlo aquí si no está)                                                                                    
        if ($request->has('folder') && $request->folder && is_numeric($request->folder)) {
            $query->whereHas('folders', function ($q) use ($request) {
                $q->where('email_folders.id', $request->folder);
            });
        }

        // Paginación                                                                                                                      
        $emails = $query->paginate(20);

        // Estadísticas (solo emails recibidos)
        $stats = Email::where('user_id', $user->id)
            ->where('status', 'received') // Solo contar emails recibidos
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
                'workflow_status' => $email->workflow_status ?? 'unread',
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

    /**
     * Vista de Papelera (emails eliminados)
     */
    public function trash(Request $request)
    {
        $user = Auth::user();

        // Verificar si tiene cuentas
        $hasEmailAccounts = EmailAccount::where('user_id', $user->id)->exists();

        // Obtener categorías
        $categories = EmailCategory::where('user_id', $user->id)
            ->active()
            ->ordered()
            ->get();

        // Query de emails eliminados
        $query = Email::where('user_id', $user->id)
            ->where('is_deleted', true)
            ->with('category')
            ->orderBy('deleted_at_custom', 'desc');

        // Filtro por carpeta
        if ($request->has('folder') && $request->folder && is_numeric($request->folder)) {
            $query->whereHas('folders', function ($q) use ($request) {
                $q->where('email_folders.id', $request->folder);
            });
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Paginación
        $emails = $query->paginate(50);

        // Estadísticas (como array para compatibilidad con sidebar)
        $stats = [
            'total' => Email::where('user_id', $user->id)->where('is_deleted', true)->count(),
            'unread' => 0,
            'starred' => 0,
            'urgent' => 0,
            'can_restore' => $emails->count(),
        ];

        return view('emails.trash', compact(
            'emails',
            'categories',
            'hasEmailAccounts',
            'stats'
        ));
    }

    /**
     * Vista de Borradores
     */
    public function drafts(Request $request)
    {
        $user = Auth::user();

        // Verificar si tiene cuentas
        $hasEmailAccounts = EmailAccount::where('user_id', $user->id)->exists();

        // Obtener categorías
        $categories = EmailCategory::where('user_id', $user->id)
            ->active()
            ->ordered()
            ->get();

        // Query de borradores
        $query = Email::where('user_id', $user->id)
            ->where('is_draft', true)
            ->where('is_deleted', false)
            ->with('category')
            ->orderBy('updated_at', 'desc');

        // Filtro por carpeta
        if ($request->has('folder') && $request->folder && is_numeric($request->folder)) {
            $query->whereHas('folders', function ($q) use ($request) {
                $q->where('email_folders.id', $request->folder);
            });
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Paginación
        $emails = $query->paginate(50);

        // Estadísticas (como array para compatibilidad con sidebar)
        $stats = [
            'total' => Email::where('user_id', $user->id)
                ->where('is_draft', true)
                ->where('is_deleted', false)
                ->count(),
            'unread' => 0,
            'starred' => 0,
            'urgent' => 0,
        ];

        return view('emails.drafts', compact(
            'emails',
            'categories',
            'hasEmailAccounts',
            'stats'
        ));
    }

    /**                                                                                                                                        
     * Update workflow status of an email                                                                                                      
     */
    public function updateWorkflowStatus(Request $request, Email $email)
    {
        // Verificar que el email pertenece al usuario autenticado                                                                             
        if ($email->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Validar el nuevo estado                                                                                                             
        $request->validate([
            'status' =>
                'required|in:unread,pending_validation,pending_response,in_progress,in_review,approved,completed,archived,no_action_needed'
        ]);

        try {
            // Cambiar el estado usando el servicio                                                                                            
            $workflowService = app(\App\Services\EmailWorkflowService::class);
            $updatedEmail = $workflowService->changeWorkflowStatus($email, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'workflow_status' => $updatedEmail->workflow_status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Command Palette: Búsqueda rápida de emails en tiempo real
     */
    public function commandPaletteSearch(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'emails' => []
            ]);
        }

        // Buscar emails (máximo 5 resultados)
        $emails = Email::where('user_id', $user->id)
            ->where('status', 'received')
            ->where('is_deleted', false)
            ->where('is_draft', false)
            ->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_name', 'like', "%{$search}%")
                  ->orWhere('from_email', 'like', "%{$search}%")
                  ->orWhere('body_text', 'like', "%{$search}%");
            })
            ->orderBy('received_at', 'desc')
            ->limit(5)
            ->get(['id', 'subject', 'from_name', 'from_email', 'received_at', 'is_read']);

        return response()->json([
            'success' => true,
            'emails' => $emails->map(function($email) {
                return [
                    'id' => $email->id,
                    'subject' => $email->subject,
                    'from' => $email->from_name ?: $email->from_email,
                    'received_at' => $email->received_at->diffForHumans(),
                    'is_read' => $email->is_read,
                ];
            })
        ]);
    }

    /**
     * Command Palette: Obtener contadores para filtros
     */
    public function commandPaletteStats(Request $request)
    {
        $user = Auth::user();

        $baseQuery = Email::where('user_id', $user->id)
            ->where('status', 'received')
            ->where('is_deleted', false)
            ->where('is_draft', false);

        $stats = [
            'today' => (clone $baseQuery)->today()->count(),
            'this_week' => (clone $baseQuery)->thisWeek()->count(),
            'this_month' => (clone $baseQuery)->thisMonth()->count(),
            'with_attachments' => (clone $baseQuery)->withAttachments()->count(),
            'unread' => (clone $baseQuery)->unread()->count(),
            'starred' => (clone $baseQuery)->starred()->count(),
            'total' => (clone $baseQuery)->count(),
            'drafts' => Email::where('user_id', $user->id)
                ->where('is_draft', true)
                ->where('is_deleted', false)
                ->count(),
            'trash' => Email::where('user_id', $user->id)
                ->where('is_deleted', true)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Command Palette: Aplicar filtros via AJAX (sin recargar página)
     */
    public function commandPaletteFilter(Request $request)
    {
        $user = Auth::user();

        // Query base
        $query = Email::where('user_id', $user->id)
            ->where('status', 'received')
            ->where('is_deleted', false)
            ->where('is_draft', false)
            ->with('category')
            ->orderBy('received_at', 'desc');

        // Aplicar filtros dinámicamente
        if ($request->has('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->today();
                    break;
                case 'this_week':
                    $query->thisWeek();
                    break;
                case 'this_month':
                    $query->thisMonth();
                    break;
            }
        }

        if ($request->has('has_attachments') && $request->has_attachments == '1') {
            $query->withAttachments();
        }

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

        if ($request->has('search') && $request->search) {
            if ($request->has('search_field')) {
                switch ($request->search_field) {
                    case 'subject':
                        $query->bySubject($request->search);
                        break;
                    case 'from':
                        $query->byFrom($request->search);
                        break;
                    case 'content':
                        $query->byContent($request->search);
                        break;
                    default:
                        $query->search($request->search);
                        break;
                }
            } else {
                $query->search($request->search);
            }
        }

        // Paginación
        $emails = $query->paginate(20);

        // Transformar emails
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
                'workflow_status' => $email->workflow_status ?? 'unread',
            ];
        });

        return response()->json([
            'success' => true,
            'emails' => $transformedEmails,
            'pagination' => [
                'current_page' => $emails->currentPage(),
                'last_page' => $emails->lastPage(),
                'per_page' => $emails->perPage(),
                'total' => $emails->total(),
            ]
        ]);
    }
}
