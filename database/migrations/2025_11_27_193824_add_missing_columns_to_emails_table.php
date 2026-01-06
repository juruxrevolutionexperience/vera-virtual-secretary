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
        Schema::table('emails', function (Blueprint $table) {
            // Add missing columns for email sync
            if (!Schema::hasColumn('emails', 'provider_message_id')) {
                $table->string('provider_message_id')->nullable()->after('thread_id');
            }
            if (!Schema::hasColumn('emails', 'provider_thread_id')) {
                $table->string('provider_thread_id')->nullable()->after('thread_id');
            }
            if (!Schema::hasColumn('emails', 'to_email')) {
                $table->string('to_email')->nullable()->after('from_name');
            }
            if (!Schema::hasColumn('emails', 'to_name')) {
                $table->string('to_name')->nullable()->after('to_email');
            }
            if (!Schema::hasColumn('emails', 'status')) {
                $table->string('status')->default('received')->after('provider_metadata');
            }
            if (!Schema::hasColumn('emails', 'category')) {
                $table->string('category')->default('primary')->after('status');
            }
            if (!Schema::hasColumn('emails', 'metadata')) {
                $table->json('metadata')->nullable()->after('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['provider_thread_id', 'to_email', 'to_name', 'status', 'category', 'metadata']);
        });
    }
};
