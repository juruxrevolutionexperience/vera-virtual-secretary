<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Delay humano": segundos que Vera espera antes de responder.
     * Lo elige el dueño del negocio en su panel. 0 = respuesta instantánea (por defecto).
     */
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->unsignedSmallInteger('response_delay_seconds')->default(0)->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn('response_delay_seconds');
        });
    }
};
