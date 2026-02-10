<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\AuditService;
use App\Services\Security\SessionManagementService;
use App\Services\Security\PasswordPolicyService;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SecurityDashboardController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected SessionManagementService $sessionService,
        protected PasswordPolicyService $policyService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $sessionStats    = $this->sessionService->getStats($user);
        $recentActivity  = $this->auditService->getUserLogs($user, 8);
        $criticalEvents  = AuditLog::where('user_id', $user->id)
            ->where('severity', 'critical')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $policy          = $this->policyService->getActivePolicy();
        $isExpired       = $policy ? $this->policyService->isPasswordExpired($user) : false;
        $daysUntilExpiry = $policy ? $this->policyService->daysUntilExpiry($user) : null;
        $shouldWarn      = $policy ? $this->policyService->shouldWarnAboutExpiry($user) : false;

        $auditStats = [
            'total'     => AuditLog::where('user_id', $user->id)->count(),
            'today'     => AuditLog::where('user_id', $user->id)->whereDate('created_at', today())->count(),
            'critical'  => AuditLog::where('user_id', $user->id)->where('severity', 'critical')->count(),
            'failed'    => AuditLog::where('user_id', $user->id)->where('action_result', 'failed')->count(),
        ];

        return view('security.dashboard', compact(
            'sessionStats',
            'recentActivity',
            'criticalEvents',
            'policy',
            'isExpired',
            'daysUntilExpiry',
            'shouldWarn',
            'auditStats'
        ));
    }
}
