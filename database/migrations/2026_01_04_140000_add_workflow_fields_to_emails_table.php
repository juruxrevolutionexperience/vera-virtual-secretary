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
            // Workflow status
            $table->enum('workflow_status', [
                'unread',                // Sin leer (nuevo)
                'pending_validation',    // Pendiente de validación
                'pending_response',      // Requiere respuesta
                'in_progress',          // En curso
                'in_review',            // En revisión
                'approved',             // Aprobado
                'completed',            // Completado
                'archived',             // Archivado
                'no_action_needed'      // No requiere acción
            ])->default('unread')->after('vera_processed');

            // Gestión de borradores y papelera
            $table->boolean('is_draft')->default(false)->after('workflow_status');
            $table->boolean('is_deleted')->default(false)->after('is_draft');
            $table->timestamp('deleted_at_custom')->nullable()->after('is_deleted');

            // Fechas y seguimiento
            $table->timestamp('due_date')->nullable()->after('deleted_at_custom');
            $table->timestamp('snoozed_until')->nullable()->after('due_date');
            $table->timestamp('last_activity_at')->nullable()->after('snoozed_until');

            // Acciones requeridas
            $table->boolean('requires_action')->default(false)->after('last_activity_at');
            $table->text('action_notes')->nullable()->after('requires_action');

            // Índices para búsqueda eficiente
            $table->index('workflow_status');
            $table->index('is_draft');
            $table->index('is_deleted');
            $table->index('due_date');
            $table->index('snoozed_until');
            $table->index('requires_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex(['workflow_status']);
            $table->dropIndex(['is_draft']);
            $table->dropIndex(['is_deleted']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['snoozed_until']);
            $table->dropIndex(['requires_action']);

            $table->dropColumn([
                'workflow_status',
                'is_draft',
                'is_deleted',
                'deleted_at_custom',
                'due_date',
                'snoozed_until',
                'last_activity_at',
                'requires_action',
                'action_notes',
            ]);
        });
    }
};
