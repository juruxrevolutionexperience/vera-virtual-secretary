<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos para el seguimiento de leads que NO agendan: cuándo se envió el enlace de
 * reserva y cuándo se mandó el recordatorio de seguimiento (a las 24h). Así Vera
 * re-engancha al cliente que recibió el enlace pero no reservó. Aditivo y nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->timestamp('booking_link_sent_at')->nullable()->after('meeting_id');
            $table->timestamp('follow_up_sent_at')->nullable()->after('booking_link_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropColumn(['booking_link_sent_at', 'follow_up_sent_at']);
        });
    }
};
