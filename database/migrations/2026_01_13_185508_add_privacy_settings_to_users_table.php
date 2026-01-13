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
            // Configuraciones de privacidad
            $table->boolean('data_processing_consent')->default(false); // Consentimiento para procesar datos con IA
            $table->boolean('marketing_consent')->default(false); // Consentimiento para marketing
            $table->boolean('analytics_consent')->default(false); // Consentimiento para analytics
            $table->string('data_retention_period')->default('1_year'); // 30_days, 90_days, 1_year, 2_years, indefinite
            $table->boolean('auto_delete_old_emails')->default(false); // Eliminar emails antiguos automáticamente
            $table->timestamp('last_privacy_review_at')->nullable(); // Última vez que revisó configuración de privacidad

            // Seguridad
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_enabled_at')->nullable();

            // RGPD
            $table->timestamp('gdpr_consent_at')->nullable(); // Fecha de aceptación RGPD
            $table->string('gdpr_consent_version')->nullable(); // Versión aceptada
            $table->boolean('account_deletion_requested')->default(false);
            $table->timestamp('account_deletion_requested_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'data_processing_consent',
                'marketing_consent',
                'analytics_consent',
                'data_retention_period',
                'auto_delete_old_emails',
                'last_privacy_review_at',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_enabled_at',
                'gdpr_consent_at',
                'gdpr_consent_version',
                'account_deletion_requested',
                'account_deletion_requested_at',
            ]);
        });
    }
};
