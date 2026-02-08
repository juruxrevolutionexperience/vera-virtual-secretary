<?php

namespace App\Http\Controllers;

use App\Services\SessionManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SessionManagementController extends Controller
{
    protected SessionManagementService $sessionService;

    public function __construct(SessionManagementService $sessionService)
    {
        $this->sessionService = $sessionService;
        $this->middleware('auth');
    }

    /**
     * Mostrar vista de sesiones activas
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $sessions = $this->sessionService->getAllSessions($user);
        $activeSessions = $sessions->filter->isActive();
        $suspiciousActivity = $this->sessionService->detectSuspiciousActivity($user);

        return view('sessions.index', compact('sessions', 'activeSessions', 'suspiciousActivity'));
    }

    /**
     * Obtener sesiones activas (AJAX)
     */
    public function getActiveSessions(Request $request)
    {
        $user = $request->user();
        $sessions = $this->sessionService->getActiveSessions($user);

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Cerrar una sesión específica
     */
    public function terminate(Request $request, int $sessionId)
    {
        $user = $request->user();
        $success = $this->sessionService->terminateSession($user, $sessionId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cerrar la sesión. No puedes cerrar la sesión actual desde aquí.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    /**
     * Cerrar todas las sesiones excepto la actual
     */
    public function terminateOthers(Request $request)
    {
        $user = $request->user();
        $currentSessionId = Session::getId();
        $count = $this->sessionService->terminateOtherSessions($user, $currentSessionId);

        return response()->json([
            'success' => true,
            'message' => "Se cerraron {$count} sesiones.",
            'count' => $count,
        ]);
    }

    /**
     * Actualizar actividad de la sesión actual
     */
    public function updateActivity(Request $request)
    {
        $sessionId = Session::getId();
        $this->sessionService->updateActivity($sessionId);

        return response()->json([
            'success' => true,
        ]);
    }
}
