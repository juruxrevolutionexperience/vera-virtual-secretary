<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Quitar límite de emails (era el antiguo Vera email)
            $table->dropColumn('monthly_email_limit');

            // Añadir límites de reuniones (nuevo Vera agenda)
            // Básica: 30/mes | Pro: ilimitado (null) | Equipo: ilimitado (null)
            $table->unsignedInteger('monthly_meeting_limit')
                ->default(30)
                ->after('plan_type')
                ->comment('Reuniones confirmadas por mes según plan. null = ilimitado.');

            $table->unsignedInteger('meetings_this_month')
                ->default(0)
                ->after('monthly_meeting_limit')
                ->comment('Contador de reuniones confirmadas en el ciclo actual.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monthly_meeting_limit', 'meetings_this_month']);

            $table->unsignedInteger('monthly_email_limit')
                ->default(100)
                ->after('plan_type');
        });
    }
};
