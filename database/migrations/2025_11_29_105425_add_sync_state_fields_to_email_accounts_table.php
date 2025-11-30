<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            // Para controlar el estado de la sincronización
            $table->timestamp('initial_sync_completed_at')->nullable()->after('last_sync_at');
            $table->integer('total_expected_emails')->default(0)->after('total_emails_synced');
            $table->integer('synced_emails_count')->default(0)->after('total_expected_emails');
            $table->string('last_history_id')->nullable()->after('synced_emails_count');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'initial_sync_completed_at',
                'total_expected_emails',
                'synced_emails_count',
                'last_history_id'
            ]);
        });
    }
};
