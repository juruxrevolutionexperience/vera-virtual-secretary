<?php

namespace App\Console\Commands;

use App\Models\MeetingRequest;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Genera un enlace de reserva de prueba para un usuario (dev/testing).
 * Uso: php artisan vera:booking-link 2
 */
class MakeBookingLink extends Command
{
    protected $signature   = 'vera:booking-link {user_id} {--contact=test@cliente.com}';
    protected $description = 'Genera un enlace de reserva de prueba para un usuario';

    public function handle(): int
    {
        $user = User::find($this->argument('user_id'));
        if (!$user) {
            $this->error('Usuario no encontrado: ' . $this->argument('user_id'));
            return self::FAILURE;
        }

        $mr = MeetingRequest::create([
            'user_id'            => $user->id,
            'channel'            => 'email',
            'contact_identifier' => $this->option('contact'),
            'state'              => 'negotiating',
        ]);

        $token = $mr->generateBookingToken();
        $base  = rtrim(config('app.url'), '/');

        $this->info('Enlace de reserva (válido 7 días, un solo uso):');
        $this->line($base . '/reservar/' . $token);

        return self::SUCCESS;
    }
}
