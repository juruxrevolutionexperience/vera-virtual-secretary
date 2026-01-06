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
        Schema::create('vera_activity_log', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vera_action_id')->nullable()->constrained('vera_email_actions')->onDelete('set null');
            $table->unsignedBigInteger('token_usage_id')->nullable(); // Sin FK por ahora, se agregará después

            // Tipo de actividad
            $table->enum('activity_type', [
                'email_processed',      // Email analizado
                'email_replied',        // Respuesta generada/enviada
                'email_archived',       // Email archivado
                'email_categorized',    // Email categorizado
                'chat_interaction',     // Chat con Vera
                'config_updated',       // Configuración cambiada
                'rule_created',         // Regla personalizada creada
                'training_updated',     // Entrenamiento actualizado
                'limit_reached'         // Límite mensual alcanzado
            ]);

            // Datos de la actividad
            $table->string('title');                    // "Email respondido a juan@example.com"
            $table->text('description')->nullable();    // Descripción detallada
            $table->json('metadata')->nullable();       // Datos adicionales

            // INTEGRACIÓN CON TOKENS
            $table->integer('tokens_consumed')->default(0);     // Tokens de esta acción
            $table->decimal('cost', 8, 6)->default(0);          // Costo en USD

            // INTEGRACIÓN CON PLANES
            $table->boolean('counts_towards_limit')->default(true);  // ¿Cuenta para el límite mensual?
            $table->string('plan_type_at_time')->nullable();         // Plan del usuario en ese momento

            // Status y feedback
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->decimal('confidence_score', 3, 2)->nullable();  // 0.00-1.00
            $table->enum('user_feedback', ['approved', 'rejected', 'modified', 'pending'])->default('pending');
            $table->text('user_notes')->nullable();

            $table->timestamps();

            // Índices para performance
            $table->index(['user_id', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            $table->index('user_feedback');
            $table->index('tokens_consumed');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vera_activity_log');
    }
};
