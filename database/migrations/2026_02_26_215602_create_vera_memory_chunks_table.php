<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vera_memory_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Tipo de chunk
            $table->enum('type', [
                'business_info',       // Qué hace la empresa
                'faq',                 // Preguntas frecuentes y respuestas
                'communication_style', // Cómo escribe el usuario
                'past_response',       // Respuesta exitosa anterior (para aprender)
                'weekly_insight',      // Resumen semanal generado automáticamente
                'contact_profile',     // Perfil de un contacto específico
                'product_info',        // Info de productos/servicios
                'policy',              // Políticas: precios, devoluciones, plazos
            ]);

            $table->string('title');        // Nombre descriptivo del chunk
            $table->text('content');        // El texto del chunk (máx ~500 palabras)

            // Para búsqueda por relevancia (FULLTEXT en MySQL)
            $table->text('keywords_text')->nullable(); // "presupuesto precio tarifa coste" — para FULLTEXT

            // JSON: tags para matching con tipo de email
            $table->json('relevance_tags')->nullable(); // ["quote_request","pricing","budget"]

            // Metadatos
            $table->string('source')->default('manual'); // onboarding | user_config | auto_detected | manual
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'type', 'is_active']);
            $table->index(['user_id', 'usage_count']);
        });

        // Índice FULLTEXT para búsqueda semántica en MySQL
        DB::statement('ALTER TABLE vera_memory_chunks ADD FULLTEXT INDEX ft_keywords (keywords_text, title)');
        DB::statement('ALTER TABLE vera_memory_chunks ADD FULLTEXT INDEX ft_content (content)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vera_memory_chunks');
    }
};
