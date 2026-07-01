<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->unsignedTinyInteger('reminder_1_hours')->default(24)->after('buffer_minutes');
            $table->unsignedTinyInteger('reminder_2_hours')->default(1)->after('reminder_1_hours');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn(['reminder_1_hours', 'reminder_2_hours']);
        });
    }
};
