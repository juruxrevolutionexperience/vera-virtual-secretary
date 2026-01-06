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
        Schema::create('email_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_folder_id')->nullable()->constrained('email_folders')->onDelete('cascade');

            // Información de la carpeta
            $table->string('name');
            $table->string('icon')->nullable(); // Ícono opcional (ej: 'folder', 'star', 'briefcase')
            $table->string('color')->nullable(); // Color hex (ej: '#FF5733')
            $table->text('description')->nullable();

            // Ordenamiento
            $table->integer('order')->default(0);

            // Configuración
            $table->boolean('is_system_folder')->default(false); // Carpetas del sistema (no editables)
            $table->boolean('is_shared')->default(false); // Si está compartida con otros

            // Metadata
            $table->json('settings')->nullable(); // Configuraciones adicionales

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'parent_folder_id']);
            $table->index('is_system_folder');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_folders');
    }
};
