<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consultas que Vera no supo responder (Fase C de Bandeja 2.0).
 *
 * Cada vez que un cliente pregunta algo que no está en el cerebro de Vera, además
 * del aviso al dueño se crea una consulta gestionable: aparece como tarjeta en la
 * Bandeja, el dueño la responde vía Vera (o la descarta) y, si quiere, la guarda
 * como FAQ para que la próxima vez Vera conteste sola.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vera_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('contact_identifier');
            $table->string('contact_name')->nullable();
            $table->string('channel', 20);
            $table->text('question');
            $table->text('answer')->nullable();
            $table->enum('status', ['pending', 'answered', 'dismissed'])->default('pending');
            $table->foreignId('meeting_request_id')->nullable()->constrained('meeting_requests')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vera_inquiries');
    }
};
