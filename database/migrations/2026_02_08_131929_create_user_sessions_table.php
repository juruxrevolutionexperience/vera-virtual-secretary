<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La tabla user_sessions ya existe desde 2026_01_14.
        // Esta migración añade las columnas adicionales de seguridad.
        Schema::table('user_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_sessions', 'device_name')) {
                $table->string('device_name')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('user_sessions', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('user_sessions', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('user_sessions', 'is_trusted')) {
                $table->boolean('is_trusted')->default(false)->after('is_current');
            }
            if (!Schema::hasColumn('user_sessions', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable();
            }
            if (!Schema::hasColumn('user_sessions', 'revoked_reason')) {
                $table->string('revoked_reason')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'country', 'city', 'is_trusted', 'revoked_at', 'revoked_reason']);
        });
    }
};
