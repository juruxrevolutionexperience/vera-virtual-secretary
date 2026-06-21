<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VeraConfig;
use App\Services\TelegramService;
use App\Services\TranscriptionService;
use App\Services\UnsupportedMessageResponder;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Descarga una nota de voz (WhatsApp/Telegram), la transcribe con Groq Whisper y
 * mete el texto en el flujo normal de Vera (ProcessIncomingMessage). Si la descarga
 * o la transcripción fallan, responde "prefiero que me escribas" como red de seguridad.
 *
 * Va en cola para no bloquear el webhook (la descarga + transcripción tardan unos segundos).
 */
class ProcessVoiceMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 60;

    public function __construct(
        public readonly int     $userId,
        public readonly string  $channel,            // whatsapp | telegram
        public readonly string  $mediaRef,           // media_id (WhatsApp) o file_id (Telegram)
        public readonly string  $contactIdentifier,
        public readonly ?string $contactName = null,
        public readonly array   $metadata = []
    ) {}

    public function handle(TranscriptionService $transcriber): void
    {
        $user   = User::find($this->userId);
        $config = $user?->veraConfig;
        if (!$user || !$config) {
            return;
        }

        $audio = $this->downloadAudio($config);
        $text  = $audio ? $transcriber->transcribe($audio, 'voice.ogg') : null;

        if (!$text) {
            $this->sendFallback($config);
            return;
        }

        Log::info('🎤 Nota de voz transcrita', [
            'user_id' => $user->id,
            'channel' => $this->channel,
            'length'  => strlen($text),
        ]);

        // El texto transcrito entra al flujo normal: clasifica, agenda, responde igual
        // que si el cliente lo hubiera escrito.
        ProcessIncomingMessage::dispatchWithDelay(
            userId:            $this->userId,
            channel:           $this->channel,
            message:           $text,
            contactIdentifier: $this->contactIdentifier,
            contactName:       $this->contactName,
            metadata:          array_merge($this->metadata, ['transcribed' => true]),
        );
    }

    /**
     * Descarga los bytes del audio según el canal (necesita el token del negocio).
     */
    private function downloadAudio(VeraConfig $config): ?string
    {
        try {
            if ($this->channel === 'whatsapp') {
                $version = config('services.whatsapp.api_version', 'v25.0');
                $token   = $config->whatsapp_access_token;
                // 1) media_id → URL temporal
                $meta = Http::withToken($token)->timeout(15)
                    ->get("https://graph.facebook.com/{$version}/{$this->mediaRef}");
                $url = $meta->json('url');
                if (!$url) {
                    return null;
                }
                // 2) descargar bytes (la URL de Meta también requiere el token)
                $file = Http::withToken($token)->timeout(30)->get($url);
                return $file->successful() ? $file->body() : null;
            }

            if ($this->channel === 'telegram') {
                $token = $config->telegram_bot_token;
                $info  = Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getFile", ['file_id' => $this->mediaRef]);
                $path  = $info->json('result.file_path');
                if (!$path) {
                    return null;
                }
                $file = Http::timeout(30)->get("https://api.telegram.org/file/bot{$token}/{$path}");
                return $file->successful() ? $file->body() : null;
            }
        } catch (\Throwable $e) {
            Log::warning('Voz: fallo descargando audio', ['error' => $e->getMessage(), 'channel' => $this->channel]);
        }

        return null;
    }

    /**
     * Si no se pudo transcribir, mandamos la respuesta de "prefiero que me escribas".
     */
    private function sendFallback(VeraConfig $config): void
    {
        $text = UnsupportedMessageResponder::build($config, 'audio')['text'];

        if ($this->channel === 'whatsapp') {
            (new WhatsAppService())->sendText(
                $config->whatsapp_phone_number_id,
                $config->whatsapp_access_token,
                $this->contactIdentifier,
                $text
            );
        } elseif ($this->channel === 'telegram') {
            $chatId = $this->metadata['chat_id'] ?? $this->contactIdentifier;
            (new TelegramService())->sendMessage($config->telegram_bot_token, $chatId, $text);
        }
    }
}
