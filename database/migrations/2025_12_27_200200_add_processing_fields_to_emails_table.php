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
        Schema::table('emails', function (Blueprint $table) {
            // Flag para saber si este email ya fue procesado en el ciclo actual
            $table->boolean('processed_this_billing_cycle')
                ->default(false)
                ->after('vera_processed')
                ->comment('Si el email fue procesado con IA este mes (para límites)');

            // Última vez que se procesó con IA
            $table->timestamp('last_processed_at')
                ->nullable()
                ->after('processed_this_billing_cycle')
                ->comment('Última vez que Vera usó IA en este email');

            // Flag para saber si se saltó IA por límite
            $table->boolean('vera_ai_skipped')
                ->default(false)
                ->after('last_processed_at')
                ->comment('Si se saltó el análisis con IA por límite de plan');

            // Índice para búsquedas rápidas
            $table->index('processed_this_billing_cycle', 'idx_processed_billing');
            $table->index('last_processed_at', 'idx_last_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex('idx_processed_billing');
            $table->dropIndex('idx_last_processed');
            $table->dropColumn([
                'processed_this_billing_cycle',
                'last_processed_at',
                'vera_ai_skipped'
            ]);
        });
    }
};
