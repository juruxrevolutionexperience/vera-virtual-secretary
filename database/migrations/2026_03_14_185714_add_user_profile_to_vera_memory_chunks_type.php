<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vera_memory_chunks MODIFY type ENUM(
            'business_info',
            'faq',
            'communication_style',
            'past_response',
            'weekly_insight',
            'contact_profile',
            'product_info',
            'policy',
            'user_profile'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vera_memory_chunks MODIFY type ENUM(
            'business_info',
            'faq',
            'communication_style',
            'past_response',
            'weekly_insight',
            'contact_profile',
            'product_info',
            'policy'
        ) NOT NULL");
    }
};
