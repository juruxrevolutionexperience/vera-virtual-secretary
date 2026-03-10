<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->string('vera_sentimiento_tono', 50)->nullable()->after('vera_sentiment_score');
            $table->string('vera_badge_sugerido', 50)->nullable()->after('vera_sentimiento_tono');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['vera_sentimiento_tono', 'vera_badge_sugerido']);
        });
    }
};
