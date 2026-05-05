<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        DB::statement("ALTER TABLE vera_activity_log MODIFY COLUMN activity_type ENUM(
            'message_received',
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
