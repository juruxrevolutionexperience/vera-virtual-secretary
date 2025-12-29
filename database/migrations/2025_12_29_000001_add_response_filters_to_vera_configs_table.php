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
        Schema::table('vera_configs', function (Blueprint $table) {
            // Categorías de emails permitidas para respuesta automática
            // null = todas permitidas, [] = ninguna, ['trabajo', 'personal'] = solo esas
            $table->json('response_allowed_categories')->nullable()->after('auto_response_require_approval');

            // Categorías de emails bloqueadas para respuesta automática
            // Por defecto bloqueamos promociones y newsletters
            $table->json('response_blocked_categories')->nullable()->after('response_allowed_categories');

            // Palabras clave que bloquean respuesta automática
            // Si el asunto o cuerpo contiene estas palabras, no responder
            $table->json('blocked_keywords')->nullable()->after('response_blocked_categories');

            // Patrones de remitentes bloqueados (emails o dominios)
            // Ej: ['noreply', 'marketing@', '@spam.com']
            $table->json('blocked_senders')->nullable()->after('blocked_keywords');

            // Remitentes/dominios prioritarios que siempre deben responderse
            // Ej: ['jefe@empresa.com', '@clientevip.com']
            $table->json('allowed_senders')->nullable()->after('blocked_senders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn([
                'response_allowed_categories',
                'response_blocked_categories',
                'blocked_keywords',
                'blocked_senders',
                'allowed_senders',
            ]);
        });
    }
};
