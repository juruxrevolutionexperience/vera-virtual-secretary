<?php

namespace App\Console\Commands;

use App\Mail\BookingFollowupMail;
use App\Models\MeetingRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Seguimiento de leads que recibieron el enlace de reserva pero NO agendaron en 24h.
 * Re-engancha al cliente con un email recordándole que puede reservar. Idempotente:
 * marca follow_up_sent_at para no repetirlo. Pensado para correr cada hora.
 */
class SendBookingFollowups extends Command
{
    protected $signature   = 'vera:send-followups';
    protected $description = 'Envía seguimiento a clientes que recibieron el enlace de reserva y no agendaron (24h)';

    public function handle(): int
    {
        $sent = 0;

        // Leads con enlace enviado hace >24h, que no han reservado (token sin usar),
        // sin seguimiento previo y aún en conversación abierta.
        $pending = MeetingRequest::query()
            ->whereNotNull('booking_link_sent_at')
            ->where('booking_link_sent_at', '<', now()->subHours(24))
            ->whereNull('booking_token_used_at')
            ->whereNull('follow_up_sent_at')
            ->whereIn('state', ['new', 'negotiating', 'awaiting'])
            ->where('channel', 'email')
            ->with('user')
            ->get();

        foreach ($pending as $req) {
            $email = $this->clientEmail($req);
            if (!$email) {
                $req->update(['follow_up_sent_at' => now()]); // sin email válido → no reintentar
                continue;
            }

            // Token válido reutilizado o nuevo (el enlace debe seguir funcionando).
            $token = $req->bookingTokenIsValid() ? $req->booking_token : $req->generateBookingToken();
            $url   = rtrim(config('app.url'), '/') . '/reservar/' . $token;

            try {
                Mail::to($email)->send(new BookingFollowupMail($req, $url));
                $req->update(['follow_up_sent_at' => now()]);
                $sent++;
                Log::info('📨 Seguimiento de reserva enviado', ['request_id' => $req->id, 'to' => $email]);
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar seguimiento de reserva', ['request_id' => $req->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Seguimientos enviados: {$sent}");
        return self::SUCCESS;
    }

    /** Extrae el email del cliente del identificador ("Nombre <email>" o email). */
    private function clientEmail(MeetingRequest $req): ?string
    {
        $id = (string) $req->contact_identifier;
        if (preg_match('/<(.+?)>/', $id, $m)) {
            $id = $m[1];
        }
        return filter_var($id, FILTER_VALIDATE_EMAIL) ? $id : null;
    }
}
