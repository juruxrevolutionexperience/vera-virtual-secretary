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
        Schema::create('email_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_category_id')->nullable()->constrained()->onDelete('set null');

            // Información de la regla
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Mayor prioridad = se ejecuta primero

            // Condiciones (JSON con estructura flexible para futuras expansiones)
            $table->json('conditions'); // {from: "email@example.com", subject_contains: "urgente", has_attachments: true}
            $table->enum('condition_match', ['all', 'any'])->default('all'); // AND vs OR

            // Acciones a ejecutar
            $table->json('actions'); // {move_to_category: 5, mark_as_important: true, auto_reply: "mensaje", forward_to: "email"}

            // Estadísticas
            $table->integer('times_applied')->default(0);
            $table->timestamp('last_applied_at')->nullable();

            // Machine Learning
            $table->boolean('is_ai_generated')->default(false); // Si fue creada por Vera
            $table->decimal('ai_confidence', 3, 2)->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'is_active', 'priority']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_rules');
    }
};
