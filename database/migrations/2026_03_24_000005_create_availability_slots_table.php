<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 — Slots de disponibilidad del usuario.
 * Complementa working_hours (horario general) con bloqueos o aperturas puntuales.
 * Ejemplo: lunes 9-18 por defecto, pero bloquear 14-16 un día concreto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Día específico o patrón semanal
            $table->date('date')->nullable()
                ->comment('Fecha concreta si es una excepción puntual. null = patrón semanal.');
            $table->tinyInteger('day_of_week')->nullable()
                ->comment('0=Dom, 1=Lun, 2=Mar, 3=Mie, 4=Jue, 5=Vie, 6=Sab. Solo si date es null.');

            // Rango horario
            $table->time('start_time');
            $table->time('end_time');

            // ¿Disponible o bloqueado?
            $table->boolean('is_available')->default(true)
                ->comment('true = disponible, false = bloqueado (vacaciones, reunión fija, etc.)');

            // Descripción opcional
            $table->string('label')->nullable()
                ->comment('Ej: Vacaciones, Formación interna, Disponible extra');

            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
    }
};
