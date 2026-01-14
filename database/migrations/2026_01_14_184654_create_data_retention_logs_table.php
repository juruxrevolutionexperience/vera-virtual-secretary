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
        Schema::create('data_retention_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('data_type'); // 'email', 'attachment', 'session', 'log', 'all'
            $table->integer('records_deleted')->default(0); // Cantidad de registros eliminados
            $table->string('retention_period'); // '30_days', '90_days', '1_year', etc.
            $table->string('trigger'); // 'manual', 'automatic', 'policy', 'gdpr_request'
            $table->json('metadata')->nullable(); // Info adicional (tabla, ids, tamaño, etc.)
            $table->timestamp('deleted_at'); // Fecha de eliminación
            $table->foreignId('executed_by')->nullable()->constrained('users'); // Quién lo ejecutó (si fue manual)
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'data_type']);
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_retention_logs');
    }
};
