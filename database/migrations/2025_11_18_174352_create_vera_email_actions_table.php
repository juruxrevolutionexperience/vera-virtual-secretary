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
        Schema::create('vera_email_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Tipo de acción realizada por Vera
            $table->enum('action_type', [
                'categorized',
                'replied',
                'forwarded',
                'archived',
                'prioritized',
                'scheduled',
                'extracted_info',
                'created_task',
                'other'
            ]);

            // Detalles de la acción
            $table->text('action_description');
            $table->json('action_data')->nullable(); // Datos adicionales en JSON
            $table->text('reasoning')->nullable(); // Por qué Vera tomó esta decisión

            // Respuesta generada (si aplica)
            $table->longText('generated_response')->nullable();
            $table->boolean('response_sent')->default(false);
            $table->timestamp('response_sent_at')->nullable();

            // Feedback del usuario
            $table->enum('user_feedback', ['approved', 'rejected', 'modified', 'pending'])->default('pending');
            $table->text('user_notes')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0.00 a 1.00

            // Machine Learning (para entrenar a Vera)
            $table->boolean('used_for_training')->default(false);
            $table->json('ml_features')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['email_id', 'action_type']);
            $table->index(['user_id', 'user_feedback']);
            $table->index('action_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vera_email_actions');
    }
};
