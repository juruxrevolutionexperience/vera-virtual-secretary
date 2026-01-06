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
        Schema::create('email_folder_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_folder_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamp('assigned_at')->useCurrent();
            $table->text('notes')->nullable(); // Notas sobre por qué se asignó a esta carpeta

            // Timestamps requeridos por withTimestamps() en el modelo
            $table->timestamps();

            // Índices únicos para evitar duplicados
            $table->unique(['email_id', 'email_folder_id']);
            $table->index('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_folder_assignments');
    }
};
