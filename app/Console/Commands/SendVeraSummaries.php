<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\VeraNotificationPreference;
use App\Services\VeraNotificationService;
use Carbon\Carbon;

class SendVeraSummaries extends Command
{
    protected $signature = 'vera:send-summaries {type=all}';
    protected $description = 'Enviar resúmenes de Vera (daily, weekly, monthly, o all)';

    public function handle()
    {
        $type = $this->argument('type');
        $service = new VeraNotificationService();

        $this->info("Enviando resúmenes: {$type}");

        // Resúmenes diarios
        if (in_array($type, ['daily', 'all'])) {
            $this->sendDailySummaries($service);
        }

        // Resúmenes semanales
        if (in_array($type, ['weekly', 'all'])) {
            $this->sendWeeklySummaries($service);
        }

        // Resúmenes mensuales
        if (in_array($type, ['monthly', 'all'])) {
            $this->sendMonthlySummaries($service);
        }

        $this->info('✅ Resúmenes enviados correctamente');
    }

    protected function sendDailySummaries(VeraNotificationService $service): void
    {
        $currentTime = Carbon::now()->format('H:i');

        $users = User::whereHas('veraNotificationPreferences', function ($q) use ($currentTime) {
            $q->where('daily_summary_enabled', true)
              ->whereTime('daily_summary_time', $currentTime);
        })->get();

        foreach ($users as $user) {
            $service->sendDailySummary($user);
            $this->info("  Enviado resumen diario a {$user->email}");
        }
    }

    protected function sendWeeklySummaries(VeraNotificationService $service): void
    {
        $dayOfWeek = strtolower(Carbon::now()->format('l'));
        $currentTime = Carbon::now()->format('H:i');

        $users = User::whereHas('veraNotificationPreferences', function ($q) use ($dayOfWeek, $currentTime) {
            $q->where('weekly_summary_enabled', true)
              ->where('weekly_summary_day', $dayOfWeek)
              ->whereTime('weekly_summary_time', $currentTime);
        })->get();

        foreach ($users as $user) {
            $service->sendWeeklySummary($user);
            $this->info("  Enviado resumen semanal a {$user->email}");
        }
    }

    protected function sendMonthlySummaries(VeraNotificationService $service): void
    {
        $dayOfMonth = Carbon::now()->day;
        $currentTime = Carbon::now()->format('H:i');

        $users = User::whereHas('veraNotificationPreferences', function ($q) use ($dayOfMonth, $currentTime) {
            $q->where('monthly_summary_enabled', true)
              ->where('monthly_summary_day', $dayOfMonth)
              ->whereTime('monthly_summary_time', $currentTime);
        })->get();

        foreach ($users as $user) {
            $service->sendMonthlySummary($user);
            $this->info("  Enviado resumen mensual a {$user->email}");
        }
    }
}
