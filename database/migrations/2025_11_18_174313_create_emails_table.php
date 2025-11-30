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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_category_id')->nullable()->constrained()->onDelete('set null');

            // Información del email
            $table->string('message_id')->unique()->nullable(); // ID único del proveedor (Gmail, Outlook)
            $table->string('thread_id')->nullable(); // Para agrupar conversaciones
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->text('to')->nullable(); // JSON array de destinatarios
            $table->text('cc')->nullable(); // JSON array
            $table->text('bcc')->nullable(); // JSON array
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();

            // Metadata y estado
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachments_count')->default(0);

            // Timestamps importantes
            $table->timestamp('received_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            // IA y análisis de Vera
            $table->text('vera_summary')->nullable(); // Resumen generado por IA
            $table->json('vera_tags')->nullable(); // Tags automáticos
            $table->decimal('vera_sentiment_score', 3, 2)->nullable(); // -1.00 a 1.00
            $table->enum('vera_action_required', ['none', 'reply', 'schedule', 'forward', 'archive'])->default('none');
            $table->boolean('vera_processed')->default(false);
            $table->timestamp('vera_processed_at')->nullable();

            // Proveedor de email
            $table->enum('provider', ['gmail', 'outlook', 'manual', 'other'])->default('manual');
            $table->json('provider_metadata')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Para papelera

            // Índices para optimización de búsquedas
            $table->index(['user_id', 'is_read', 'is_archived']);
            $table->index(['user_id', 'email_category_id']);
            $table->index('from_email');
            $table->index('priority');
            $table->index('received_at');
            $table->index('vera_processed');
            $table->fullText(['subject', 'body_text']); // Para búsqueda full-text
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
