<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zona horaria del negocio. Fuente de verdad para generar slots, reservar y crear
 * eventos en Calendar. Antes se derivaba de user->timezone / config('app.timezone'),
 * lo que causaba confusión de horas. Default Europe/Madrid (mayoría de clientes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->string('timezone', 64)->default('Europe/Madrid')->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
