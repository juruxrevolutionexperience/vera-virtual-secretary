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
        Schema::create('writing_style_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Saludos y despedidas frecuentes
            $table->json('common_greetings')->nullable(); // ["Hola", "Buenos días", ...]
            $table->json('common_closings')->nullable(); // ["Saludos", "Un abrazo", ...]

            // Expresiones características
            $table->json('signature_phrases')->nullable(); // Frases que usa mucho
            $table->json('common_words')->nullable(); // Top palabras más usadas

            // Estilo general
            $table->string('overall_tone')->nullable(); // formal, casual, mixed
            $table->decimal('formality_level', 3, 2)->nullable();
            $table->boolean('uses_emojis')->default(false);
            $table->decimal('emoji_frequency', 3, 2)->nullable();

            // Estructura
            $table->integer('avg_paragraph_length')->nullable();
            $table->integer('avg_sentence_length')->nullable();
            $table->integer('avg_email_length')->nullable();

            // Metadata
            $table->integer('analyzed_emails_count')->default(0);
            $table->timestamp('last_analysis_at')->nullable();
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
        Schema::dropIfExists('writing_style_patterns');
    }
};
