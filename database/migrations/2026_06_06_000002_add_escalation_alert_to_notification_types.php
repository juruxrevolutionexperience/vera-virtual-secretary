<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade 'escalation_alert' al enum type de vera_notifications, para avisar al dueño
 * cuando Vera escala una queja/incidencia de un cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vera_notifications MODIFY COLUMN type ENUM(
            'daily_summary',
            'weekly_summary',
            'monthly_summary',
            'limit_warning',
            'limit_reached',
            'email_processed',
            'response_generated',
            'high_activity',
            'escalation_alert',
            'custom'
        )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vera_notifications MODIFY COLUMN type ENUM(
            'daily_summary',
            'weekly_summary',
            'monthly_summary',
            'limit_warning',
            'limit_reached',
            'email_processed',
            'response_generated',
            'high_activity',
            'custom'
        )");
    }
};
