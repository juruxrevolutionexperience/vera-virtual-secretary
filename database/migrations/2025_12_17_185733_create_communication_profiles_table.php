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
        Schema::create('communication_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email_address')->index(); // Remitente
            $table->string('name')->nullable();

            // Estadísticas de comunicación
            $table->integer('total_emails_received')->default(0);
            $table->integer('total_emails_sent')->default(0);
            $table->decimal('avg_response_time_hours', 8, 2)->nullable();
            $table->string('communication_frequency')->nullable(); // daily, weekly, monthly, sporadic

            // Análisis de tono
            $table->string('detected_tone')->nullable(); // formal, casual, friendly, business
            $table->decimal('formality_score', 3, 2)->nullable(); // 0.00 a 1.00
            $table->decimal('avg_sentiment', 3, 2)->nullable();

            // Patrones identificados
            $table->json('common_topics')->nullable();
            $table->json('typical_greetings')->nullable();
            $table->json('typical_closings')->nullable();
            $table->integer('avg_message_length')->nullable();

            // Metadata
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('profile_updated_at')->nullable();
            $table->timestamps();

            // Índices
            $table->unique(['user_id', 'email_address']);
            $table->index(['user_id', 'last_interaction_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_profiles');
    }
};
