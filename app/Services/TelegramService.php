<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private const MAX_LEN = 4000;

    public function sendMessage(string $botToken, int|string $chatId, string $text): bool
    {
        $chunks = $this->splitText($text);
        $allOk  = true;

        foreach ($chunks as $chunk) {
            try {
                $response = Http::timeout(10)->post(
                    "https://api.telegram.org/bot{$botToken}/sendMessage",
                    [
                        'chat_id'                  => $chatId,
                        'text'                     => $chunk,
                        // 'Markdown' (no V2) interpreta *negrita*/_cursiva_ sin exigir
                        // escapar cada símbolo especial — el texto de Vera ya viene así.
                        'parse_mode'               => 'Markdown',
                        'disable_web_page_preview' => true,
                    ]
                );

                // Un asterisco o guion bajo suelto (sin pareja) en el texto hace que
                // Telegram rechace el mensaje ENTERO con error 400. Reintentamos sin
                // formato para no perder el mensaje, mejor sin negrita que sin nada.
                if (!$response->successful()) {
                    Log::warning('Telegram: fallo con Markdown, reintentando sin formato', [
                        'chat_id' => $chatId,
                        'status'  => $response->status(),
                    ]);

                    $response = Http::timeout(10)->post(
                        "https://api.telegram.org/bot{$botToken}/sendMessage",
                        [
                            'chat_id'                  => $chatId,
                            'text'                     => $chunk,
                            'disable_web_page_preview' => true,
                        ]
                    );
                }

                if (!$response->successful()) {
                    $allOk = false;
                    Log::error('Telegram sendMessage falló', [
                        'chat_id' => $chatId,
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                $allOk = false;
                Log::error('Telegram sendMessage error de red', [
                    'chat_id' => $chatId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $allOk;
    }

    public function sendMessageWithButton(
        string $botToken,
        int|string $chatId,
        string $text,
        string $buttonLabel,
        string $buttonUrl
    ): bool {
        $markup = json_encode([
            'inline_keyboard' => [[
                ['text' => $buttonLabel, 'url' => $buttonUrl],
            ]],
        ]);

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'parse_mode'               => 'Markdown',
                    'disable_web_page_preview' => true,
                    'reply_markup'             => $markup,
                ]
            );

            if (!$response->successful()) {
                // Reintentamos sin Markdown manteniendo el botón
                $response = Http::timeout(10)->post(
                    "https://api.telegram.org/bot{$botToken}/sendMessage",
                    [
                        'chat_id'      => $chatId,
                        'text'         => $text,
                        'reply_markup' => $markup,
                    ]
                );
            }

            if (!$response->successful()) {
                Log::error('Telegram sendMessageWithButton falló', [
                    'chat_id' => $chatId,
                    'status'  => $response->status(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessageWithButton error de red', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function splitText(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_LEN) {
            return [$text];
        }

        $chunks  = [];
        $current = '';

        foreach (preg_split('/(\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $piece) {
            if (mb_strlen($current . $piece) > self::MAX_LEN) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current  = '';
                }

                while (mb_strlen($piece) > self::MAX_LEN) {
                    $chunks[] = mb_substr($piece, 0, self::MAX_LEN);
                    $piece    = mb_substr($piece, self::MAX_LEN);
                }
            }

            $current .= $piece;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
