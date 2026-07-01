<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingRequestMail;
use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Models\VeraService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Página pública de reservas (tipo Calendly). El cliente abre un enlace
 * tokenizado de un solo uso, ve la disponibilidad real del negocio y elige
 * su hueco. Todo el código es aislado: nadie llega aquí hasta que Vera
 * genera y envía un enlace.
 */
class BookingController extends Controller
{
    /** Página de reserva. */
    public function show(string $token)
    {
        $req = MeetingRequest::where('booking_token', $token)->first();

        if (!$req || !$req->booking_token) {
            return response()->view('booking.invalid', ['reason' => 'notfound'], 404);
        }
        if ($req->booking_token_used_at) {
            return response()->view('booking.invalid', ['reason' => 'used'], 200);
        }
        if (!$req->bookingTokenIsValid()) {
            return response()->view('booking.invalid', ['reason' => 'expired'], 200);
        }

        $config   = VeraConfig::where('user_id', $req->user_id)->first();
        $services = VeraService::where('user_id', $req->user_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'duration_minutes', 'price', 'description']);

        return view('booking.show', [
            'token'        => $token,
            'businessName' => $config->business_name ?? 'el negocio',
            'duration'     => $config->meeting_duration ?? 45,
            'tzLabel'      => VeraConfig::humanTimezone($config->timezone ?? null),
            'services'     => $services,
        ]);
    }

    /** JSON: huecos libres de una fecha concreta (YYYY-MM-DD). */
    public function slots(string $token, Request $request)
    {
        $req = MeetingRequest::where('booking_token', $token)->first();
        if (!$req || !$req->bookingTokenIsValid()) {
            return response()->json(['slots' => [], 'error' => 'enlace no válido'], 410);
        }

        $date      = (string) $request->query('date', '');
        $serviceId = (int) $request->query('service_id', 0);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['slots' => []]);
        }

        // Duración: usa el servicio elegido si existe; si no, el valor por defecto del config
        $serviceDuration = null;
        if ($serviceId) {
            $svc = VeraService::where('id', $serviceId)->where('user_id', $req->user_id)->first();
            $serviceDuration = $svc?->duration_minutes;
        }

        try {
            $cacheKey = "booking-slots-{$req->user_id}-{$date}-" . ($serviceDuration ?? 'def');
            $slots = Cache::remember($cacheKey, 60, function () use ($req, $date, $serviceDuration) {
                return (new GoogleCalendarService())->getDayAvailability($req->user, $date, $serviceDuration);
            });
            return response()->json(['slots' => $slots]);
        } catch (\Throwable $e) {
            Log::warning('booking.slots error', ['error' => $e->getMessage()]);
            return response()->json(['slots' => []]);
        }
    }

    /** POST: crea la reserva del hueco elegido. */
    public function store(string $token, Request $request)
    {
        $req = MeetingRequest::where('booking_token', $token)->first();
        if (!$req || !$req->bookingTokenIsValid()) {
            return response()->json(['error' => 'Este enlace ya no es válido.'], 410);
        }

        $data = $request->validate([
            'datetime'   => ['required', 'date'],
            'note'       => ['nullable', 'string', 'max:500'],
            'service_id' => ['nullable', 'integer'],
        ]);

        $user   = $req->user;
        $config = VeraConfig::where('user_id', $user->id)->first();
        $tz     = $config->timezone ?: config('app.timezone', 'Europe/Madrid');
        $start  = Carbon::parse($data['datetime'])->setTimezone($tz);

        // Resolución de duración: servicio elegido > config global
        $serviceId       = (int) ($data['service_id'] ?? 0);
        $selectedService = $serviceId
            ? VeraService::where('id', $serviceId)->where('user_id', $user->id)->first()
            : null;
        $duration = $selectedService?->duration_minutes ?? $config->meeting_duration ?? 45;
        $end      = $start->copy()->addMinutes($duration);

        // Revalidar disponibilidad en servidor (anti doble-booking / manipulación del cliente)
        $calendar = new GoogleCalendarService();
        if (!$start->isFuture() || !$calendar->checkConflicts($user, $start, $end)) {
            return response()->json(['error' => 'Ese hueco ya no está disponible. Recarga y elige otro.'], 409);
        }

        $autoConfirm = (bool) ($config->auto_confirm_bookings ?? false);

        $meeting = Meeting::create([
            'user_id'            => $user->id,
            'contact_name'       => $req->contact?->name ?? $req->contact_identifier,
            'contact_email'      => $req->channel === 'email' ? $req->contact_identifier : null,
            'scheduled_at'       => $start,
            'duration_minutes'   => $duration,
            'timezone'           => $tz,
            'status'             => $autoConfirm ? 'confirmed' : 'pending',
            'source_channel'     => $req->channel,
            'meeting_request_id' => $req->id,
            'service_name'       => $selectedService?->name,
            'service_duration'   => $selectedService?->duration_minutes,
        ]);

        // Bloquea el hueco en el Calendar del negocio (evita que otro lo coja).
        // OJO: createEvent fuerza status='confirmed' (lo usa el flujo core de Vera).
        // Si el negocio requiere confirmación manual, lo devolvemos a 'pending':
        // el evento queda en Calendar reservando el hueco, pero la cita está
        // pendiente de que el dueño la confirme o rechace desde el panel.
        try {
            $calendar->createEvent($user, $meeting);
            if (!$autoConfirm) {
                $meeting->update(['status' => 'pending']);
            }
        } catch (\Throwable $e) {
            Log::warning('booking.store createEvent falló', ['error' => $e->getMessage()]);
        }

        $req->update([
            'state'                 => $autoConfirm ? 'confirmed' : 'awaiting',
            'meeting_id'            => $meeting->id,
            'booking_token_used_at' => now(),
        ]);

        if (!empty($data['note'])) {
            $ctx = $req->context ?? [];
            $ctx['booking_note'] = $data['note'];
            $req->update(['context' => $ctx]);
        }

        VeraActivityLog::record([
            'user_id'              => $user->id,
            'activity_type'        => 'meeting_confirmed',
            'title'                => ($autoConfirm ? 'Reunión confirmada con ' : 'Reserva solicitada por ') . ($req->contact?->name ?? $req->contact_identifier),
            'description'          => 'Vía web de reservas — ' . $start->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i'),
            'metadata'             => ['channel' => $req->channel, 'contact' => $req->contact_identifier, 'via' => 'booking_page'],
            'counts_towards_limit' => true,
            'status'               => 'completed',
        ]);

        // Invalidar el caché del día para que cualquier siguiente consulta vea
        // el hueco ya ocupado (en vez de esperar al TTL de 60s).
        Cache::forget("booking-slots-{$user->id}-{$start->format('Y-m-d')}");

        $dateLabel = $start->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i')
            . ' (' . VeraConfig::humanTimezone($tz) . ')';

        // Email al dueño del negocio
        try {
            Mail::to($user->email)->queue(new BookingRequestMail($user, $meeting, $req, $dateLabel));
        } catch (\Throwable $e) {
            Log::warning('booking: email al dueño falló', ['error' => $e->getMessage()]);
        }

        // Email al cliente (solo si tenemos su email)
        $clientEmail = $req->channel === 'email' ? $req->contact_identifier : ($meeting->contact_email ?? null);
        if ($clientEmail && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($clientEmail)->queue(new BookingConfirmedMail($meeting, $req, $dateLabel, $autoConfirm));
            } catch (\Throwable $e) {
                Log::warning('booking: email al cliente falló', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ok'     => true,
            'status' => $meeting->status,
            'label'  => $start->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i'),
        ]);
    }
}
