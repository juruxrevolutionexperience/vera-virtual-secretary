<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paso D — Campos de canales de mensajería en vera_configs.
 *
 * Telegram: cada usuario crea su propio bot via @BotFather.
 *   - telegram_bot_token   → token del bot (ej: 123456:ABCdef...)
 *   - telegram_chat_id     → chat ID personal del dueño para notificaciones de escalado
 *
 * WhatsApp: número conectado en Meta Business.
 *   - whatsapp_phone_number_id  → ID del número en Meta (ej: 123456789)
 *   - whatsapp_access_token     → token de acceso permanente de Meta
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            // Telegram
            $table->string('telegram_bot_token')->nullable()->after('google_token_expires_at')
                ->comment('Token del bot de Telegram creado en @BotFather');
            $table->string('telegram_chat_id')->nullable()->after('telegram_bot_token')
                ->comment('Chat ID personal del owner para notificaciones de escalado');

            // WhatsApp Business
            $table->string('whatsapp_phone_number_id')->nullable()->after('telegram_chat_id')
                ->comment('Phone Number ID del Meta Business (ej: 123456789012345)');
            $table->string('whatsapp_access_token', 512)->nullable()->after('whatsapp_phone_number_id')
                ->comment('Token de acceso de Meta para enviar mensajes via API');
        });
    }

    public function down(): void
    {
        Schema::table('vera_configs', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_bot_token',
                'telegram_chat_id',
                'whatsapp_phone_number_id',
                'whatsapp_access_token',
            ]);
        });
    }
};
