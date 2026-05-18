<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vera_activity_log MODIFY COLUMN status ENUM(
            'pending',
            'completed',
            'failed',
            'skipped'
        ) NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vera_activity_log MODIFY COLUMN status ENUM(
            'pending',
            'completed',
            'failed'
        ) NOT NULL DEFAULT 'completed'");
    }
};
