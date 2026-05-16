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
                        'disable_web_page_preview' => true,
                    ]
                );

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
