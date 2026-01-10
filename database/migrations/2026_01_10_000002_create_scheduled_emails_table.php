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
        Schema::create('scheduled_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_account_id')->constrained()->onDelete('cascade');

            // Email original (si es una respuesta/forward)
            $table->foreignId('original_email_id')->nullable()->constrained('emails')->onDelete('set null');

            // Template usado (opcional)
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->onDelete('set null');

            // Destinatarios
            $table->string('to'); // Puede ser múltiples separados por coma
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();

            // Contenido del email
            $table->string('subject');
            $table->text('body');
            $table->text('body_html')->nullable();

            // Adjuntos (JSON array de paths)
            $table->json('attachments')->nullable();

            // Programación
            $table->timestamp('scheduled_at'); // Cuándo se debe enviar
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Error tracking
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);

            // Metadata adicional
            $table->json('metadata')->nullable(); // Para guardar info adicional

            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->index('email_account_id');
            $table->index('scheduled_at');
            $table->index('status');
            $table->index(['status', 'scheduled_at']); // Para queries del cron job
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_emails');
    }
};
