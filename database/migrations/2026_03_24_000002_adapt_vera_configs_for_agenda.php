<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 — Adaptación de vera_configs al nuevo Cerebro de Vera (secretaria de agenda).
 *
 * QUITAR: campos específicos de email (auto_response, newsletter, filtros de remitentes, etc.)
 * AÑADIR: campos del Plan Maestro (business_name, working_hours, meeting_duration, mode, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {

            // ── ELIMINAR campos email-específicos ──────────────────────────────

            // Columnas que existen desde create_vera_configs_table
            $table->dropColumn([
                'main_objective',
                'auto_response_enabled',
                'auto_response_mode',
                'auto_response_require_approval',
                'newsletter_handling',
                'important_contacts',
                're_analysis_frequency_days',
                'last_analysis_at',
                'last_re_analysis_at',
                'analysis_summary',
                'autonomy_level',
                'availability_start',
                'availability_end',
            ]);

            // Columnas añadidas por add_custom_prompts_to_vera_configs (migración existente)
            $table->dropColumn([
                'custom_categorization_prompt',
                'custom_priority_prompt',
                'custom_response_prompt',
                'custom_summary_prompt',
                'response_tone',
                'custom_tone_description',
                'email_signature',
                'include_signature',
                'enable_suggestions',
                'max_suggestions',
                'auto_apply_best_suggestion',
                'response_language',
            ]);
        });

        Schema::table('vera_configs', function (Blueprint $table) {

            // ── AÑADIR campos del Cerebro de Vera (Plan Maestro) ──────────────

            // Identidad del negocio
            $table->string('business_name')->nullable()->after('user_id');
            $table->string('owner_name')->nullable()->after('business_name');

            // Tono y idioma
            $table->enum('tone', ['formal', 'cercano', 'profesional'])
                ->default('profesional')->after('owner_name');
            $table->string('language', 5)->default('es')->after('tone');

            // Horario laboral (JSON)
            // Formato: {"lun": "9-18", "mar": "9-18", "mie": "9-18", "jue": "9-18", "vie": "9-18", "sab": null, "dom": null}
            $table->json('working_hours')->nullable()->after('language');

            // Configuración de reuniones
            $table->unsignedSmallInteger('meeting_duration')->default(45)->after('working_hours')
                ->comment('Duración por defecto de una reunión en minutos: 30 | 45 | 60');
            $table->unsignedSmallInteger('buffer_minutes')->default(15)->after('meeting_duration')
                ->comment('Tiempo mínimo entre reuniones consecutivas');

            // Respuesta cuando Vera no sabe
            $table->text('unknown_response')->nullable()->after('buffer_minutes')
                ->comment('Qué dice Vera cuando no tiene la respuesta. Ej: Déjame consultarlo y te respondo en breve.');

            // Triggers de escalado al humano
            $table->json('escalation_triggers')->nullable()->after('unknown_response')
                ->comment('Array de palabras que activan escalado: ["urgente","queja","emergencia"]');

            // Modo de operación
            $table->enum('mode', ['supervised', 'autonomous'])->default('supervised')->after('escalation_triggers')
                ->comment('supervised: Vera sugiere, usuario aprueba. autonomous: Vera actúa directamente.');

            // Firma de Vera
            $table->string('vera_signature')->nullable()->after('mode')
                ->comment('Ej: Vera, asistente de [negocio]');

            // Google Calendar (Sprint 3)
            $table->string('google_calendar_id')->nullable()->after('vera_signature')
                ->comment('ID del calendario de Google Calendar vinculado');

            $table->string('google_access_token')->nullable()->after('google_calendar_id');
            $table->string('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn([
                'business_name', 'owner_name', 'tone', 'language',
                'working_hours', 'meeting_duration', 'buffer_minutes',
                'unknown_response', 'escalation_triggers', 'mode', 'vera_signature',
                'google_calendar_id', 'google_access_token', 'google_refresh_token', 'google_token_expires_at',
            ]);
        });

        Schema::table('vera_configs', function (Blueprint $table) {
            $table->enum('main_objective', ['filter', 'respond', 'balanced'])->default('balanced');
            $table->boolean('auto_response_enabled')->default(false);
            $table->string('auto_response_mode')->nullable();
            $table->boolean('auto_response_require_approval')->default(true);
            $table->string('newsletter_handling')->default('auto_archive');
            $table->json('response_allowed_categories')->nullable();
            $table->json('response_blocked_categories')->nullable();
            $table->json('blocked_keywords')->nullable();
            $table->json('blocked_senders')->nullable();
            $table->json('allowed_senders')->nullable();
            $table->integer('re_analysis_frequency_days')->default(30);
            $table->timestamp('last_analysis_at')->nullable();
            $table->timestamp('last_re_analysis_at')->nullable();
            $table->json('analysis_summary')->nullable();
            $table->json('weekly_suggestions')->nullable();
            $table->integer('response_delay_minutes')->default(0);
            $table->text('custom_categorization_prompt')->nullable();
            $table->text('custom_priority_prompt')->nullable();
            $table->text('custom_response_prompt')->nullable();
            $table->text('custom_summary_prompt')->nullable();
            $table->enum('response_tone', ['formal', 'casual', 'friendly', 'professional', 'custom'])->default('professional');
            $table->text('custom_tone_description')->nullable();
            $table->text('email_signature')->nullable();
            $table->boolean('include_signature')->default(false);
            $table->boolean('enable_suggestions')->default(true);
            $table->integer('max_suggestions')->default(3);
            $table->boolean('auto_apply_best_suggestion')->default(false);
            $table->enum('autonomy_level', ['conservative', 'moderate', 'aggressive'])->default('moderate');
            $table->time('availability_start')->default('09:00');
            $table->time('availability_end')->default('18:00');
            $table->string('response_language', 10)->default('es');
        });
    }
};
