<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Meeting;
use App\Models\User;
use App\Models\VeraActivityLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Siembra datos de DEMO (para grabar el vídeo): reuniones confirmadas, actividad
 * reciente y contactos, para que el panel se vea "vivo". Todo queda etiquetado
 * (meetings.notes='__demo__', metadata.demo=true) y se borra con --clear.
 *
 *   php artisan vera:demo-seed            → siembra (user 2 por defecto)
 *   php artisan vera:demo-seed --clear    → borra todo lo de demo
 *   php artisan vera:demo-seed --user=3   → otro usuario
 */
class DemoSeed extends Command
{
    protected $signature   = 'vera:demo-seed {--user=2} {--clear}';
    protected $description = 'Siembra (o borra con --clear) datos de demo para grabar el vídeo';

    private const TAG = '__demo__';

    public function handle(): int
    {
        $user = User::find($this->option('user'));
        if (!$user) {
            $this->error('Usuario no encontrado: ' . $this->option('user'));
            return self::FAILURE;
        }

        if ($this->option('clear')) {
            return $this->clear($user);
        }

        return $this->seed($user);
    }

    private function seed(User $user): int
    {
        $tz = 'Europe/Madrid';

        // ── Reuniones confirmadas (próximas) ────────────────────────────────
        $meetings = [
            ['Laura Gómez',      'laura.gomez@gmail.com',       now($tz)->addHours(2)],
            ['Carlos Ruiz',      'carlos.ruiz@outlook.com',     now($tz)->addHours(5)],
            ['María Fernández',  'maria.fernandez@gmail.com',   now($tz)->addDay()->setTime(11, 0)],
            ['Javier Moreno',    'javier.moreno@gmail.com',     now($tz)->addDays(2)->setTime(16, 0)],
            ['Ana Torres',       'ana.torres@gmail.com',        now($tz)->addDays(3)->setTime(10, 0)],
            ['David Sánchez',    'david.sanchez@hotmail.com',   now($tz)->addDays(5)->setTime(12, 0)],
            ['Lucía Romero',     'lucia.romero@gmail.com',      now($tz)->addDays(6)->setTime(17, 0)],
        ];

        foreach ($meetings as [$name, $email, $when]) {
            Meeting::create([
                'user_id'          => $user->id,
                'contact_name'     => $name,
                'contact_email'    => $email,
                'scheduled_at'     => $when,
                'duration_minutes' => 45,
                'timezone'         => $tz,
                'status'           => 'confirmed',
                'source_channel'   => 'email',
                'notes'            => self::TAG,
            ]);
        }

        // ── Actividad reciente (variada, con fechas escalonadas) ────────────
        $acts = [
            ['meeting_confirmed', 'Reunión confirmada con Laura Gómez',        'Reunión agendada vía web de reservas.',                 now()->subMinutes(6)],
            ['slot_proposed',     'Enlace de reserva enviado a Carlos Ruiz',   'Vera envió el enlace para que el cliente elija hueco.',  now()->subMinutes(22)],
            ['response_sent',     'Respuesta enviada a María Fernández',       'Vera respondió a una consulta sobre los servicios.',    now()->subHour()],
            ['message_received',  'Email recibido de Javier Moreno',           'Nuevo mensaje entrante procesado por Vera.',            now()->subHours(2)],
            ['response_sent',     'Respuesta enviada a Ana Torres',            'Vera respondió a una consulta sobre precios.',          now()->subHours(3)],
            ['meeting_confirmed', 'Reunión confirmada con David Sánchez',      'Reunión agendada y bloqueada en el calendario.',        now()->subHours(5)],
            ['slot_proposed',     'Slots propuestos a Lucía Romero',           'Vera propuso disponibilidad al cliente.',               now()->subDay()],
        ];

        foreach ($acts as [$type, $title, $desc, $when]) {
            $log = new VeraActivityLog();
            $log->forceFill([
                'user_id'              => $user->id,
                'activity_type'        => $type,
                'title'                => $title,
                'description'          => $desc,
                'metadata'             => ['demo' => true],
                'counts_towards_limit' => false,
                'plan_type_at_time'    => $user->plan_type ?? 'beta',
                'status'               => 'completed',
            ]);
            $log->timestamps = false;
            $log->created_at = $when;
            $log->updated_at = $when;
            $log->save();
        }

        // ── Contactos ───────────────────────────────────────────────────────
        $contacts = [
            ['Laura Gómez',     'laura.gomez@gmail.com',     8],
            ['Carlos Ruiz',     'carlos.ruiz@outlook.com',   3],
            ['María Fernández', 'maria.fernandez@gmail.com', 5],
            ['Javier Moreno',   'javier.moreno@gmail.com',   2],
            ['Ana Torres',      'ana.torres@gmail.com',      4],
        ];

        foreach ($contacts as [$name, $email, $count]) {
            Contact::updateOrCreate(
                ['user_id' => $user->id, 'email' => $email],
                [
                    'name'             => $name,
                    'emails_received'  => $count,
                    'first_contact_at' => now()->subDays(rand(10, 60)),
                    'last_contact_at'  => now()->subDays(rand(0, 5)),
                    'metadata'         => ['demo' => true],
                ]
            );
        }

        $this->info('✅ Datos de demo sembrados para ' . ($user->name ?? $user->email) . ' (user ' . $user->id . ').');
        $this->line('   ' . count($meetings) . ' reuniones · ' . count($acts) . ' actividades · ' . count($contacts) . ' contactos.');
        $this->line('   Para borrarlo: php artisan vera:demo-seed --user=' . $user->id . ' --clear');
        return self::SUCCESS;
    }

    private function clear(User $user): int
    {
        $m = Meeting::where('user_id', $user->id)->where('notes', self::TAG)->delete();
        $a = VeraActivityLog::where('user_id', $user->id)->where('metadata->demo', true)->delete();
        $c = Contact::where('user_id', $user->id)->where('metadata->demo', true)->delete();

        $this->info("🧹 Demo borrado: {$m} reuniones, {$a} actividades, {$c} contactos.");
        return self::SUCCESS;
    }
}
