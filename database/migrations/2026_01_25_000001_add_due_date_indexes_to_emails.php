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
            // Índice simple para due_date (búsquedas y ordenamiento)
            $table->index('due_date', 'emails_due_date_index');

            // Índice simple para snoozed_until (wakeup command)
            $table->index('snoozed_until', 'emails_snoozed_until_index');

            // Índice compuesto para filtros de due dates (overdue, due today, etc)
            // Usado en queries: WHERE due_date IS NOT NULL AND workflow_status != 'completed'
            $table->index(['due_date', 'workflow_status'], 'emails_due_workflow_index');

            // Índice compuesto para queries de snooze activos
            // Usado en scope snoozed()
            $table->index(['snoozed_until', 'workflow_status'], 'emails_snooze_workflow_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex('emails_due_date_index');
            $table->dropIndex('emails_snoozed_until_index');
            $table->dropIndex('emails_due_workflow_index');
            $table->dropIndex('emails_snooze_workflow_index');
        });
    }
};
