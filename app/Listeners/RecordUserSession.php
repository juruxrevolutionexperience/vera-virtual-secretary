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
        UserSession::create([
            'user_id' => $user->id,
            'session_id' => Session::getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $parsedAgent['device_type'],
            'browser' => $parsedAgent['browser'],
            'platform' => $parsedAgent['platform'],
            'location' => $this->getLocationFromIp($request->ip()),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
            'is_current' => true,
        ]);
    }

    /**
     * Obtener ubicación aproximada desde IP
     */
    protected function getLocationFromIp(string $ip): ?string
    {
        // Para localhost/desarrollo
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Local Development';
        }

        // TODO: Integrar con servicio como ipinfo.io, ipapi.co, etc.
        return null;
    }
}
