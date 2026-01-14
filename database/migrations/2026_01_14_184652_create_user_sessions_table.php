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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique(); // Laravel session ID
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Información del dispositivo (usando jenssegers/agent)
            $table->string('device_type')->nullable(); // desktop, mobile, tablet, robot
            $table->string('device_name')->nullable(); // iPhone, Samsung Galaxy, etc.
            $table->string('platform')->nullable(); // iOS, Android, Windows, Mac, Linux
            $table->string('browser')->nullable(); // Chrome, Firefox, Safari, etc.

            // Ubicación (opcional - puedes agregar GeoIP más adelante)
            $table->string('country')->nullable();
            $table->string('city')->nullable();

            // Seguridad
            $table->boolean('is_current')->default(false); // Sesión actual
            $table->boolean('is_trusted')->default(false); // Dispositivo confiable
            $table->timestamp('last_activity')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable(); // manual, expired, security, logout_all

            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['user_id', 'is_current']);
            $table->index(['user_id', 'last_activity']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
