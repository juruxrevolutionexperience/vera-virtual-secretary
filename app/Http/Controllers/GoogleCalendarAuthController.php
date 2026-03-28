<?php

namespace App\Http\Controllers;

use App\Models\VeraConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * GoogleCalendarAuthController
 *
 * Sprint 2 — OAuth con Google para acceder a Google Calendar.
 * Scopes: calendar (leer/escribir) + calendar.events (crear/editar eventos)
 *
 * Flujo:
 *   1. Usuario hace clic en "Conectar Google Calendar" en /vera/config
 *   2. redirect() → Google OAuth con los scopes de Calendar
 *   3. Google redirige a callback() con el código de autorización
 *   4. Se guarda access_token + refresh_token en vera_configs del usuario
 */
class GoogleCalendarAuthController extends Controller
{
    /**
     * Scopes necesarios para gestionar Calendar completamente.
     * - calendar: leer disponibilidad, listar calendarios
     * - calendar.events: crear, modificar y cancelar eventos
     * - userinfo.email: identificar al usuario tras el OAuth
     */
    private const SCOPES = [
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    /**
     * Redirige al usuario a Google para autorizar acceso a Calendar.
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(self::SCOPES)
            ->with([
                'access_type'   => 'offline',  // Necesario para recibir refresh_token
                'prompt'        => 'consent',   // Fuerza mostrar pantalla de consentimiento (obtiene refresh_token siempre)
            ])
            ->redirect();
    }

    /**
     * Callback tras autorización de Google.
     * Guarda los tokens en vera_configs del usuario autenticado.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::warning('Google Calendar OAuth cancelado', [
                'user_id' => Auth::id(),
                'error'   => $request->get('error'),
            ]);

            $config = VeraConfig::where('user_id', Auth::id())->first();
            $route  = ($config && $config->onboarding_completed) ? 'vera.config' : 'vera.onboarding';

            return redirect()->route($route)
                ->with('error', 'Autorización cancelada. Puedes intentarlo de nuevo cuando quieras.');
        }

        try {
            $googleUser = Socialite::driver('google')
                ->scopes(self::SCOPES)
                ->stateless()
                ->user();

            $user = Auth::user();

            // Guardar tokens en vera_configs
            $config = VeraConfig::firstOrCreate(['user_id' => $user->id]);

            $config->update([
                'google_calendar_id'      => $googleUser->getId(),
                'google_access_token'     => $googleUser->token,
                'google_refresh_token'    => $googleUser->refreshToken ?? $config->google_refresh_token,
                'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
            ]);

            Log::info('✅ Google Calendar conectado', [
                'user_id'     => $user->id,
                'google_id'   => $googleUser->getId(),
                'has_refresh' => !empty($googleUser->refreshToken),
            ]);

            // Si el onboarding no está completado, volver al paso 4
            if (!$config->onboarding_completed) {
                return redirect()->route('vera.onboarding')
                    ->with('success', '¡Google Calendar conectado! Ahora pulsa "Empezar con Vera" para terminar.');
            }

            return redirect()->route('vera.config')
                ->with('success', '¡Google Calendar conectado correctamente! Vera ya puede gestionar tu agenda.');

        } catch (\Exception $e) {
            Log::error('❌ Error en Google Calendar OAuth callback', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('vera.config')
                ->with('error', 'Error al conectar con Google Calendar. Inténtalo de nuevo.');
        }
    }

    /**
     * Desconectar Google Calendar — elimina los tokens del usuario.
     */
    public function disconnect()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        if ($config) {
            $config->update([
                'google_calendar_id'      => null,
                'google_access_token'     => null,
                'google_refresh_token'    => null,
                'google_token_expires_at' => null,
            ]);
        }

        Log::info('Google Calendar desconectado', ['user_id' => $user->id]);

        return redirect()->route('vera.config')
            ->with('success', 'Google Calendar desconectado.');
    }
}
