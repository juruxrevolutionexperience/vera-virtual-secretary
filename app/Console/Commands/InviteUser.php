<?php

namespace App\Console\Commands;

use App\Mail\BetaInvitationMail;
use App\Models\Invitation;
use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class InviteUser extends Command
{
    protected $signature   = 'vera:invite {email} {--days=30 : Días de validez} {--plan= : Slug del plan a asignar al aceptar (ej: beta)} {--no-email : No enviar email, solo generar el enlace}';
    protected $description = 'Generar un enlace de invitación para acceder a la beta';

    public function handle(): int
    {
        $email    = $this->argument('email');
        $days     = (int) $this->option('days');
        $planSlug = $this->option('plan') ?: null;
        $sendMail = !$this->option('no-email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Email no válido: {$email}");
            return self::FAILURE;
        }

        if ($planSlug && !Plan::where('slug', $planSlug)->where('is_active', true)->exists()) {
            $this->error("Plan no encontrado o inactivo: {$planSlug}");
            return self::FAILURE;
        }

        $invitation = Invitation::generate($email, $days, $planSlug);
        $link = config('app.url') . '/invite/' . $invitation->token;

        $this->info("✓ Invitación generada para: {$email}");
        $this->line("  Enlace: {$link}");
        $this->line("  Válida hasta: " . $invitation->expires_at->format('d/m/Y'));
        if ($planSlug) {
            $this->line("  Plan al aceptar: {$planSlug}");
        }

        if ($sendMail) {
            try {
                Mail::to($email)->send(new BetaInvitationMail($invitation));
                $this->info("  Email enviado");
            } catch (\Throwable $e) {
                $this->warn("  No se pudo enviar el email: " . $e->getMessage());
                $this->line("  (la invitación quedó creada; comparte el enlace manualmente)");
            }
        } else {
            $this->line("  Email no enviado (--no-email)");
        }

        return self::SUCCESS;
    }
}
