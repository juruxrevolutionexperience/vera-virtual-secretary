<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorAuthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TwoFactorAuthController extends Controller
{
    public function __construct(protected TwoFactorAuthService $twoFactorService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $recoveryCodes = $user->two_factor_enabled
            ? $this->twoFactorService->getRecoveryCodes($user)
            : null;

        return Inertia::render('Security/TwoFactor', [
            'enabled' => $user->two_factor_enabled,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function generateQr(Request $request)
    {
        return response()->json([
            'qr_code_url' => $this->twoFactorService->getQRCodeUrl($request->user()),
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();

        if (!$this->twoFactorService->enable($user, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        return back()->with([
            'success' => '2FA habilitado.',
            'recovery_codes' => $this->twoFactorService->getRecoveryCodes($user),
        ]);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $this->twoFactorService->disable($request->user());
        return back()->with('success', '2FA deshabilitado.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return back()->withErrors(['error' => '2FA no habilitado.']);
        }

        return back()->with([
            'success' => 'Códigos regenerados.',
            'recovery_codes' => $this->twoFactorService->regenerateRecoveryCodes($user),
        ]);
    }
}
