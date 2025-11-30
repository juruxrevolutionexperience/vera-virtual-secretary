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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->onDelete('cascade');

            // Información de la sincronización
            $table->enum('sync_type', ['full', 'incremental', 'manual'])->default('incremental');
            $table->enum('direction', ['inbound', 'outbound', 'bidirectional'])->default('inbound');

            // Resultado de la sincronización
            $table->enum('status', ['success', 'partial', 'failed'])->default('success');
            $table->integer('emails_fetched')->default(0); // Emails obtenidos del proveedor
            $table->integer('emails_created')->default(0); // Emails nuevos creados
            $table->integer('emails_updated')->default(0); // Emails actualizados
            $table->integer('emails_failed')->default(0); // Emails que fallaron

            // Detalles de ejecución
            $table->timestamp('started_at'); // Cuándo empezó
            $table->timestamp('completed_at')->nullable(); // Cuándo terminó
            $table->integer('duration_seconds')->nullable(); // Duración en segundos

            // Errores y debugging
            $table->text('error_message')->nullable(); // Mensaje de error si falló
            $table->json('error_details')->nullable(); // Detalles técnicos del error
            $table->json('metadata')->nullable(); // Metadata adicional

            $table->timestamps();

            // Índices
            $table->index(['email_account_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
