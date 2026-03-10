<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->json('weekly_suggestions')->nullable()->after('analysis_summary');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn('weekly_suggestions');
        });
    }
};
