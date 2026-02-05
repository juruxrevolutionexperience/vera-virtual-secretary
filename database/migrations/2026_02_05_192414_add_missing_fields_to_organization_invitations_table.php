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
        Schema::table('organization_invitations', function (Blueprint $table) {
            // Agregar campos faltantes solo si no existen
            if (!Schema::hasColumn('organization_invitations', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('organization_invitations', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('declined_at');
            }
        });

        // Modificar el enum de status para incluir 'declined' y 'cancelled'
        DB::statement("ALTER TABLE organization_invitations MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'declined', 'cancelled', 'expired') DEFAULT 'pending'");

        // Modificar el default de role de 'employee' a 'member'
        DB::statement("ALTER TABLE organization_invitations MODIFY COLUMN role VARCHAR(255) DEFAULT 'member'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table) {
            $table->dropColumn(['declined_at', 'cancelled_at']);
        });

        // Revertir el enum de status
        DB::statement("ALTER TABLE organization_invitations MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending'");

        // Revertir el default de role
        DB::statement("ALTER TABLE organization_invitations MODIFY COLUMN role VARCHAR(255) DEFAULT 'employee'");
    }
};
