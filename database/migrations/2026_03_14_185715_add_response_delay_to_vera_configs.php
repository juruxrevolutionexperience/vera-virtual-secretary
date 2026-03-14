<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            // Minutos que Vera espera antes de auto-enviar (0 = inmediato)
            $table->unsignedSmallInteger('response_delay_minutes')->default(0)->after('confidence_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn('response_delay_minutes');
        });
    }
};
