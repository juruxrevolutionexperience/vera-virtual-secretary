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
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('employee'); // organization_owner, admin, manager, secretary, administrative, employee
            $table->boolean('is_primary')->default(false); // Organización principal del usuario
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices compuestos para mejorar consultas
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['organization_id', 'is_active']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
