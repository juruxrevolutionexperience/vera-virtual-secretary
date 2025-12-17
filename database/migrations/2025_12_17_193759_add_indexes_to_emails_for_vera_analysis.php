<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            // Solo añadimos índices que no existen
            // from_email ya existe, no lo añadimos
            $table->index('to_email');
            $table->index(['user_id', 'from_email']); // Compuesto para getTopContacts
            $table->index(['user_id', 'status']);      // Compuesto para filtrar enviados
            $table->index(['user_id', 'received_at']); // Compuesto para ordenar por fecha
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex(['to_email']);
            $table->dropIndex(['user_id', 'from_email']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'received_at']);
        });
    }
};
