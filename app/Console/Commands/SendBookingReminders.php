<?php

namespace App\Console\Commands;

use App\Mail\BookingReminderMail;
use App\Models\Meeting;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía recordatorios de cita a los clientes.
 *
 * Lógica:
 *   - Cada usuario configura cuántas horas antes quiere los dos avisos
 *     (reminder_1_hours, por defecto 24; reminder_2_hours, por defecto 1).
 *   - Si la cita está a menos de reminder_1_hours de distancia cuando se agenda,
 *     el primer aviso simplemente nunca entra en ventana: sólo llega el segundo.
 *   - Idempotente: cada aviso se marca en reminder_1_sent_at / reminder_2_sent_at.
 *   - Pensado para correr cada 15 min desde el scheduler.
 */
class SendBookingReminders extends Command
{
    protected $signature   = 'vera:send-reminders';
    protected $description = 'Envía recordatorios de cita a los clientes según la configuración de cada usuario';

    // Ventana de holgura (minutos) para no perder una cita entre dos runs del scheduler
    private const WINDOW_SLACK_MIN = 15;

    public function handle(): int
    {
        $now  = now();
        $sent = 0;

        $users = User::with('veraConfig')->whereHas('veraConfig')->get();

        foreach ($users as $user) {
            $config  = $user->veraConfig;
            $hours1  = max(2, (int) ($config->reminder_1_hours ?? 24));  // mínimo 2h para tener sentido
            $hours2  = max(0, (int) ($config->reminder_2_hours ?? 1));

            $sent += $this->sendForWindow(
                $user->id,
                $config,
                $now,
                $hours1 * 60,
                'reminder_1_sent_at',
                'early',
            );

            if ($hours2 > 0) {
                $sent += $this->sendForWindow(
                    $user->id,
                    $config,
                    $now,
                    $hours2 * 60,
                    'reminder_2_sent_at',
                    'near',
                );
            }
        }

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }

    private function sendForWindow(
        int    $userId,
        object $config,
        Carbon $now,
        int    $minutesBefore,
        string $sentColumn,
        string $kind,
    ): int {
        $slack = self::WINDOW_SLACK_MIN;
        $open  = $now->copy()->addMinutes($minutesBefore - $slack);
        $close = $now->copy()->addMinutes($minutesBefore + $slack);

        $meetings = Meeting::where('user_id', $userId)
            ->where('status', 'confirmed')
            ->whereNull($sentColumn)
            ->whereBetween('scheduled_at', [$open, $close])
            ->get();

        $sent = 0;
        foreach ($meetings as $meeting) {
            if ($this->sendReminder($meeting, $kind, $config, $minutesBefore)) {
                $meeting->update([$sentColumn => now()]);
                $sent++;
            }
        }

        return $sent;
    }

    private function sendReminder(Meeting $meeting, string $kind, object $config, int $minutesBefore): bool
    {
        $channel = $meeting->source_channel ?? 'email';

        if ($channel === 'telegram') {
            return $this->sendTelegramReminder($meeting, $kind, $config, $minutesBefore);
        }

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
            Log::info('Recordatorio enviado por email', [
                'meeting_id' => $meeting->id,
                'kind'       => $kind,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Error enviando recordatorio por email', [
                'meeting_id' => $meeting->id,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendTelegramReminder(Meeting $meeting, string $kind, object $config, int $minutesBefore): bool
    {
        $contact  = $meeting->contact;
        // chat_id NUMÉRICO persistido en metadata (el "@username" de telegram_id NO
        // sirve para chats privados — la API lo rechaza). Fallback: telegram_id solo
        // si es numérico (contactos sin username, donde el identificador ES el chat_id).
        $chatId   = $contact?->metadata['telegram_chat_id']
            ?? (is_numeric($contact?->telegram_id) ? $contact->telegram_id : null);
        $botToken = $config->telegram_bot_token ?? null;

        if (!$chatId || !$botToken) {
            Log::warning('Recordatorio Telegram: faltan datos', [
                'meeting_id'  => $meeting->id,
                'has_chat_id' => (bool) $chatId,
                'has_token'   => (bool) $botToken,
            ]);
            return false;
        }

        $tz       = $config->timezone ?? 'Europe/Madrid';
        $date     = Carbon::parse($meeting->scheduled_at)->setTimezone($tz)->format('d/m/Y \a \l\a\s H:i');
        $business = $config->business_name ?? 'tu cita';
        $label    = $this->humanLabel($minutesBefore);

        $text = "Recordatorio: tienes cita con *{$business}* el *{$date}* (en {$label}).";

        $ok = (new TelegramService())->sendMessage($botToken, $chatId, $text);

        Log::info('Recordatorio enviado por Telegram', [
            'meeting_id' => $meeting->id,
            'kind'       => $kind,
            'ok'         => $ok,
        ]);

        return $ok;
    }

    private function humanLabel(int $minutesBefore): string
    {
        if ($minutesBefore >= 60 && $minutesBefore % 60 === 0) {
            $h = $minutesBefore / 60;
            return $h === 1 ? '1 hora' : "{$h} horas";
        }
        if ($minutesBefore >= 60) {
            $h = intdiv($minutesBefore, 60);
            $m = $minutesBefore % 60;
            return "{$h}h {$m}min";
        }
        return "{$minutesBefore} minutos";
    }
}
