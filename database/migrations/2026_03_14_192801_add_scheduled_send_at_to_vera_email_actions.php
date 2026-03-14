<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_email_actions', function (Blueprint $table) {
            // Cuando está configurado un delay, aquí se guarda cuándo debe enviarse
            $table->timestamp('scheduled_send_at')->nullable()->after('response_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('vera_email_actions', function (Blueprint $table) {
            $table->dropColumn('scheduled_send_at');
        });
    }
};
