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

    /**
     * Responder al cliente VÍA VERA desde la Bandeja: el dueño escribe, Vera lo
     * envía por el canal original presentándolo ella ("Ya me ha pasado X la
     * respuesta: ..."), y la incidencia queda gestionada automáticamente — con lo
     * que Vera vuelve a hablar con ese contacto. Canales: telegram y whatsapp
     * (email ya tiene "Abrir en Gmail", que responde en el hilo real).
     */
    public function respondViaVera(Request $request, MeetingRequest $meetingRequest): RedirectResponse
    {
        abort_if($meetingRequest->user_id !== auth()->id(), 403);

        $data = $request->validate(
            ['reply' => ['required', 'string', 'max:2000']],
            [
                'reply.required' => 'Escribe la respuesta antes de enviar.',
                'reply.max'      => 'La respuesta no puede pasar de 2000 caracteres.',
            ]
        );

        $config = VeraConfig::where('user_id', $meetingRequest->user_id)->first();
        $owner  = $config?->owner_name ?: 'el equipo';
        $text   = "Ya me ha pasado {$owner} la respuesta:\n\n" . trim($data['reply']);

        if ($meetingRequest->channel === 'telegram' && $config?->telegram_bot_token) {
            // chat_id numérico persistido en el contacto ("@username" no vale para
            // chats privados). Fallback: telegram_id si es numérico (sin username).
            $contact = $meetingRequest->contact
                ?? \App\Models\Contact::where('user_id', $meetingRequest->user_id)
                    ->where('telegram_id', $meetingRequest->contact_identifier)->first();
            $chatId = $contact?->metadata['telegram_chat_id']
                ?? (is_numeric($contact?->telegram_id) ? $contact->telegram_id : null);

            if (!$chatId) {
                Log::warning('responder-via-vera: contacto telegram sin chat_id persistido', [
                    'meeting_request_id' => $meetingRequest->id,
                ]);
                return back()->with('error', 'Aún no tengo guardado el canal de este cliente (escribió antes de la última actualización). En cuanto vuelva a escribir, podrás responderle desde aquí.');
            }

            $sent = (new \App\Services\TelegramService())->sendMessage($config->telegram_bot_token, $chatId, $text);
        } elseif ($meetingRequest->channel === 'whatsapp' && $config?->hasWhatsAppConnected()) {
            $sent = (new \App\Services\WhatsAppService())->sendText(
                $config->whatsapp_phone_number_id,
                $config->whatsapp_access_token,
                $meetingRequest->contact_identifier,
                $text
            );
        } else {
            return back()->with('error', 'Este canal no admite respuesta directa desde la Bandeja. Para email, usa "Abrir en Gmail".');
        }

        if (!$sent) {
            return back()->with('error', 'No se pudo enviar la respuesta. Inténtalo de nuevo en un momento.');
        }

        // Enviada → la incidencia queda gestionada (y Vera se reactiva con el contacto).
        $ctx = $meetingRequest->context ?? [];
        $ctx['handled_at']  = now()->toIso8601String();
        $ctx['owner_reply'] = trim($data['reply']);
        $ctx['replied_at']  = now()->toIso8601String();
        $meetingRequest->update(['context' => $ctx]);

        \App\Models\VeraActivityLog::record([
            'user_id'              => $meetingRequest->user_id,
            'activity_type'        => 'response_sent',
            'title'                => 'Respuesta del dueño enviada vía Vera a ' . ($meetingRequest->contact?->name ?? $meetingRequest->contact_identifier),
            'description'          => mb_substr(trim($data['reply']), 0, 200),
            'metadata'             => ['channel' => $meetingRequest->channel, 'contact' => $meetingRequest->contact_identifier, 'via' => 'bandeja_reply'],
            'counts_towards_limit' => false,
            'status'               => 'completed',
        ]);

        return back()->with('success', 'Respuesta enviada al cliente vía Vera. Incidencia gestionada.');
    }
}
