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
        // Añadir organization_id a emails
        Schema::table('emails', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a email_folders
        Schema::table('email_folders', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a contacts
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a email_alerts
        Schema::table('email_alerts', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a escalation_rules
        Schema::table('escalation_rules', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a follow_up_reminders
        Schema::table('follow_up_reminders', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a email_templates
        Schema::table('email_templates', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a scheduled_emails
        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a auto_responders
        Schema::table('auto_responders', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a email_categories
        Schema::table('email_categories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });

        // Añadir organization_id a email_rules
        Schema::table('email_rules', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_folders', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_alerts', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('escalation_rules', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('follow_up_reminders', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('auto_responders', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_categories', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_rules', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
