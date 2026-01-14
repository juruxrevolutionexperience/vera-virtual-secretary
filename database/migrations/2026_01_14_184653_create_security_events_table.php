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
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // Puede ser null para eventos sin usuario
            $table->string('event_type'); // login_failed, login_success, password_changed, 2fa_enabled, etc.
            $table->string('severity')->default('info'); // info, warning, danger, critical
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Información adicional del evento
            $table->boolean('requires_action')->default(false); // Si requiere acción del admin
            $table->timestamp('actioned_at')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users'); // Admin que tomó acción
            $table->timestamps();

            // Índices para búsquedas y queries
            $table->index(['user_id', 'event_type']);
            $table->index(['severity', 'created_at']);
            $table->index('requires_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
