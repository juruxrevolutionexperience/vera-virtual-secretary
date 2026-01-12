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
        Schema::create('email_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_id')->nullable()->constrained()->onDelete('cascade');

            // Tipo y nivel de alerta
            $table->enum('type', [
                'deadline',      // Email con fecha de vencimiento próxima o pasada
                'no_response',   // Email sin respuesta esperada después de X tiempo
                'follow_up',     // Recordatorio de seguimiento
                'escalation',    // Escalado por reglas
                'overdue',       // Tarea vencida
                'high_priority', // Email de alta prioridad no atendido
                'custom'         // Alerta personalizada
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Detalles de la alerta
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();  // Datos adicionales específicos del tipo

            // Estado
            $table->enum('status', ['pending', 'acknowledged', 'resolved', 'dismissed'])->default('pending');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();

            // Acciones tomadas
            $table->text('resolution_notes')->nullable();

            // Configuración de recordatorios
            $table->timestamp('alert_at')->nullable();  // Cuándo mostrar la alerta
            $table->timestamp('expires_at')->nullable();  // Cuándo expira la alerta

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['user_id', 'status', 'severity']);
            $table->index(['email_id']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_alerts');
    }
};
