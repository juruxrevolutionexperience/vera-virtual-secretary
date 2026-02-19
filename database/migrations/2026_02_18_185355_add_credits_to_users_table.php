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
        Schema::table('users', function (Blueprint $table) {
            // Tokens mensuales usados (se resetea cada mes via scheduler)
            $table->unsignedBigInteger('monthly_tokens_used')
                ->default(0)
                ->after('plan_type');

            // Límite mensual según plan (0 = ilimitado en Enterprise)
            $table->unsignedBigInteger('monthly_tokens_limit')
                ->default(50000)
                ->after('monthly_tokens_used');

            // Fecha del último reset mensual
            $table->date('tokens_reset_at')
                ->nullable()
                ->after('monthly_tokens_limit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_tokens_used',
                'monthly_tokens_limit',
                'tokens_reset_at',
            ]);
        });
    }
};
