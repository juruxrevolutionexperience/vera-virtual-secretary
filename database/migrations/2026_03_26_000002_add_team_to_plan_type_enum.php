<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN plan_type ENUM('basic','starter','pro','team','enterprise') NOT NULL DEFAULT 'basic'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN plan_type ENUM('basic','starter','pro','enterprise') NOT NULL DEFAULT 'basic'");
    }
};
