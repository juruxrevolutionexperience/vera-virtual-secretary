<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase A — Personalidad rica de Vera.
 *
 * El tono solo no basta para que Vera suene humana. Estos campos le dan
 * carácter propio para que el LLM la use como persona real, no como bot:
 *
 *   - personality_traits      → JSON con expresiones, frases firma, tabúes y vibe
 *   - years_at_business       → "llevo 4 años con Carlos" — da continuidad histórica
 *   - pov_about_business      → punto de vista propio sobre el negocio
 *   - refers_to_owner_as      → cómo llama al dueño ("Carlos", "el doctor", "mi jefe")
 *
 * Todos los campos son nullable para no romper configs existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->json('personality_traits')->nullable()->after('vera_signature')
                ->comment('Expresiones, frases firma, tabúes y vibe de Vera (JSON)');

            $table->unsignedTinyInteger('years_at_business')->nullable()->after('personality_traits')
                ->comment('Años que Vera lleva trabajando en el negocio (para continuidad histórica)');

            $table->text('pov_about_business')->nullable()->after('years_at_business')
                ->comment('Punto de vista propio de Vera sobre el negocio');

            $table->string('refers_to_owner_as', 100)->nullable()->after('pov_about_business')
                ->comment('Cómo llama Vera al dueño (Carlos, el doctor, mi jefe...)');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn([
                'personality_traits',
                'years_at_business',
                'pov_about_business',
                'refers_to_owner_as',
            ]);
        });
    }
};
