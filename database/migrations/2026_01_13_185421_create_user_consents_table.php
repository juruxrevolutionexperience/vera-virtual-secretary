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
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('consent_type'); // terms_of_service, privacy_policy, cookies, marketing, data_processing
            $table->string('consent_version'); // Versión del documento (ej: "1.0.0", "2024-01-13")
            $table->boolean('is_granted')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable(); // Notas adicionales sobre el consentimiento
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'consent_type']);
            $table->index(['consent_type', 'is_granted']);
            $table->unique(['user_id', 'consent_type', 'consent_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
