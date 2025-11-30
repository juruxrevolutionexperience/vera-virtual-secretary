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
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained()->onDelete('cascade');

            // Información del archivo
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->bigInteger('size'); // En bytes
            $table->string('path'); // Ruta en storage
            $table->string('disk')->default('local'); // local, s3, etc.

            // Metadata
            $table->string('attachment_id')->nullable(); // ID del proveedor
            $table->boolean('is_inline')->default(false); // Si es imagen embebida
            $table->string('content_id')->nullable(); // Para imágenes inline

            // Escaneo de seguridad (para futuro)
            $table->boolean('is_scanned')->default(false);
            $table->boolean('is_safe')->default(true);
            $table->text('scan_result')->nullable();

            $table->timestamps();

            // Índices
            $table->index('email_id');
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
