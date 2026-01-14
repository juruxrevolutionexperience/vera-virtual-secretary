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
        Schema::create('password_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'default', 'admin', 'custom'
            $table->boolean('is_active')->default(false);

            // Requisitos de complejidad
            $table->integer('min_length')->default(8);
            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_numbers')->default(true);
            $table->boolean('require_special_chars')->default(true);
            $table->string('allowed_special_chars')->default('!@#$%^&*()_+-=[]{}|;:,.<>?');

            // Expiración
            $table->boolean('expires')->default(false);
            $table->integer('expiry_days')->nullable(); // null = nunca expira
            $table->integer('expiry_warning_days')->default(7); // Avisar X días antes

            // Historial
            $table->boolean('prevent_reuse')->default(true);
            $table->integer('history_count')->default(5); // No reutilizar últimas X contraseñas

            // Bloqueo por intentos fallidos
            $table->boolean('lockout_enabled')->default(true);
            $table->integer('max_attempts')->default(5);
            $table->integer('lockout_duration_minutes')->default(30);

            // Otras configuraciones
            $table->boolean('force_change_first_login')->default(false);
            $table->text('description')->nullable();

            $table->timestamps();
        });

        // Crear política por defecto
        DB::table('password_policies')->insert([
            'name' => 'default',
            'is_active' => true,
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => false,
            'expires' => false,
            'prevent_reuse' => true,
            'history_count' => 5,
            'lockout_enabled' => true,
            'max_attempts' => 5,
            'lockout_duration_minutes' => 30,
            'description' => 'Política de contraseñas por defecto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_policies');
    }
};
