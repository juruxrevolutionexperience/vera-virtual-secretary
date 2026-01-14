<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\AuditService;
use App\Services\Security\SessionManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecurityDashboardController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected SessionManagementService $sessionService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Security/Dashboard', [
            'stats' => [
                'sessions' => $this->sessionService->getStats($user),
                'audit' => $this->auditService->getStats(),
            ],
            'recentActivity' => $this->auditService->getUserLogs($user, 10),
            'securityEvents' => $this->auditService->getRecentSecurityEvents(10),
            'criticalEvents' => $this->auditService->getCriticalEvents(),
        ]);
    }
}
