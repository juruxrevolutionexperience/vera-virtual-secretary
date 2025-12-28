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
        Schema::create('vera_conversations', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Tipo de conversación
            $table->enum('conversation_type', [
                'chat',                 // Chat general
                'training',             // Entrenamiento de Vera
                'rule_creation',        // Creación de reglas
                'email_review',         // Revisión de email específico
                'style_learning'        // Aprendizaje de estilo de escritura
            ])->default('chat');

            // Título de la conversación
            $table->string('title')->nullable();

            // Estado de la conversación
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');

            // Metadata
            $table->json('metadata')->nullable();  // Información adicional contextual

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'created_at']);
            $table->index('conversation_type');
            $table->index('status');
        });

        Schema::create('vera_conversation_messages', function (Blueprint $table) {
            $table->id();

            // Relación con conversación
            $table->foreignId('conversation_id')->constrained('vera_conversations')->onDelete('cascade');

            // Role: user, assistant, system
            $table->enum('role', ['user', 'assistant', 'system']);

            // Contenido del mensaje
            $table->text('content');

            // Tokens consumidos (solo para mensajes de assistant)
            $table->integer('tokens_consumed')->default(0);
            $table->decimal('cost', 8, 6)->default(0);

            // Metadata adicional (ej: ejemplos aprendidos, reglas creadas)
            $table->json('metadata')->nullable();

            // Feedback del usuario sobre la respuesta
            $table->enum('user_feedback', ['helpful', 'not_helpful', 'neutral', 'pending'])->default('pending');

            $table->timestamps();

            // Índices
            $table->index(['conversation_id', 'created_at']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vera_conversation_messages');
        Schema::dropIfExists('vera_conversations');
    }
};
