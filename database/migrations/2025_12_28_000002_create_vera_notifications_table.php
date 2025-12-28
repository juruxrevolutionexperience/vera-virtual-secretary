<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vera_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'daily_summary',
                'weekly_summary',
                'monthly_summary',
                'limit_warning',
                'limit_reached',
                'email_processed',
                'response_generated',
                'high_activity',
                'custom'
            ]);

            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();

            $table->enum('channel', ['email', 'push', 'both'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['type', 'scheduled_for']);
        });

        Schema::create('vera_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(false);

            $table->boolean('daily_summary_enabled')->default(true);
            $table->time('daily_summary_time')->default('09:00');

            $table->boolean('weekly_summary_enabled')->default(true);
            $table->enum('weekly_summary_day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->default('monday');
            $table->time('weekly_summary_time')->default('09:00');

            $table->boolean('monthly_summary_enabled')->default(true);
            $table->integer('monthly_summary_day')->default(1);
            $table->time('monthly_summary_time')->default('09:00');

            $table->boolean('limit_warnings_enabled')->default(true);
            $table->integer('warning_threshold_percentage')->default(80);

            $table->boolean('high_activity_alerts')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vera_notification_preferences');
        Schema::dropIfExists('vera_notifications');
    }
};
