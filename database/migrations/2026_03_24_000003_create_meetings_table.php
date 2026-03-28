<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 — Tabla de reuniones confirmadas.
 * El destino final de cada conversación que gestiona Vera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Datos del contacto (puede ser externo, no depende del modelo Contact)
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');

            // Fecha y duración
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->string('timezone', 64)->default('Europe/Madrid')
                ->comment('Timezone del negocio al crear la reunión');

            // Estado
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'rescheduled', 'completed'])
                ->default('pending');

            // Canal de origen
            $table->enum('source_channel', ['email', 'whatsapp', 'telegram', 'manual'])
                ->default('manual');

            // Google Calendar (Sprint 3)
            $table->string('google_event_id')->nullable()->index();
            $table->string('google_meet_link')->nullable();

            // Referencia a la solicitud que originó esta reunión (sin FK para evitar dependencia circular)
            $table->unsignedBigInteger('meeting_request_id')->nullable()->index();

            // Notas internas
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
