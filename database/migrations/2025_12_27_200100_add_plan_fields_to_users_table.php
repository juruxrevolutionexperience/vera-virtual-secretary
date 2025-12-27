<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Plan de suscripción del usuario
            $table->enum('plan_type', ['basic', 'starter', 'pro'])
                ->default('basic')
                ->after('email');

            // Límite mensual de emails procesables según el plan
            $table->unsignedInteger('monthly_email_limit')
                ->default(100)
                ->after('plan_type')
                ->comment('Emails procesables por mes según plan');

            // Fecha de inicio del ciclo de facturación
            $table->date('billing_cycle_start')
                ->nullable()
                ->after('monthly_email_limit')
                ->comment('Inicio del ciclo de facturación mensual');

            // Metadata del plan (historial, promociones, etc.)
            $table->json('plan_metadata')
                ->nullable()
                ->after('billing_cycle_start')
                ->comment('Historial de planes, promociones aplicadas, etc.');

            // Fecha de último upgrade/downgrade
            $table->timestamp('plan_upgraded_at')
                ->nullable()
                ->after('plan_metadata');

            // Índice para búsquedas por plan
            $table->index('plan_type', 'idx_plan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_plan_type');
            $table->dropColumn([
                'plan_type',
                'monthly_email_limit',
                'billing_cycle_start',
                'plan_metadata',
                'plan_upgraded_at'
            ]);
        });
    }
};
