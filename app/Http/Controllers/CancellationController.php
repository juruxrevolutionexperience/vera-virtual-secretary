<?php

namespace App\Http\Controllers;

use App\Models\MeetingRequest;
use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Página pública de CANCELACIÓN (espejo de BookingController). El cliente abre un
 * enlace tokenizado, ve SUS citas futuras y elige cuál cancelar — sin ambigüedad,
 * a diferencia de adivinar desde el texto. Al cancelar se borra el evento en Google
 * Calendar (sendUpdates=all → desaparece también del calendario del cliente) y la
 * cita queda 'cancelled' (deja de aparecer en el panel de Vera y de generar avisos).
 */
class CancellationController extends Controller
{
    /** Página de cancelación: lista las citas futuras del cliente. */
    public function show(string $token)
    {
        $req = MeetingRequest::where('cancel_token', $token)->first();

        if (!$req) {
            return response()->view('cancelar.invalid', ['reason' => 'notfound'], 404);
        }
        if (!$req->cancelTokenIsValid()) {
            return response()->view('cancelar.invalid', ['reason' => 'expired'], 200);
        }

        $config   = VeraConfig::where('user_id', $req->user_id)->first();
        $meetings = $this->upcomingMeetings($req);

        return view('cancelar.show', [
            'token'        => $token,
            'businessName' => $config->business_name ?? 'el negocio',
            'tz'           => $config->timezone ?: config('app.timezone', 'Europe/Madrid'),
            'meetings'     => $meetings,
        ]);
    }

    /** POST: cancela la cita elegida (validando que es del cliente y futura). */
    public function cancel(string $token, Request $request)
    {
        $req = MeetingRequest::where('cancel_token', $token)->first();
        if (!$req || !$req->cancelTokenIsValid()) {
            return response()->json(['error' => 'Este enlace ya no es válido.'], 410);
        }

        $data = $request->validate([
            'meeting_id' => ['required', 'integer'],
        ]);

        // La cita debe pertenecer a ESTE cliente (no se puede cancelar la de otro).
        $meeting = $this->upcomingMeetings($req)
            ->firstWhere('id', (int) $data['meeting_id']);

        if (!$meeting) {
            return response()->json(['error' => 'Esa cita ya no está disponible para cancelar.'], 409);
        }

        try {
            (new GoogleCalendarService())->cancelEvent($req->user, $meeting);
        } catch (\Throwable $e) {
            Log::warning('cancelar.cancel: fallo al cancelar en Calendar', [
                'meeting_id' => $meeting->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'No pudimos cancelar la cita. Inténtalo de nuevo en un momento.'], 500);
        }

        $cuando = $meeting->scheduled_at->copy()
            ->setTimezone($meeting->timezone ?: config('app.timezone', 'Europe/Madrid'))
            ->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i');

        VeraActivityLog::record([
            'user_id'              => $req->user_id,
            'activity_type'        => 'meeting_cancelled',
            'title'                => 'Cita cancelada por ' . ($req->contact?->name ?? $req->contact_identifier),
            'description'          => "El cliente canceló su cita del {$cuando} desde la página de cancelación. El hueco quedó libre.",
            'metadata'             => ['channel' => $req->channel, 'contact' => $req->contact_identifier, 'meeting_id' => $meeting->id, 'via' => 'cancel_page'],
            'counts_towards_limit' => false,
            'status'               => 'completed',
        ]);

        return response()->json(['ok' => true, 'label' => $cuando]);
    }

    /**
     * Citas confirmadas/pendientes y FUTURAS del contacto de esta solicitud.
     * La lógica de matching vive en MeetingRequest::upcomingContactMeetings()
     * (compartida con el flujo de cancelación del Job) — antes esta página solo
     * casaba por email y a los clientes de Telegram/WhatsApp les salía vacía.
     */
    private function upcomingMeetings(MeetingRequest $req)
    {
        return $req->upcomingContactMeetings()->get();
    }
}
