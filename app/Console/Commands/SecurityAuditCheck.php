<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityAuditCheck extends Command
{
    protected $signature = 'security:audit-check
                            {--notify= : Email al que enviar alerta si hay CVEs (opcional)}';

    protected $description = 'Ejecuta composer audit y notifica si hay vulnerabilidades HIGH o MEDIUM';

    public function handle(): int
    {
        $this->info('Ejecutando composer audit...');

        $output     = [];
        $returnCode = 0;

        exec('composer audit --no-dev --format=json 2>/dev/null', $output, $returnCode);

        $json = json_decode(implode('', $output), true);

        if (!$json || empty($json['advisories'])) {
            $this->info('✅ Sin vulnerabilidades encontradas.');
            Log::info('security:audit-check — sin vulnerabilidades');
            return 0;
        }

        $advisories = $json['advisories'];
        $high   = [];
        $medium = [];
        $low    = [];

        foreach ($advisories as $package => $list) {
            foreach ($list as $advisory) {
                $severity = strtolower($advisory['severity'] ?? 'low');
                $cve      = $advisory['cve'] ?? 'sin CVE';
                $entry    = "[{$severity}] {$package} — {$advisory['title']} ({$cve})";

                if (in_array($severity, ['high', 'critical'])) {
                    $high[] = $entry;
                } elseif ($severity === 'medium') {
                    $medium[] = $entry;
                } else {
                    $low[] = $entry;
                }
            }
        }

        // Log siempre
        if (!empty($high)) {
            foreach ($high as $item) {
                Log::critical("🚨 CVE HIGH: {$item}");
                $this->error($item);
            }
        }
        foreach ($medium as $item) {
            Log::warning("⚠️ CVE MEDIUM: {$item}");
            $this->warn($item);
        }
        foreach ($low as $item) {
            Log::info("ℹ️ CVE LOW: {$item}");
            $this->line($item);
        }

        // Notificación por email si hay HIGH o MEDIUM y se pasó --notify
        $email = $this->option('notify') ?? config('mail.from.address');
        if ((!empty($high) || !empty($medium)) && $email) {
            $lines   = array_merge($high, $medium, $low);
            $subject = empty($high)
                ? '⚠️ Vera — Vulnerabilidades MEDIUM en dependencias'
                : '🚨 Vera — Vulnerabilidades HIGH en dependencias';

            try {
                Mail::raw(
                    "Resultado de composer audit:\n\n" . implode("\n", $lines) .
                    "\n\nEjecuta: composer audit\nActualiza: composer update [paquete]",
                    fn ($msg) => $msg->to($email)->subject($subject)
                );
                $this->info("📧 Notificación enviada a {$email}");
            } catch (\Exception $e) {
                Log::warning('security:audit-check — no se pudo enviar email: ' . $e->getMessage());
            }
        }

        // Salir con código de error si hay HIGH para que CI/CD falle
        return empty($high) ? 0 : 1;
    }
}
