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
            $table->string('provider_thread_id')->nullable()->after('provider_message_id');
            $table->string('to_email')->nullable()->after('from_name');
            $table->string('to_name')->nullable()->after('to_email');
            $table->string('status')->default('received')->after('provider_metadata');
            $table->string('category')->default('primary')->after('status');
            $table->json('metadata')->nullable()->after('category');
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
