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
        Schema::create('email_folder_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_folder_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_with')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->enum('permission', ['view', 'edit', 'delete'])->default('view');
            $table->boolean('can_share')->default(false); // Puede compartir con otros
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Índices
            $table->unique(['email_folder_id', 'shared_with']);
            $table->index('shared_with');
            $table->index('shared_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_folder_shares');
    }
};
