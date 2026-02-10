<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\PasswordPolicyService;
use App\Services\Security\SessionManagementService;
use App\Models\PasswordPolicy;
use App\Models\PasswordHistory;
use App\Models\UserSession;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\DataDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordPolicyController extends Controller
{
    public function __construct(
        protected PasswordPolicyService $policyService,
        protected SessionManagementService $sessionService
    ) {}

    /**
     * Endpoint JSON para el modal de ajustes
     */
    public function securityData(Request $request)
    {
        $user = $request->user();

        // Garantizar que la sesión actual siempre esté registrada
        $userAgent   = $request->userAgent() ?? 'Unknown';
        $parsedAgent = UserSession::parseUserAgent($userAgent);
        $ip          = $request->ip();
        $location    = $this->resolveLocation($ip);

        UserSession::updateOrCreate(
            ['session_id' => session()->getId()],
            [
                'user_id'       => $user->id,
                'ip_address'    => $ip,
                'user_agent'    => $userAgent,
                'device_type'   => $parsedAgent['device_type'],
                'browser'       => $parsedAgent['browser'],
                'platform'      => $parsedAgent['platform'],
                'city'          => $location['city'],
                'country'       => $location['country'],
                'last_activity' => now(),
                'expires_at'    => now()->addMinutes(config('session.lifetime', 120)),
                'is_current'    => true,
            ]
        );

        $policy         = PasswordPolicy::getActive();
        $sessions       = UserSession::where('user_id', $user->id)->orderBy('last_activity', 'desc')->get();
        $activeSessions = $sessions->filter->isActive();

        return response()->json([
            'sessions' => [
                'total'   => $sessions->count(),
                'active'  => $activeSessions->count(),
                'list'    => $sessions->take(8)->map(fn($s) => [
                    'id'            => $s->id,
                    'label'         => $this->formatDeviceLabel($s->browser, $s->platform, $s->device_type),
                    'location'      => collect([$s->city, $s->country])->filter()->implode(', ') ?: 'Ubicación desconocida',
                    'ip'            => $s->ip_address,
                    'is_current'    => $s->session_id === session()->getId(),
                    'is_active'     => $s->isActive(),
                    'last_activity' => $s->last_activity?->format('d M Y, H:i'),
                    'created_at'    => $s->created_at?->format('d M Y, H:i'),
                ]),
                'suspicious' => $this->sessionService->detectSuspiciousActivity($user),
            ],
            'password' => [
                'has_policy'      => (bool) $policy,
                'policy_name'     => $policy?->name,
                'min_length'      => $policy?->min_length ?? 8,
                'expires'         => $policy?->expires ?? false,
                'expiry_days'     => $policy?->expiry_days,
                'is_expired'      => $policy ? $this->policyService->isPasswordExpired($user) : false,
                'days_until_exp'  => $policy ? $this->policyService->daysUntilExpiry($user) : null,
                'should_warn'     => $policy ? $this->policyService->shouldWarnAboutExpiry($user) : false,
                'total_changes'   => PasswordHistory::where('user_id', $user->id)->count(),
                'last_change'     => PasswordHistory::where('user_id', $user->id)->latest('created_at')->value('created_at'),
                'require_upper'   => $policy?->require_uppercase ?? true,
                'require_lower'   => $policy?->require_lowercase ?? true,
                'require_numbers' => $policy?->require_numbers ?? true,
                'require_special' => $policy?->require_special_chars ?? false,
            ],
            'gdpr' => [
                'export_requests'   => DataExportRequest::where('user_id', $user->id)->latest()->take(3)->get()->map(fn($e) => [
                    'id'             => $e->id,
                    'status'         => $e->status,
                    'created_at'     => $e->created_at->format('d/m/Y'),
                    'download_token' => $e->status === 'completed' && !$e->isExpired() ? $e->download_token : null,
                    'file_size'      => $e->getFileSizeFormatted(),
                    'is_expired'     => $e->isExpired(),
                ]),
                'deletion_requests' => DataDeletionRequest::where('user_id', $user->id)->latest()->take(3)->get()->map(fn($d) => [
                    'id'            => $d->id,
                    'status'        => $d->status,
                    'deletion_type' => $d->deletion_type,
                    'created_at'    => $d->created_at->format('d/m/Y'),
                    'can_cancel'    => $d->status === 'pending',
                ]),
                'has_active_deletion' => DataDeletionRequest::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'approved', 'processing'])->exists(),
            ],
            'audit' => [
                'total'   => AuditLog::where('user_id', $user->id)->count(),
                'today'   => AuditLog::where('user_id', $user->id)->whereDate('created_at', today())->count(),
                'critical' => AuditLog::where('user_id', $user->id)->where('severity', 'critical')->count(),
                'recent'  => AuditLog::where('user_id', $user->id)->latest()->take(6)->get()->map(fn($l) => [
                    'description' => $l->description ?? $l->event_type ?? 'Acción registrada',
                    'category'    => $l->event_category ?? 'general',
                    'severity'    => $l->severity ?? 'info',
                    'time'        => $l->created_at->diffForHumans(),
                    'ip'          => $l->ip_address,
                ]),
            ],
        ]);
    }

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
            if ($request->expectsJson()) {
                return response()->json(['message' => 'La contraseña actual no es correcta.', 'errors' => ['current_password' => ['La contraseña actual no es correcta.']]], 422);
            }
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        try {
            $this->policyService->changePassword($user, $request->new_password, 'user');
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => '¡Contraseña actualizada correctamente!']);
            }
            return back()->with('success', '¡Contraseña actualizada correctamente!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
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

    // ==========================================
    // HELPERS PRIVADOS
    // ==========================================

    /**
     * Formatea la etiqueta del dispositivo: "Chrome (Windows)", "Safari (macOS)", etc.
     */
    private function formatDeviceLabel(?string $browser, ?string $platform, ?string $deviceType): string
    {
        if ($browser && $platform && $platform !== 'Unknown') {
            return "{$browser} ({$platform})";
        }
        if ($browser && $browser !== 'Unknown') {
            return $browser;
        }
        return match ($deviceType) {
            'mobile'  => 'Dispositivo móvil',
            'tablet'  => 'Tablet',
            'desktop' => 'Ordenador',
            default   => 'Dispositivo desconocido',
        };
    }

    /**
     * Resuelve la ubicación desde una IP.
     * Devuelve array ['city' => ..., 'country' => ...].
     */
    private function resolveLocation(string $ip): array
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
            // Silencioso: la geolocalización no es crítica
        }

        return ['city' => null, 'country' => null];
    }
}
