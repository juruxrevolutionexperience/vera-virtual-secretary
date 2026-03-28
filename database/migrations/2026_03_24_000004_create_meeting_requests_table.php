<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 — Conversaciones en curso antes de confirmar la reunión.
 * Estado intermedio entre el primer mensaje del cliente y la reunión confirmada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Canal de entrada
            $table->enum('channel', ['email', 'whatsapp', 'telegram'])
                ->default('email');

            // Contacto (puede ser externo, sin FK obligatoria)
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->string('contact_identifier')->nullable()
                ->comment('Email, número de teléfono o username según canal');

            // Estado de la negociación
            $table->enum('state', [
                'new',            // Mensaje recibido, sin procesar
                'negotiating',    // Vera está proponiendo/negociando slots
                'awaiting',       // Vera envió propuesta, esperando respuesta del cliente
                'confirmed',      // Reunión confirmada → se crea un registro en meetings
                'cancelled',      // Cancelada por cualquiera de las partes
                'escalated',      // Escalada al humano (trigger detectado)
                'expired',        // Sin respuesta tras X días
            ])->default('new');

            // Contexto completo de la conversación en curso
            $table->json('context')->nullable()
                ->comment('Slots propuestos, mensaje original, historial reciente, etc.');

            // Referencia a la conversación de Vera y a la reunión resultante
            $table->foreignId('conversation_id')->nullable()
                ->constrained('vera_conversations')->onDelete('set null');
            $table->unsignedBigInteger('meeting_id')->nullable()->index()
                ->comment('ID de la reunión creada cuando state=confirmed');

            // Motivo de escalado (si aplica)
            $table->string('escalation_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'state']);
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_requests');
    }
};
