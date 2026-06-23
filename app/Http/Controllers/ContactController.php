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

            $contacts = $query->paginate($request->get('per_page', 50));

            // Enriquecer cada contacto con su SEGMENTO + nº de citas (sin N+1: un map por email).
            $apptCounts = Meeting::where('user_id', $user->id)
                ->whereNotNull('contact_email')
                ->selectRaw('LOWER(contact_email) as email, COUNT(*) as total')
                ->groupBy('email')
                ->pluck('total', 'email');

            $contacts->getCollection()->transform(function ($c) use ($apptCounts) {
                $n = (int) ($apptCounts[strtolower((string) $c->email)] ?? 0);
                $c->appointments_count = $n;
                $c->segment = Contact::segmentFrom($n, $c->last_contact_at);
                return $c;
            });

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

        // Segmento automático (CRM Centro) — se calcula del nº de citas + última interacción.
        $segment = Contact::segmentFrom($appointments->count(), $contact->last_contact_at);

        // Si es petición normal del navegador, devolver vista
        return view('contacts.show', compact('contact', 'emails', 'appointments', 'segment'));
    }

    /**
     * Informes del CRM (Centro / crm_advanced): embudo de conversión, segmentación
     * automática de la cartera e informe mensual de citas.
     */
    public function reports()
    {
        $user = Auth::user();

        if (! $user->hasFeature('crm_advanced')) {
            abort(403, 'Los informes del CRM están disponibles en el plan Centro.');
        }

        // nº de citas por email de contacto (una sola query, sin N+1)
        $apptCounts = Meeting::where('user_id', $user->id)
            ->whereNotNull('contact_email')
            ->selectRaw('LOWER(contact_email) as email, COUNT(*) as total')
            ->groupBy('email')
            ->pluck('total', 'email');

        // Segmentación de toda la cartera
        $segments = ['habitual' => 0, 'cliente' => 0, 'potencial' => 0, 'dudoso' => 0, 'perdido' => 0];
        $contacts = Contact::accessibleByUser($user)->get(['email', 'last_contact_at']);
        foreach ($contacts as $c) {
            $n   = (int) ($apptCounts[strtolower((string) $c->email)] ?? 0);
            $key = Contact::segmentFrom($n, $c->last_contact_at)['key'];
            $segments[$key] = ($segments[$key] ?? 0) + 1;
        }

        // Embudo de conversión
        $totalContactos = $contacts->count();
        $agendaron      = $apptCounts->filter(fn ($n) => $n >= 1)->count();
        $repiten        = $apptCounts->filter(fn ($n) => $n >= 2)->count();
        $conversion     = $totalContactos > 0 ? round($agendaron / $totalContactos * 100, 1) : 0;

        // Informe mensual de citas (últimos 6 meses)
        $monthly = Meeting::where('user_id', $user->id)
            ->where('scheduled_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("DATE_FORMAT(scheduled_at, '%Y-%m') as mes, COUNT(*) as total, SUM(status = 'confirmed') as confirmadas, SUM(status = 'cancelled') as canceladas")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        return view('contacts.reports', compact('segments', 'totalContactos', 'agendaron', 'repiten', 'conversion', 'monthly'));
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
