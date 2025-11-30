<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Cambiar el tipo de columna de ENUM a VARCHAR para soportar cualquier estado
        DB::statement("ALTER TABLE email_accounts MODIFY sync_status VARCHAR(50) DEFAULT 'active'");
    }

    public function down(): void
    {
        // Volver al ENUM original si es necesario
        DB::statement("ALTER TABLE email_accounts MODIFY sync_status ENUM('active', 'paused', 'error') DEFAULT 'active'");
    }
};