<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $config = VeraConfig::where('user_id', $req->user_id)->first();

        return view('booking.show', [
            'token'        => $token,
            'businessName' => $config->business_name ?? 'el negocio',
            'duration'     => $config->meeting_duration ?? 45,
        ]);
    }

    /** JSON: huecos libres de una fecha concreta (YYYY-MM-DD). */
    public function slots(string $token, Request $request)
    {
        $req = MeetingRequest::where('booking_token', $token)->first();
        if (!$req || !$req->bookingTokenIsValid()) {
            return response()->json(['slots' => [], 'error' => 'enlace no válido'], 410);
        }

        $date = (string) $request->query('date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['slots' => []]);
        }

        try {
            $slots = (new GoogleCalendarService())->getAvailableSlots(
                $req->user, daysAhead: 1, maxSlots: 40, preferredDate: $date
            );
            $out = array_map(fn ($s) => [
                'time' => $s['start']->format('H:i'),
                'iso'  => $s['start']->toIso8601String(),
            ], $slots);
            return response()->json(['slots' => $out]);
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
            'datetime' => ['required', 'date'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        $user   = $req->user;
        $config = VeraConfig::where('user_id', $user->id)->first();
        $tz     = ($user->timezone && $user->timezone !== 'UTC') ? $user->timezone : config('app.timezone', 'Europe/Madrid');
        $start  = Carbon::parse($data['datetime'])->setTimezone($tz);
        $end    = $start->copy()->addMinutes($config->meeting_duration ?? 45);

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
            'duration_minutes'   => $config->meeting_duration ?? 45,
            'timezone'           => $tz,
            'status'             => $autoConfirm ? 'confirmed' : 'pending',
            'source_channel'     => $req->channel,
            'meeting_request_id' => $req->id,
        ]);

        // Bloquea el hueco en el Calendar del negocio (evita que otro lo coja).
        try {
            $calendar->createEvent($user, $meeting);
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

        return response()->json([
            'ok'     => true,
            'status' => $meeting->status,
            'label'  => $start->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i'),
        ]);
    }
}
