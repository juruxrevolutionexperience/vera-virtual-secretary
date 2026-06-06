<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingRejectedMail;
use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\VeraConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MeetingConfirmController extends Controller
{
    public function confirm(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_if($meeting->user_id !== auth()->id(), 403);
        abort_if($meeting->status !== 'pending', 422);

        $meeting->update(['status' => 'confirmed']);

        $req = $meeting->meetingRequest;
        if ($req) {
            $req->update(['state' => 'confirmed']);
        }

        $dateLabel = $meeting->scheduled_at->copy()
            ->setTimezone($meeting->timezone)
            ->locale('es')
            ->translatedFormat('l d \d\e F \a \l\a\s H:i')
            . ' (' . VeraConfig::humanTimezone($meeting->timezone) . ')';

        $clientEmail = $meeting->contact_email
            ?? ($req?->channel === 'email' ? $req->contact_identifier : null);

        if ($clientEmail && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($clientEmail)->queue(
                    new BookingConfirmedMail($meeting, $req, $dateLabel, autoConfirmed: true)
                );
            } catch (\Throwable $e) {
                Log::warning('meeting.confirm: email al cliente falló', ['error' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Cita confirmada. El cliente ha sido notificado.');
    }

    public function reject(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_if($meeting->user_id !== auth()->id(), 403);
        abort_if($meeting->status !== 'pending', 422);

        // Libera el hueco en Google Calendar (borra el evento) y marca cancelada.
        // Si no hay evento o falla, cancelEvent igualmente deja la cita cancelada.
        try {
            (new \App\Services\GoogleCalendarService())->cancelEvent($meeting->user, $meeting);
        } catch (\Throwable $e) {
            Log::warning('meeting.reject: cancelEvent falló', ['error' => $e->getMessage()]);
            $meeting->update(['status' => 'cancelled']);
        }

        $req = $meeting->meetingRequest;
        if ($req) {
            $req->update(['state' => 'cancelled']);
        }

        $dateLabel = $meeting->scheduled_at->copy()
            ->setTimezone($meeting->timezone)
            ->locale('es')
            ->translatedFormat('l d \d\e F \a \l\a\s H:i')
            . ' (' . VeraConfig::humanTimezone($meeting->timezone) . ')';

        $clientEmail = $meeting->contact_email
            ?? ($req?->channel === 'email' ? $req->contact_identifier : null);

        if ($clientEmail && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($clientEmail)->queue(
                    new BookingRejectedMail($meeting, $req, $dateLabel)
                );
            } catch (\Throwable $e) {
                Log::warning('meeting.reject: email al cliente falló', ['error' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Reserva rechazada. El cliente ha sido notificado.');
    }

    /**
     * Marca una incidencia/queja escalada como gestionada por el dueño.
     * No cambia el estado del request (sigue 'escalated' como histórico), solo
     * añade context->handled_at para que salga del listado de "Requiere tu atención".
     */
    public function resolveEscalation(Request $request, MeetingRequest $meetingRequest): RedirectResponse
    {
        abort_if($meetingRequest->user_id !== auth()->id(), 403);

        $ctx = $meetingRequest->context ?? [];
        $ctx['handled_at'] = now()->toIso8601String();
        $meetingRequest->update(['context' => $ctx]);

        return back()->with('success', 'Incidencia marcada como gestionada.');
    }
}
