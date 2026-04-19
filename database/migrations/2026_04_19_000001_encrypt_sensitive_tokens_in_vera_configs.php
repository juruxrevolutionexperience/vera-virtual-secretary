<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tokens que deben estar cifrados en reposo
    private array $columns = [
        'google_access_token',
        'google_refresh_token',
        'telegram_bot_token',
        'whatsapp_access_token',
    ];

    public function up(): void
    {
        // Ampliar columnas a TEXT — los valores cifrados superan los 255 chars de string
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->text('google_access_token')->nullable()->change();
            $table->text('google_refresh_token')->nullable()->change();
            $table->text('telegram_bot_token')->nullable()->change();
            $table->text('whatsapp_access_token')->nullable()->change();
        });

        // Leer con DB directamente (sin Eloquent) para obtener valores en texto plano
        DB::table('vera_configs')->whereNotNull('google_access_token')
            ->orWhereNotNull('google_refresh_token')
            ->orWhereNotNull('telegram_bot_token')
            ->orWhereNotNull('whatsapp_access_token')
            ->get()
            ->each(function ($config) {
                $updates = [];
                foreach ($this->columns as $col) {
                    $value = $config->$col ?? null;
                    if ($value === null) continue;

                    // Si ya está cifrado (empieza por "eyJ") lo dejamos
                    if (str_starts_with($value, 'eyJ')) continue;

                    try {
                        $updates[$col] = Crypt::encryptString($value);
                    } catch (\Exception) {
                        // Si falla el cifrado, dejar como está antes de romper datos
                    }
                }

                if (!empty($updates)) {
                    DB::table('vera_configs')->where('id', $config->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        // Descifrar de vuelta a texto plano y reducir columnas
        DB::table('vera_configs')->get()->each(function ($config) {
            $updates = [];
            foreach ($this->columns as $col) {
                $value = $config->$col ?? null;
                if ($value === null) continue;

                try {
                    $updates[$col] = Crypt::decryptString($value);
                } catch (\Exception) {
                    // Ya estaba en texto plano
                }
            }

            if (!empty($updates)) {
                DB::table('vera_configs')->where('id', $config->id)->update($updates);
            }
        });

        Schema::table('vera_configs', function (Blueprint $table) {
            $table->string('google_access_token')->nullable()->change();
            $table->string('google_refresh_token')->nullable()->change();
            $table->string('telegram_bot_token')->nullable()->change();
            $table->string('whatsapp_access_token', 512)->nullable()->change();
        });
    }
};
