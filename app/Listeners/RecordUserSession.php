<?php

namespace App\Listeners;

use App\Models\UserSession;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class RecordUserSession
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $request = Request::instance();

        // Parsear información del navegador/dispositivo
        $userAgent = $request->userAgent() ?? 'Unknown';
        $parsedAgent = UserSession::parseUserAgent($userAgent);

        // Marcar todas las sesiones anteriores como no actuales
        UserSession::where('user_id', $user->id)
            ->update(['is_current' => false]);

        // Crear nueva sesión
        $ip = $request->ip();
        $locationParts = $this->getLocationFromIp($ip);
        UserSession::create([
            'user_id'       => $user->id,
            'session_id'    => Session::getId(),
            'ip_address'    => $ip,
            'user_agent'    => $userAgent,
            'device_type'   => $parsedAgent['device_type'],
            'browser'       => $parsedAgent['browser'],
            'platform'      => $parsedAgent['platform'],
            'city'          => $locationParts['city'],
            'country'       => $locationParts['country'],
            'last_activity' => now(),
            'expires_at'    => now()->addMinutes(config('session.lifetime', 120)),
            'is_current'    => true,
        ]);
    }

    /**
     * Obtener ubicación aproximada desde IP
     */
    protected function getLocationFromIp(string $ip): array
    {
        // IPs locales / Docker
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost']) || str_starts_with($ip, '172.') || str_starts_with($ip, '10.')) {
            return ['city' => 'Desarrollo local', 'country' => null];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'city'    => isset($data['city'], $data['region']) ? $data['city'] . ', ' . $data['region'] : ($data['city'] ?? null),
                    'country' => $data['country_code'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Silencioso
        }

        return ['city' => null, 'country' => null];
    }
}
