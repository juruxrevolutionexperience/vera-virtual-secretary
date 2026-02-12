<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorAuthService;
use Illuminate\Http\Request;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuthController extends Controller
{
    public function __construct(protected TwoFactorAuthService $twoFactorService) {}

    /**
     * Vista de configuración 2FA
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $recoveryCodes = $user->two_factor_enabled
            ? $this->twoFactorService->getRecoveryCodes($user)
            : null;

        return view('security.two-factor', [
            'enabled'       => (bool) $user->two_factor_enabled,
            'confirmed'     => $user->two_factor_confirmed_at !== null,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Generar QR code SVG + secret para el setup inicial
     */
    public function generateQr(Request $request)
    {
        $user = $request->user();
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user);

        // Generar SVG del QR
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'qr_svg'     => $svg,
            'secret_key' => decrypt($user->two_factor_secret),
        ]);
    }

    /**
     * Activar 2FA — verificar código OTP y guardar
     */
    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|string|digits:6']);
        $user = $request->user();

        if (!$this->twoFactorService->enable($user, $request->code)) {
            return response()->json(['message' => 'Código inválido. Inténtalo de nuevo.'], 422);
        }

        $user->update(['two_factor_confirmed_at' => now()]);

        return response()->json([
            'message'        => '2FA activado correctamente.',
            'recovery_codes' => $this->twoFactorService->getRecoveryCodes($user),
        ]);
    }

    /**
     * Desactivar 2FA
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $this->twoFactorService->disable($request->user());

        return response()->json(['message' => '2FA desactivado correctamente.']);
    }

    /**
     * Regenerar recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json(['message' => '2FA no está activado.'], 422);
        }

        $codes = $this->twoFactorService->regenerateRecoveryCodes($user);

        return response()->json([
            'message'        => 'Códigos de recuperación regenerados.',
            'recovery_codes' => $codes,
        ]);
    }

    // =========================================================
    // CHALLENGE: pantalla de verificación durante el login
    // =========================================================

    /**
     * Mostrar pantalla de verificación 2FA
     */
    public function showChallenge(Request $request)
    {
        // Si no hay usuario pendiente de 2FA en sesión → redirigir a login
        if (!$request->session()->has('2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('security.two-factor-challenge');
    }

    /**
     * Verificar código 2FA durante el login
     */
    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code'          => 'nullable|string|digits:6',
            'recovery_code' => 'nullable|string',
        ]);

        $userId = $request->session()->get('2fa.user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);
        $verified = false;

        if ($request->filled('recovery_code')) {
            $verified = $this->twoFactorService->verifyRecoveryCode($user, $request->recovery_code);
        } elseif ($request->filled('code')) {
            $verified = $this->twoFactorService->verify($user, $request->code);
        }

        if (!$verified) {
            return back()->withErrors(['code' => 'Código incorrecto. Inténtalo de nuevo.']);
        }

        // Limpiar sesión temporal y hacer login completo
        $request->session()->forget('2fa.user_id');
        auth()->login($user);

        return redirect()->intended(route('emails.index'));
    }
}
