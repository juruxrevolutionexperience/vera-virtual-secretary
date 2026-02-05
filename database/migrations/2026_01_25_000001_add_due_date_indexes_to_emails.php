<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear índices solo si no existen
        $this->createIndexIfNotExists('emails_due_date_index', 'emails', ['due_date']);
        $this->createIndexIfNotExists('emails_snoozed_until_index', 'emails', ['snoozed_until']);
        $this->createIndexIfNotExists('emails_due_workflow_index', 'emails', ['due_date', 'workflow_status']);
        $this->createIndexIfNotExists('emails_snooze_workflow_index', 'emails', ['snoozed_until', 'workflow_status']);
    }

    /**
     * Helper para crear índice solo si no existe
     */
    private function createIndexIfNotExists(string $indexName, string $tableName, array $columns): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
        } catch (\Exception $e) {
            // Si el índice ya existe, ignorar el error
            if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }
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
