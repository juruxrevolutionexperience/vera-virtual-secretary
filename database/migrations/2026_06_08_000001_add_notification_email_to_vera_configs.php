<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email donde el dueño recibe los AVISOS de Vera (quejas escaladas, resúmenes...).
 * Separado del Gmail que Vera gestiona: el dueño quiere los avisos en SU correo
 * personal, no mezclados en la bandeja que Vera atiende. Nullable → si está vacío,
 * se usa el email de la cuenta como hasta ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->string('notification_email')->nullable()->after('owner_name');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }
};
