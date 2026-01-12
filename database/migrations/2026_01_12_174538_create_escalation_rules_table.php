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
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Información básica
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);  // Orden de ejecución

            // Condiciones para activar la regla
            $table->json('conditions');  // Array de condiciones
            // Ejemplo: [
            //   'email_priority' => 'high',
            //   'no_response_hours' => 24,
            //   'category_id' => 5,
            //   'has_due_date' => true
            // ]

            // Acción a tomar
            $table->enum('action_type', [
                'create_alert',       // Crear una alerta
                'notify_email',       // Enviar notificación por email
                'change_priority',    // Cambiar prioridad del email
                'assign_to_folder',   // Mover a carpeta específica
                'add_tag',           // Añadir etiqueta
                'mark_as_urgent',    // Marcar como urgente
                'forward_email',     // Reenviar a otro usuario
                'custom'             // Acción personalizada
            ]);
            $table->json('action_config');  // Configuración específica de la acción
            // Ejemplo para create_alert: {
            //   'severity': 'high',
            //   'title': 'Email sin respuesta por 24h',
            //   'message': 'El email requiere atención urgente'
            // }

            // Límites y restricciones
            $table->integer('max_executions_per_email')->nullable();  // Máximo de veces que se ejecuta por email
            $table->integer('cooldown_minutes')->nullable();  // Tiempo mínimo entre ejecuciones

            // Estadísticas
            $table->integer('times_triggered')->default(0);
            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'is_active']);
            $table->index(['priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};
