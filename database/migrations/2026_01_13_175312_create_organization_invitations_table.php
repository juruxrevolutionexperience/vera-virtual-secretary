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
        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('email'); // Email del invitado
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('employee'); // Rol propuesto
            $table->string('token')->unique(); // Token de validación
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->text('message')->nullable(); // Mensaje personalizado
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['organization_id', 'email']);
            $table->index(['email', 'status']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};
