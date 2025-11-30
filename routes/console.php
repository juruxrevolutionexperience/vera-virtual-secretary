<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincronización incremental automática cada 2 minutos
// Solo para cuentas que hayan completado la sincronización inicial
Schedule::call(function () {
    $accounts = \App\Models\EmailAccount::where('sync_enabled', true)
        ->whereNotNull('initial_sync_completed_at')
        ->get();

    foreach ($accounts as $account) {
        \App\Jobs\IncrementalSyncEmailsJob::dispatch($account);
    }
})->everyTwoMinutes()->name('incremental-sync-emails');

// Refrescar tokens que están próximos a expirar (30 minutos antes)
Schedule::call(function () {
    $accounts = \App\Models\EmailAccount::where('sync_enabled', true)
        ->whereNotNull('token_expires_at')
        ->where('token_expires_at', '<=', now()->addMinutes(30))
        ->get();

    foreach ($accounts as $account) {
        // Solo loggear por ahora, el refresh se hace automáticamente en GmailSyncService
        \Log::info('Token próximo a expirar', [
            'account_id' => $account->id,
            'expires_at' => $account->token_expires_at,
        ]);
    }
})->hourly()->name('check-email-tokens');
