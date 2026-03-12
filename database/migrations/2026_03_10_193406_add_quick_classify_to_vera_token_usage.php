<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Añadir 'quick_classify' al ENUM action_type de vera_token_usage.
     * MySQL no permite ALTER COLUMN en ENUMs con Blueprint directamente,
     * así que usamos SQL raw para modificar solo los valores del ENUM.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vera_token_usage MODIFY COLUMN action_type ENUM(
            'email_analysis',
            'response_generation',
            'chat_interaction',
            'quick_classify'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Eliminar registros con quick_classify antes de revertir el ENUM
        DB::table('vera_token_usage')->where('action_type', 'quick_classify')->delete();

        DB::statement("ALTER TABLE vera_token_usage MODIFY COLUMN action_type ENUM(
            'email_analysis',
            'response_generation',
            'chat_interaction'
        ) NOT NULL");
    }
};
