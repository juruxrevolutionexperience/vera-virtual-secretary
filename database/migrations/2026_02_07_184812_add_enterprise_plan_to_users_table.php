<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar 'enterprise' al enum de plan_type
        DB::statement("ALTER TABLE users MODIFY COLUMN plan_type ENUM('basic', 'starter', 'pro', 'enterprise') NOT NULL DEFAULT 'basic'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver a enum original (solo 3 planes)
        // ADVERTENCIA: Si hay usuarios con plan 'enterprise', esto fallará
        DB::statement("ALTER TABLE users MODIFY COLUMN plan_type ENUM('basic', 'starter', 'pro') NOT NULL DEFAULT 'basic'");
    }
};
