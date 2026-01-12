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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Información básica del contacto
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();

            // Estadísticas de interacción
            $table->integer('emails_received')->default(0);  // Emails recibidos desde este contacto
            $table->integer('emails_sent')->default(0);      // Emails enviados a este contacto
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('last_contact_at')->nullable();

            // Clasificación y seguimiento
            $table->enum('priority', ['low', 'normal', 'high', 'vip'])->default('normal');
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_blocked')->default(false);

            // Metadata adicional
            $table->json('metadata')->nullable();  // Para info adicional (foto, redes sociales, etc.)

            $table->timestamps();

            // Índice único para evitar duplicados por usuario
            $table->unique(['user_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
