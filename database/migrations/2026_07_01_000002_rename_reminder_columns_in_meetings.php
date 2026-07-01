<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->renameColumn('reminder_24h_sent_at', 'reminder_1_sent_at');
            $table->renameColumn('reminder_1h_sent_at',  'reminder_2_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->renameColumn('reminder_1_sent_at', 'reminder_24h_sent_at');
            $table->renameColumn('reminder_2_sent_at', 'reminder_1h_sent_at');
        });
    }
};
