<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SessionManagementService;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SessionManagementController extends Controller
{
    public function __construct(protected SessionManagementService $sessionService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $sessions = $this->sessionService->getActiveSessions($user);

        return Inertia::render('Security/Sessions', [
            'sessions' => $sessions->map(fn($s) => [
                'id' => $s->id,
                'device_info' => $s->device_info,
                'location_info' => $s->location_info,
                'is_current' => $s->is_current,
                'is_trusted' => $s->is_trusted,
                'last_activity' => $s->last_activity,
            ]),
        ]);
    }

    public function revoke(Request $request, UserSession $session)
    {
        $this->authorize('revoke', $session);
        $this->sessionService->revokeSession($session);
        return back()->with('success', 'Sesión revocada.');
    }

    public function revokeAll(Request $request)
    {
        $count = $this->sessionService->revokeAllExceptCurrent($request->user());
        return back()->with('success', "{$count} sesiones revocadas.");
    }

    public function trust(Request $request, UserSession $session)
    {
        $this->authorize('update', $session);
        $this->sessionService->trustDevice($session);
        return back()->with('success', 'Dispositivo marcado como confiable.');
    }
}
