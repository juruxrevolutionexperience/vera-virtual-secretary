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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_type'); // login, logout, email_read, email_sent, settings_changed, etc.
            $table->string('event_category')->default('general'); // auth, email, settings, data, security
            $table->string('auditable_type')->nullable(); // Modelo afectado (Email, User, etc.)
            $table->unsignedBigInteger('auditable_id')->nullable(); // ID del registro afectado
            $table->text('description'); // Descripción legible del evento
            $table->json('old_values')->nullable(); // Valores antiguos (para updates)
            $table->json('new_values')->nullable(); // Valores nuevos (para updates)
            $table->json('metadata')->nullable(); // Datos adicionales (IP, user agent, etc.)
            $table->string('ip_address', 45)->nullable(); // IPv4 o IPv6
            $table->text('user_agent')->nullable();
            $table->string('action_result')->default('success'); // success, failed, blocked
            $table->string('severity')->default('info'); // info, warning, critical
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['event_category', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
