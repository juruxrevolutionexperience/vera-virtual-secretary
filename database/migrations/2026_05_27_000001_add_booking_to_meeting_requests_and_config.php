<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Web de reservas (tipo Calendly): enlace tokenizado por cliente para que elija
 * su hueco él mismo. Todo aditivo y nullable — no afecta a datos ni flujos
 * existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            // Token único de un solo uso para la página de reserva
            $table->string('booking_token', 64)->nullable()->unique();
            $table->timestamp('booking_token_expires_at')->nullable();
            $table->timestamp('booking_token_used_at')->nullable();
        });

        Schema::table('vera_configs', function (Blueprint $table) {
            // false = el dueño confirma cada cita (default, más control)
            // true  = si hay hueco, la cita se confirma sola al reservar
            $table->boolean('auto_confirm_bookings')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropColumn(['booking_token', 'booking_token_expires_at', 'booking_token_used_at']);
        });

        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn('auto_confirm_bookings');
        });
    }
};
