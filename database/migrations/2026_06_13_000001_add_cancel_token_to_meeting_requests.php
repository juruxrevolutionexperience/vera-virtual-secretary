<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token para la página pública de CANCELACIÓN (/cancelar/{token}). Espejo del
 * booking_token: Vera manda un enlace, el cliente ve SUS citas futuras y elige
 * cuál cancelar (sin ambigüedad). Reutilizable durante la ventana de validez
 * (puede tener varias citas), por eso no se marca usado al cancelar una. Aditivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->string('cancel_token', 64)->nullable()->unique()->after('follow_up_sent_at');
            $table->timestamp('cancel_token_expires_at')->nullable()->after('cancel_token');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropColumn(['cancel_token', 'cancel_token_expires_at']);
        });
    }
};
