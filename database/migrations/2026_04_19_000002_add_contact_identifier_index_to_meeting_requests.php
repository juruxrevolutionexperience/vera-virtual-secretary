<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            // getOrCreateMeetingRequest() busca por user_id + channel + contact_identifier en cada mensaje
            $table->index(['user_id', 'channel', 'contact_identifier'], 'idx_meeting_requests_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropIndex('idx_meeting_requests_lookup');
        });
    }
};
