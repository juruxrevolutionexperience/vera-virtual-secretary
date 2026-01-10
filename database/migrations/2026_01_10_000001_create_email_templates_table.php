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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Información básica del template
            $table->string('name'); // Nombre del template
            $table->string('subject'); // Asunto del email
            $table->text('body'); // Cuerpo del email (puede incluir placeholders)
            $table->text('description')->nullable(); // Descripción del template

            // Tipo de template
            $table->enum('type', ['reply', 'forward', 'new', 'auto_response'])->default('reply');

            // Placeholders disponibles (JSON array)
            // Ej: ["{{nombre}}", "{{empresa}}", "{{fecha}}"]
            $table->json('placeholders')->nullable();

            // Configuración
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Si es el template por defecto para este tipo

            // Categoría/Tag para organizar templates
            $table->string('category')->nullable();

            // Estadísticas
            $table->integer('usage_count')->default(0); // Cuántas veces se ha usado
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('user_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
