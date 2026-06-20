<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubicación del negocio: dirección (texto que Vera dice cuando preguntan dónde
     * está) + enlace de Google Maps (que se pinta como botón "Cómo llegar").
     */
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->string('business_address', 500)->nullable()->after('timezone');
            $table->string('maps_url', 500)->nullable()->after('business_address');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn(['business_address', 'maps_url']);
        });
    }
};
