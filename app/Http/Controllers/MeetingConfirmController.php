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
     * Cierra la incidencia y devuelve la conversación a Vera. El cliente recibe
     * un mensaje HARDCODEADO (cero LLM, cero delirio) que le explica que su
     * consulta quedó atendida, que vuelve a hablar con Vera (IA) y que puede
     * volver a pedir hablar con el dueño cuando quiera.
     */
    public function resolveEscalation(Request $request, MeetingRequest $meetingRequest): RedirectResponse
    {
        abort_if($meetingRequest->user_id !== auth()->id(), 403);

        $config   = VeraConfig::where('user_id', $meetingRequest->user_id)->first();
        $negocio  = $config?->business_name ?: 'nuestro negocio';
        $owner    = $config?->owner_name ?: 'el equipo';
        $closeMsg = "¡Me alegro de que hayamos podido ayudarte! A partir de ahora vuelves a hablar conmigo, Vera, la asistente virtual de {$negocio}. Lo que necesites me lo dices, y si quieres volver a hablar con {$owner}, solo tienes que pedírmelo.";

        // Aviso de reactivación solo en canales de mensajería (en email la
        // conversación vive en el hilo de Gmail). Best effort: si no se puede
        // enviar, la incidencia se cierra igualmente.
        if (in_array($meetingRequest->channel, ['telegram', 'whatsapp'])) {
            $sent = $this->sendViaClientChannel($meetingRequest, $config, $closeMsg);
            if ($sent) {
                $meetingRequest->conversation?->addMessage('assistant', $closeMsg);
            }
        }

        $ctx = $meetingRequest->context ?? [];
        $ctx['handled_at'] = now()->toIso8601String();
        $meetingRequest->update(['context' => $ctx]);

        return back()->with('success', 'Incidencia cerrada. Vera vuelve a atender a este cliente.');
    }

    /**
     * Responder al cliente VÍA VERA desde la Bandeja: el dueño escribe, Vera lo
     * envía por el canal original presentándolo ella ("Ya me ha pasado X la
     * respuesta: ..."). La incidencia NO se cierra: la conversación sigue abierta
     * (con Vera en silencio) para el ida y vuelta que haga falta, hasta que el
     * dueño pulse "Cerrar y devolver a Vera". Canales: telegram y whatsapp
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

        if (!in_array($meetingRequest->channel, ['telegram', 'whatsapp'])) {
            return back()->with('error', 'Este canal no admite respuesta directa desde la Bandeja. Para email, usa "Abrir en Gmail".');
        }

        $config = VeraConfig::where('user_id', $meetingRequest->user_id)->first();
        $owner  = $config?->owner_name ?: 'el equipo';
        $text   = "Ya me ha pasado {$owner} la respuesta:\n\n" . trim($data['reply']);

        $sent = $this->sendViaClientChannel($meetingRequest, $config, $text);
        if ($sent === null) {
            return back()->with('error', 'Aún no tengo guardado el canal de este cliente (escribió antes de la última actualización). En cuanto vuelva a escribir, podrás responderle desde aquí.');
        }
        if (!$sent) {
            return back()->with('error', 'No se pudo enviar la respuesta. Inténtalo de nuevo en un momento.');
        }

        // Guardar en el hilo (visible en la Bandeja y en el historial que verá
        // Vera cuando se reactive) y marcar que el dueño ya contestó lo último.
        $meetingRequest->conversation?->addMessage('assistant', $text);

        $ctx = $meetingRequest->context ?? [];
        $ctx['owner_replied_at'] = now()->toIso8601String();
        unset($ctx['last_client_reply_at']);
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

        return back()->with('success', 'Respuesta enviada. La conversación sigue abierta: cuando esté resuelta, ciérrala para devolvérsela a Vera.');
    }

    /**
     * Envía un texto al cliente por su canal de mensajería (telegram/whatsapp).
     * Devuelve true/false según el envío, o null si el contacto de Telegram aún
     * no tiene chat_id persistido (escribió antes de que se guardara).
     */
    private function sendViaClientChannel(MeetingRequest $meetingRequest, ?VeraConfig $config, string $text): ?bool
    {
        if ($meetingRequest->channel === 'telegram' && $config?->telegram_bot_token) {
            // chat_id numérico persistido en el contacto ("@username" no vale para
            // chats privados). Fallback: telegram_id si es numérico (sin username).
            $contact = $meetingRequest->contact
                ?? \App\Models\Contact::where('user_id', $meetingRequest->user_id)
                    ->where('telegram_id', $meetingRequest->contact_identifier)->first();
            $chatId = $contact?->metadata['telegram_chat_id']
                ?? (is_numeric($contact?->telegram_id) ? $contact->telegram_id : null);

            if (!$chatId) {
                Log::warning('bandeja: contacto telegram sin chat_id persistido', [
                    'meeting_request_id' => $meetingRequest->id,
                ]);
                return null;
            }

            return (new \App\Services\TelegramService())->sendMessage($config->telegram_bot_token, $chatId, $text);
        }

        if ($meetingRequest->channel === 'whatsapp' && $config?->hasWhatsAppConnected()) {
            return (new \App\Services\WhatsAppService())->sendText(
                $config->whatsapp_phone_number_id,
                $config->whatsapp_access_token,
                $meetingRequest->contact_identifier,
                $text
            );
        }

        return false;
    }
}
