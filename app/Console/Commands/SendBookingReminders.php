<?php

namespace App\Console\Commands;

use App\Mail\BookingReminderMail;
use App\Models\Meeting;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía recordatorios de cita a los clientes: ~24h antes y ~1h antes.
 * Pensado para correr cada ~15 min desde el scheduler. Idempotente: cada
 * recordatorio se marca al enviarse (reminder_24h_sent_at / reminder_1h_sent_at)
 * para no repetirlo.
 */
class SendBookingReminders extends Command
{
    protected $signature   = 'vera:send-reminders';
    protected $description = 'Envía recordatorios de cita (24h y 1h antes) a los clientes';

    public function handle(): int
    {
        $now  = now();
        $sent = 0;

        // ── Recordatorio 24h antes ──────────────────────────────────────────
        // Ventana de 1h (23h-24h para la cita) → con runs cada 15 min se captura
        // sobradamente, y el flag evita duplicados.
        $meetings24h = Meeting::where('status', 'confirmed')
            ->whereNull('reminder_24h_sent_at')
            ->whereBetween('scheduled_at', [$now->copy()->addHours(23), $now->copy()->addHours(24)])
            ->get();

        foreach ($meetings24h as $meeting) {
            if ($this->sendReminder($meeting, '24h')) {
                $meeting->update(['reminder_24h_sent_at' => now()]);
                $sent++;
            }
        }

        // ── Recordatorio 1h antes ───────────────────────────────────────────
        $meetings1h = Meeting::where('status', 'confirmed')
            ->whereNull('reminder_1h_sent_at')
            ->whereBetween('scheduled_at', [$now->copy()->addMinutes(45), $now->copy()->addMinutes(75)])
            ->get();

        foreach ($meetings1h as $meeting) {
            if ($this->sendReminder($meeting, '1h')) {
                $meeting->update(['reminder_1h_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }

    private function sendReminder(Meeting $meeting, string $kind): bool
    {
        $channel = $meeting->source_channel ?? 'email';

        if ($channel === 'telegram') {
            return $this->sendTelegramReminder($meeting, $kind);
        }

        // email (y whatsapp como fallback hasta que esté activo)
        return $this->sendEmailReminder($meeting, $kind);
    }

    private function sendEmailReminder(Meeting $meeting, string $kind): bool
    {
        $email = $meeting->contact_email;
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::to($email)->send(new BookingReminderMail($meeting, $kind));
            Log::info('Recordatorio de cita enviado por email', [
                'meeting_id' => $meeting->id,
                'kind'       => $kind,
                'to'         => $email,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar recordatorio por email', [
                'meeting_id' => $meeting->id,
                'kind'       => $kind,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendTelegramReminder(Meeting $meeting, string $kind): bool
    {
        $contact  = $meeting->contact;
        $chatId   = $contact?->telegram_id;
        $config   = $meeting->user?->veraConfig;
        $botToken = $config?->telegram_bot_token;

        if (!$chatId || !$botToken) {
            Log::warning('Recordatorio Telegram: faltan datos', [
                'meeting_id' => $meeting->id,
                'has_chat_id'  => (bool) $chatId,
                'has_token'    => (bool) $botToken,
            ]);
            return false;
        }

        $label    = $kind === '24h' ? '24 horas' : '1 hora';
        $date     = \Carbon\Carbon::parse($meeting->scheduled_at)
                        ->setTimezone($config->timezone ?? 'Europe/Madrid')
                        ->format('d/m/Y \a \l\a\s H:i');
        $business = $config->business_name ?? 'tu cita';

        $text = "Recordatorio: tienes cita con *{$business}* en *{$date}* (en {$label}).";

        $ok = (new TelegramService())->sendMessage($botToken, $chatId, $text);

        Log::info('Recordatorio de cita enviado por Telegram', [
            'meeting_id' => $meeting->id,
            'kind'       => $kind,
            'chat_id'    => $chatId,
            'ok'         => $ok,
        ]);

        return $ok;
    }
}
