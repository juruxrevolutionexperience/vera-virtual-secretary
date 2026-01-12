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
        Schema::create('follow_up_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_id')->constrained()->onDelete('cascade');

            // Configuración del recordatorio
            $table->string('title');
            $table->text('notes')->nullable();
            $table->timestamp('remind_at');  // Cuándo recordar

            // Configuración de repetición
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'custom'])->nullable();
            $table->integer('recurrence_interval')->nullable();  // Cada X días/semanas/meses
            $table->timestamp('recurrence_end_date')->nullable();

            // Estado
            $table->enum('status', ['pending', 'sent', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Método de notificación
            $table->json('notification_channels')->nullable();
            // Ejemplo: ['in_app', 'email', 'push']

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['remind_at', 'status']);
            $table->index(['email_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_reminders');
    }
};
