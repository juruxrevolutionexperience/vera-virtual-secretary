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
        Schema::create('vera_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Objetivo principal
            $table->enum('main_objective', ['filter', 'respond', 'balanced'])->default('balanced');

            // Estilo de escritura
            $table->json('writing_style')->nullable(); // {tone, formality_score, avg_length, uses_emojis, greetings, closings}

            // Respuestas automáticas
            $table->boolean('auto_response_enabled')->default(false);
            $table->string('auto_response_mode')->nullable(); // always, important_only, ask_first
            $table->boolean('auto_response_require_approval')->default(true);
            $table->decimal('confidence_threshold', 3, 2)->default(0.75); // 0.00 a 1.00

            // Manejo de newsletters
            $table->string('newsletter_handling')->default('auto_archive'); // auto_archive, mark_read, categorize, manual

            // Contactos importantes (nunca archivar automáticamente)
            $table->json('important_contacts')->nullable(); // Array de emails

            // Horarios de disponibilidad
            $table->time('availability_start')->default('09:00');
            $table->time('availability_end')->default('18:00');

            // Nivel de autonomía
            $table->enum('autonomy_level', ['conservative', 'moderate', 'aggressive'])->default('moderate');

            // Re-análisis
            $table->integer('re_analysis_frequency_days')->default(30);
            $table->timestamp('last_analysis_at')->nullable();
            $table->timestamp('last_re_analysis_at')->nullable();

            // Metadata
            $table->json('analysis_summary')->nullable(); // Resumen del último análisis
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vera_configs');
    }
};
