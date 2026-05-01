<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

class InviteUser extends Command
{
    protected $signature   = 'vera:invite {email} {--days=30 : Días de validez}';
    protected $description = 'Generar un enlace de invitación para acceder a la beta';

    public function handle(): int
    {
        $email = $this->argument('email');
        $days  = (int) $this->option('days');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Email no válido: {$email}");
            return self::FAILURE;
        }

        $invitation = Invitation::generate($email, $days);
        $link = config('app.url') . '/invite/' . $invitation->token;

        $this->info("✓ Invitación generada para: {$email}");
        $this->line("  Enlace: {$link}");
        $this->line("  Válida hasta: " . $invitation->expires_at->format('d/m/Y'));

        return self::SUCCESS;
    }
}
