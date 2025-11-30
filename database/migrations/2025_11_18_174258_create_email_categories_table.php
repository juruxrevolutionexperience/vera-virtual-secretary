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
        Schema::create('email_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Inbox, Importante, Respondidos, Archivados, etc.
            $table->string('slug')->unique(); // inbox, important, replied, archived
            $table->string('color', 7)->default('#6366f1'); // Color hexadecimal para UI
            $table->string('icon')->nullable(); // Clase de ícono o emoji
            $table->integer('order')->default(0); // Orden de visualización
            $table->boolean('is_system')->default(false); // Categorías del sistema vs personalizadas
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // Índices para optimización
            $table->index(['user_id', 'is_active']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_categories');
    }
};
