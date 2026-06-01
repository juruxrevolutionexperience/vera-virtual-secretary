<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade 'response_sent' al enum activity_type.
 *
 * El código (ProcessIncomingMessage) registra 'response_sent' al enviar una
 * respuesta en modo NO-agenda, pero el valor no existía en el enum → el INSERT
 * fallaba con "Data truncated", el job se marcaba como fallido DESPUÉS de enviar
 * el email y al reintentarse podía reenviarlo (causa del doble email).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vera_activity_log MODIFY COLUMN activity_type ENUM(
            'message_received',
            'message_skipped',
            'response_sent',
            'meeting_confirmed',
            'meeting_cancelled',
            'meeting_rescheduled',
            'slot_proposed',
            'escalation',
            'chat_interaction',
            'config_updated',
            'training_updated',
            'limit_reached'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vera_activity_log MODIFY COLUMN activity_type ENUM(
            'message_received',
            'message_skipped',
            'meeting_confirmed',
            'meeting_cancelled',
            'meeting_rescheduled',
            'slot_proposed',
            'escalation',
            'chat_interaction',
            'config_updated',
            'training_updated',
            'limit_reached'
        ) NOT NULL");
    }
};
