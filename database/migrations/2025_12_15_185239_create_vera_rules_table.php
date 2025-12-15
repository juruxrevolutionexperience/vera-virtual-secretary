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
        Schema::create('vera_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Información básica de la regla
            $table->string('name'); // Nombre descriptivo
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0); // Orden de ejecución

            // Tipo de regla
            $table->enum('rule_type', [
                'auto_archive',      // Archivar automáticamente
                'auto_categorize',   // Categorizar
                'auto_respond',      // Responder automáticamente
                'auto_forward',      // Reenviar
                'auto_prioritize',   // Marcar como prioritario
                'custom',            // Regla personalizada
            ])->default('auto_archive');

            // Condiciones (JSON)
            // Ej: {"from_email": "noreply@cenecmalaga.es"}
            // Ej: {"subject_contains": "invoice"}
            $table->json('conditions');

            // Acción a ejecutar (JSON)
            // Ej: {"action": "archive"}
            // Ej: {"action": "categorize", "category": "CENEC"}
            $table->json('action');

            // Estadísticas
            $table->integer('times_applied')->default(0);
            $table->timestamp('last_applied_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'enabled']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vera_rules');
    }
};
