<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\VeraConfig;
use App\Services\GoogleCalendarService;
use App\Services\GmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VeraCalendarController extends Controller
{
    private GoogleCalendarService $calendar;

    public function __construct()
    {
        $this->calendar = new GoogleCalendarService();
    }

    // ─── Vista principal ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config?->hasCalendarConnected()) {
            return redirect()->route('vera.dashboard')
                ->with('error', 'Conecta Google Calendar primero.');
        }

        $filter = $request->get('filter', 'week');

        $from = $filter === 'month'
            ? now()->startOfMonth()
            : now()->startOfWeek(Carbon::MONDAY);

        $to = $filter === 'month'
            ? now()->endOfMonth()
            : now()->endOfWeek(Carbon::SUNDAY);

        try {
            $events = $this->calendar->getUpcomingEvents($user, $from, $to);
        } catch (\Exception $e) {
            Log::error('Error cargando eventos Calendar', ['error' => $e->getMessage()]);
            $events = [];
        }

        // Agrupar por día
        $grouped = collect($events)->groupBy(fn ($e) => $e['start']->format('Y-m-d'));

        $todayCount = $this->calendar->getTodayEventCount($user);

        return view('vera.calendar.index', compact('grouped', 'filter', 'from', 'to', 'todayCount', 'config'));
    }

    // ─── Crear reunión manual ─────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_name'     => 'required|string|max:100',
            'contact_email'    => 'nullable|email',
            'scheduled_at'     => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'notes'            => 'nullable|string|max:500',
            'send_invite'      => 'boolean',
        ]);

        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $meeting = Meeting::create([
            'user_id'          => $user->id,
            'contact_name'     => $data['contact_name'],
            'contact_email'    => $data['contact_email'] ?? null,
            'scheduled_at'     => Carbon::parse($data['scheduled_at']),
            'duration_minutes' => $data['duration_minutes'],
            'timezone'         => 'Europe/Madrid',
            'status'           => 'confirmed',
            'source_channel'   => 'manual',
            'notes'            => $data['notes'] ?? null,
        ]);

        try {
            $this->calendar->createEvent($user, $meeting);
        } catch (\Exception $e) {
            Log::error('Error creando evento en Calendar', ['error' => $e->getMessage()]);
            return back()->with('error', 'No se pudo crear el evento en Google Calendar.');
        }

        // Enviar invitación por email si se solicitó
        if (!empty($data['send_invite']) && $data['contact_email'] && $config->hasGmailConnected()) {
            try {
                $gmail   = new GmailService();
                $start   = Carbon::parse($meeting->scheduled_at)->format('d/m/Y \a \l\a\s H:i');
                $body    = "Hola {$meeting->contact_name},\n\nTe confirmo nuestra reunión el {$start} ({$meeting->duration_minutes} min).\n\n";
                if ($meeting->google_meet_link) {
                    $body .= "Enlace Google Meet: {$meeting->google_meet_link}\n\n";
                }
                $gmail->replyToEmail($config, [
                    'from'      => $meeting->contact_email,
                    'subject'   => 'Reunión confirmada',
                    'thread_id' => null,
                ], $body, $config->vera_signature ?? 'Vera');
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar invitación email', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('vera.calendar.index')
            ->with('success', "Reunión con {$meeting->contact_name} creada correctamente.");
    }

    // ─── Editar reunión ───────────────────────────────────────────────────────

    public function update(Request $request, int $meetingId)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date',
        ]);

        $user    = Auth::user();
        $meeting = Meeting::where('user_id', $user->id)->findOrFail($meetingId);
        $newStart = Carbon::parse($data['scheduled_at']);

        try {
            $this->calendar->updateEvent($user, $meeting, $newStart);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reunión actualizada correctamente.',
        ]);
    }

    // ─── Cancelar reunión ─────────────────────────────────────────────────────

    public function destroy(Request $request, int $meetingId)
    {
        $user    = Auth::user();
        $meeting = Meeting::where('user_id', $user->id)->findOrFail($meetingId);
        $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();

        try {
            $this->calendar->cancelEvent($user, $meeting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Notificar por email al contacto si se solicita
        if ($request->boolean('notify') && $meeting->contact_email && $config->hasGmailConnected()) {
            try {
                $gmail = new GmailService();
                $start = Carbon::parse($meeting->scheduled_at)->format('d/m/Y \a \l\a\s H:i');
                $body  = "Hola {$meeting->contact_name},\n\nLamentablemente tengo que cancelar nuestra reunión del {$start}.\n\nEn breve me pondré en contacto para buscar una nueva fecha.\n\n";
                $gmail->replyToEmail($config, [
                    'from'      => $meeting->contact_email,
                    'subject'   => 'Reunión cancelada',
                    'thread_id' => null,
                ], $body, $config->vera_signature ?? 'Vera');
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar email de cancelación', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Reunión cancelada correctamente.',
        ]);
    }
}
