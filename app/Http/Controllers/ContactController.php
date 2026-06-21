<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Meeting;
use App\Services\ContactService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller implements HasMiddleware
{
    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * El CRM (Contactos) solo está disponible en planes con la función 'crm_basic'
     * (Pro y Centro). El Básico no tiene acceso.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (! $request->user()?->hasFeature('crm_basic')) {
                    abort(403, 'Tu plan no incluye el CRM. Actualiza a Pro o Centro para gestionar tus contactos.');
                }
                return $next($request);
            }),
        ];
    }

    /**
     * Listar contactos del usuario
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Si es petición AJAX, devolver JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            // Incluir contactos propios + contactos compartidos de la organización
            $query = Contact::accessibleByUser($user);

            // Filtros
            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('priority')) {
                $query->byPriority($request->priority);
            }

            if ($request->has('starred')) {
                $query->starred();
            }

            if ($request->has('blocked')) {
                $query->blocked();
            } else {
                $query->notBlocked();
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'last_contact_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $contacts = $query->paginate($request->get('per_page', 20));

            return response()->json($contacts);
        }

        // Si es petición normal del navegador, devolver vista
        return view('contacts.index');
    }

    /**
     * Ver contacto específico
     */
    public function show(Request $request, Contact $contact)
    {
        $this->authorize('view', $contact);

        // Si es petición AJAX, devolver JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($contact);
        }

        // Obtener todos los emails del contacto
        $emails = $contact->all_emails;

        // Historial de citas del contacto (enlazadas por id o por email) → "ficha de paciente"
        $appointments = Meeting::where('user_id', $contact->user_id)
            ->where(function ($q) use ($contact) {
                $q->where('contact_id', $contact->id);
                if ($contact->email) {
                    $q->orWhereRaw('LOWER(contact_email) = ?', [strtolower($contact->email)]);
                }
            })
            ->orderByDesc('scheduled_at')
            ->get();

        // Si es petición normal del navegador, devolver vista
        return view('contacts.show', compact('contact', 'emails', 'appointments'));
    }

    /**
     * Crear contacto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,vip',
            'is_organization_shared' => 'nullable|boolean',
        ]);

        $contact = $this->contactService->getOrCreate(
            Auth::user(),
            $validated['email'],
            $validated['name'] ?? null
        );

        if (isset($validated['company'])) $contact->company = $validated['company'];
        if (isset($validated['phone'])) $contact->phone = $validated['phone'];
        if (isset($validated['notes'])) $contact->notes = $validated['notes'];
        if (isset($validated['priority'])) $contact->priority = $validated['priority'];

        // Setear organization_id si se comparte
        if (($validated['is_organization_shared'] ?? false) && Auth::user()->organizations()->wherePivot('is_active', true)->exists()) {
            $contact->organization_id = Auth::user()->organizations()->wherePivot('is_active', true)->first()->id;
        }

        $contact->save();

        return response()->json($contact, 201);
    }

    /**
     * Actualizar contacto
     */
    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,vip',
        ]);

        $contact = $this->contactService->updateContact($contact, $validated);

        return response()->json($contact);
    }

    /**
     * Eliminar contacto
     */
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        $this->contactService->deleteContact($contact);

        return response()->json(['message' => 'Contacto eliminado correctamente']);
    }

    /**
     * Buscar contactos
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'nullable|integer|max:50',
        ]);

        $contacts = $this->contactService->search(
            Auth::user(),
            $request->q,
            $request->get('limit', 20)
        );

        return response()->json($contacts);
    }

    /**
     * Obtener contactos frecuentes
     */
    public function frequent(Request $request)
    {
        $contacts = $this->contactService->getFrequentContacts(
            Auth::user(),
            $request->get('threshold', 10),
            $request->get('limit', 20)
        );

        return response()->json($contacts);
    }

    /**
     * Obtener contactos VIP
     */
    public function vip()
    {
        $contacts = $this->contactService->getVipContacts(Auth::user());

        return response()->json($contacts);
    }

    /**
     * Obtener estadísticas
     */
    public function stats()
    {
        $stats = $this->contactService->getStats(Auth::user());

        return response()->json($stats);
    }

    /**
     * Sincronizar contactos desde emails
     */
    public function syncFromEmails()
    {
        $stats = $this->contactService->syncFromEmails(Auth::user());

        return response()->json([
            'message' => 'Contactos sincronizados correctamente',
            'stats' => $stats,
        ]);
    }

    /**
     * Marcar/desmarcar estrella
     */
    public function toggleStar(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact->toggleStar();

        return response()->json($contact->fresh());
    }

    /**
     * Bloquear contacto
     */
    public function block(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact = $this->contactService->blockContact($contact);

        return response()->json($contact);
    }

    /**
     * Desbloquear contacto
     */
    public function unblock(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact = $this->contactService->unblockContact($contact);

        return response()->json($contact);
    }

    /**
     * Marcar como VIP
     */
    public function markAsVip(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact = $this->contactService->markAsVip($contact);

        return response()->json($contact);
    }
}
