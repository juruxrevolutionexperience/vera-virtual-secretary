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
        Schema::create('auto_responders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_account_id')->nullable()->constrained()->onDelete('cascade');

            // Información básica
            $table->string('name'); // Nombre descriptivo
            $table->enum('type', ['out_of_office', 'confirmation', 'thank_you', 'custom'])->default('custom');

            // Template a usar
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->onDelete('set null');

            // O mensaje directo
            $table->string('subject')->nullable();
            $table->text('body')->nullable();

            // Condiciones de activación
            $table->boolean('is_active')->default(false);
            $table->timestamp('active_from')->nullable(); // Desde cuando está activo
            $table->timestamp('active_until')->nullable(); // Hasta cuando está activo

            // Filtros - A quién responder automáticamente
            $table->json('sender_filters')->nullable(); // Lista de emails/dominios específicos
            $table->json('subject_filters')->nullable(); // Palabras clave en asunto
            $table->boolean('only_first_contact')->default(true); // Solo responder la primera vez

            // Limitaciones
            $table->integer('max_responses_per_sender')->default(1); // Máximo de respuestas por remitente
            $table->integer('max_responses_per_day')->nullable(); // Límite diario total
            $table->integer('delay_minutes')->default(0); // Delay antes de enviar (en minutos)

            // Horario de operación (JSON)
            // Ej: {"monday": {"enabled": true, "start": "09:00", "end": "18:00"}, ...}
            $table->json('schedule')->nullable();

            // Solo para ciertos tipos de emails
            $table->boolean('reply_to_all')->default(false); // Responder a todos en CC
            $table->boolean('skip_auto_replies')->default(true); // No responder a otros auto-responders

            // Estadísticas
            $table->integer('responses_sent')->default(0);
            $table->timestamp('last_sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('user_id');
            $table->index('email_account_id');
            $table->index('is_active');
            $table->index(['is_active', 'active_from', 'active_until']); // Para queries del cron
        });

        // Tabla para trackear a quién ya se le respondió
        Schema::create('auto_responder_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auto_responder_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_id')->constrained()->onDelete('cascade');
            $table->string('sender_email'); // Email del remitente
            $table->timestamp('responded_at');

            $table->timestamps();

            // Índices
            $table->index('auto_responder_id');
            $table->index('sender_email');
            $table->index(['auto_responder_id', 'sender_email']); // Para verificar duplicados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_responder_history');
        Schema::dropIfExists('auto_responders');
    }
};
