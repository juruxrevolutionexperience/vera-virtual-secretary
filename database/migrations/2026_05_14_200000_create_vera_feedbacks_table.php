<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vera_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('category', [
                'respuesta_mala',
                'respuesta_buena',
                'bug_web',
                'bug_calendar',
                'sugerencia',
                'otro',
            ])->index();
            $table->text('message');
            $table->string('context_url')->nullable();
            $table->foreignId('conversation_id')->nullable()
                  ->constrained('vera_conversations')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->enum('status', ['nuevo', 'revisado', 'resuelto', 'descartado'])
                  ->default('nuevo')
                  ->index();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vera_feedbacks');
    }
};
