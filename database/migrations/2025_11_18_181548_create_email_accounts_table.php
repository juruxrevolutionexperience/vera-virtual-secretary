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
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Información de la cuenta
            $table->enum('provider', ['gmail', 'outlook']); // Proveedor de email
            $table->string('email_address')->unique(); // Email de la cuenta conectada
            $table->string('display_name')->nullable(); // Nombre para mostrar

            // OAuth2 Tokens (encriptados)
            $table->text('access_token'); // Token de acceso encriptado
            $table->text('refresh_token')->nullable(); // Token de refresco encriptado
            $table->timestamp('token_expires_at')->nullable(); // Cuándo expira el token

            // Configuración de sincronización
            $table->boolean('sync_enabled')->default(true); // Si está activa la sync
            $table->integer('sync_frequency')->default(5); // Minutos entre sincronizaciones
            $table->timestamp('last_sync_at')->nullable(); // Última sincronización exitosa
            $table->timestamp('last_sync_attempt_at')->nullable(); // Último intento

            // Estado de sincronización
            $table->enum('sync_status', ['active', 'error', 'paused'])->default('active');
            $table->text('sync_error')->nullable(); // Último error de sincronización
            $table->integer('total_emails_synced')->default(0); // Total de emails sincronizados

            // Metadata del proveedor
            $table->string('provider_user_id')->nullable(); // ID del usuario en el proveedor
            $table->json('provider_metadata')->nullable(); // Datos adicionales del proveedor

            // Configuraciones avanzadas
            $table->boolean('sync_sent')->default(true); // Sincronizar emails enviados
            $table->boolean('sync_drafts')->default(true); // Sincronizar borradores
            $table->boolean('sync_trash')->default(false); // Sincronizar papelera
            $table->date('sync_from_date')->nullable(); // Desde qué fecha sincronizar

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'sync_enabled']);
            $table->index(['provider', 'email_address']);
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
