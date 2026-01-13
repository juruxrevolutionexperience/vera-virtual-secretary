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
        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('export_format')->default('json'); // json, csv, xml
            $table->json('data_types')->nullable(); // Tipos de datos a exportar: ['emails', 'contacts', 'folders', etc.]
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_path')->nullable(); // Ruta al archivo generado
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // Tamaño en bytes
            $table->timestamp('expires_at')->nullable(); // Fecha de expiración del archivo (7 días después)
            $table->string('download_token', 64)->nullable(); // Token seguro para descarga
            $table->unsignedInteger('download_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('download_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_export_requests');
    }
};
