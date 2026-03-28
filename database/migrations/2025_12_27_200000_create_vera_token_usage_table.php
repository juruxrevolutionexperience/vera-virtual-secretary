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
        Schema::create('vera_token_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('conversation_id')->nullable(); // FK a conversations (Sprint 2)

            // Tipo de acción que consumió tokens
            $table->enum('action_type', [
                'intent_detection',
                'response_generation',
                'slot_proposal',
                'chat_interaction',
                'quick_classify'
            ]);

            // Tokens consumidos (datos de Gemini API)
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            // Metadata del modelo usado
            $table->string('model', 100)->default('gemini-1.5-flash');
            $table->decimal('cost', 10, 6)->default(0.000000)->comment('Costo en USD');

            // Datos adicionales para analytics (JSON)
            $table->json('metadata')->nullable()->comment('Subject, from, content_length, duration_ms, etc.');

            $table->timestamps();

            // Índices para consultas rápidas
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index(['user_id', 'action_type'], 'idx_user_action');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vera_token_usage');
    }
};
