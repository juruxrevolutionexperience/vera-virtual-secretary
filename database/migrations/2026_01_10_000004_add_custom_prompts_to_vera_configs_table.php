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
        Schema::table('vera_configs', function (Blueprint $table) {
            // Prompts personalizados para diferentes acciones
            $table->text('custom_categorization_prompt')->nullable()->after('onboarding_completed_at');
            $table->text('custom_priority_prompt')->nullable()->after('custom_categorization_prompt');
            $table->text('custom_response_prompt')->nullable()->after('custom_priority_prompt');
            $table->text('custom_summary_prompt')->nullable()->after('custom_response_prompt');

            // Configuración de estilo de respuesta
            $table->enum('response_tone', ['formal', 'casual', 'friendly', 'professional', 'custom'])->default('professional')->after('custom_summary_prompt');
            $table->text('custom_tone_description')->nullable()->after('response_tone'); // Si es 'custom'

            // Configuración de idioma
            $table->string('response_language', 10)->default('es')->after('custom_tone_description'); // es, en, etc.

            // Firma automática para respuestas
            $table->text('email_signature')->nullable()->after('response_language');
            $table->boolean('include_signature')->default(false)->after('email_signature');

            // Configuración de sugerencias
            $table->boolean('enable_suggestions')->default(true)->after('include_signature');
            $table->integer('max_suggestions')->default(3)->after('enable_suggestions');
            $table->boolean('auto_apply_best_suggestion')->default(false)->after('max_suggestions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn([
                'custom_categorization_prompt',
                'custom_priority_prompt',
                'custom_response_prompt',
                'custom_summary_prompt',
                'response_tone',
                'custom_tone_description',
                'response_language',
                'email_signature',
                'include_signature',
                'enable_suggestions',
                'max_suggestions',
                'auto_apply_best_suggestion',
            ]);
        });
    }
};
