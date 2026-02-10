<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\PasswordPolicyService;
use App\Models\PasswordPolicy;
use App\Models\PasswordHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordPolicyController extends Controller
{
    public function __construct(protected PasswordPolicyService $policyService) {}

    /**
     * Vista principal de políticas de contraseña
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $policy = PasswordPolicy::getActive();
        $passwordHistory = PasswordHistory::getUserHistory($user->id, 10);
        $historyStats = PasswordHistory::getStats($user->id);
        $isExpired = $policy ? $this->policyService->isPasswordExpired($user) : false;
        $daysUntilExpiry = $policy ? $this->policyService->daysUntilExpiry($user) : null;
        $shouldWarn = $policy ? $this->policyService->shouldWarnAboutExpiry($user) : false;

        return view('security.password-policy', compact(
            'policy',
            'passwordHistory',
            'historyStats',
            'isExpired',
            'daysUntilExpiry',
            'shouldWarn'
        ));
    }

    /**
     * Cambiar contraseña del usuario
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        try {
            $this->policyService->changePassword($user, $request->new_password, 'user');
            return back()->with('success', '¡Contraseña actualizada correctamente!');
        } catch (\Exception $e) {
            return back()->withErrors(['new_password' => $e->getMessage()]);
        }
    }

    /**
     * Actualizar política de contraseña (solo admin)
     */
    public function updatePolicy(Request $request)
    {
        $request->validate([
            'min_length'               => 'required|integer|min:6|max:128',
            'require_uppercase'        => 'boolean',
            'require_lowercase'        => 'boolean',
            'require_numbers'          => 'boolean',
            'require_special_chars'    => 'boolean',
            'expires'                  => 'boolean',
            'expiry_days'              => 'nullable|integer|min:1|max:365',
            'expiry_warning_days'      => 'nullable|integer|min:1|max:30',
            'prevent_reuse'            => 'boolean',
            'history_count'            => 'nullable|integer|min:1|max:20',
            'lockout_enabled'          => 'boolean',
            'max_attempts'             => 'nullable|integer|min:3|max:20',
            'lockout_duration_minutes' => 'nullable|integer|min:5|max:1440',
        ]);

        $policy = PasswordPolicy::getActive();

        if (!$policy) {
            $policy = PasswordPolicy::create(['name' => 'default', 'is_active' => true]);
        }

        $policy->update([
            'min_length'               => $request->min_length,
            'require_uppercase'        => $request->boolean('require_uppercase'),
            'require_lowercase'        => $request->boolean('require_lowercase'),
            'require_numbers'          => $request->boolean('require_numbers'),
            'require_special_chars'    => $request->boolean('require_special_chars'),
            'expires'                  => $request->boolean('expires'),
            'expiry_days'              => $request->expires ? $request->expiry_days : null,
            'expiry_warning_days'      => $request->expiry_warning_days ?? 7,
            'prevent_reuse'            => $request->boolean('prevent_reuse'),
            'history_count'            => $request->prevent_reuse ? ($request->history_count ?? 5) : 5,
            'lockout_enabled'          => $request->boolean('lockout_enabled'),
            'max_attempts'             => $request->lockout_enabled ? ($request->max_attempts ?? 5) : 5,
            'lockout_duration_minutes' => $request->lockout_enabled ? ($request->lockout_duration_minutes ?? 30) : 30,
        ]);

        return back()->with('success', '¡Política de contraseñas actualizada correctamente!');
    }
}
