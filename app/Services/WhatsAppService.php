<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de mensajes por la WhatsApp Cloud API (Meta Graph).
 *
 * IMPORTANTE: el envío de texto libre solo funciona DENTRO de la ventana de 24h
 * desde el último mensaje del cliente. Fuera de esa ventana, Meta exige plantillas
 * aprobadas (template messages) — eso es para los recordatorios proactivos y se
 * implementará aparte. Las RESPUESTAS de Vera (reactivas) caen siempre dentro de la
 * ventana, así que el texto libre vale.
 */
class WhatsAppService
{
    private string $version;

    public function __construct()
    {
        // Versión del Graph API — configurable por si Meta la actualiza.
        $this->version = config('services.whatsapp.api_version', 'v21.0');
    }

    /**
     * Envía un mensaje de texto. Devuelve true si Meta lo aceptó.
     *
     * @param string|null $phoneNumberId  ID del número del negocio (de VeraConfig)
     * @param string|null $accessToken    Token de acceso del negocio (cifrado en BD)
     * @param string      $to             Teléfono del cliente (wa_id, formato E.164 sin +)
     * @param string      $body           Texto a enviar
     */
    public function sendText(?string $phoneNumberId, ?string $accessToken, string $to, string $body): bool
    {
        if (!$phoneNumberId || !$accessToken || trim($body) === '' || trim($to) === '') {
            Log::warning('WhatsApp: faltan datos para enviar', ['has_pnid' => (bool) $phoneNumberId, 'to' => $to]);
            return false;
        }

        try {
            $res = Http::withToken($accessToken)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$this->version}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => [
                        'preview_url' => true,            // hace clicables los enlaces (reservas/cancelación)
                        'body'        => mb_substr($body, 0, 4096), // límite de WhatsApp
                    ],
                ]);

            if ($res->successful()) {
                return true;
            }

            Log::warning('WhatsApp: Meta rechazó el envío', [
                'status' => $res->status(),
                'error'  => $res->json('error.message') ?? $res->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: excepción al enviar', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
