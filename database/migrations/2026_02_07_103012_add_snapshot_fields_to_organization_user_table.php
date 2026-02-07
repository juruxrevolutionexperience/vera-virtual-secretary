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
        Schema::table('organization_user', function (Blueprint $table) {
            // Snapshot de datos del usuario (para auditoría)
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('user_email')->nullable()->after('user_name');

            // Información de remoción
            $table->unsignedBigInteger('removed_by')->nullable()->after('left_at');
            $table->text('removal_reason')->nullable()->after('removed_by');

            // Índices para búsquedas
            $table->index('removed_by');
            $table->index('user_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropIndex(['removed_by']);
            $table->dropIndex(['user_email']);

            $table->dropColumn([
                'user_name',
                'user_email',
                'removed_by',
                'removal_reason',
            ]);
        });
    }
};
