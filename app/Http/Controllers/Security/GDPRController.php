<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\GDPRComplianceService;
use App\Models\DataExportRequest;
use App\Models\DataDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GDPRController extends Controller
{
    public function __construct(protected GDPRComplianceService $gdprService) {}

    /**
     * Vista principal RGPD / Privacidad
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $exportRequests = DataExportRequest::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $deletionRequests = DataDeletionRequest::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $hasActiveDeletion = $deletionRequests->whereIn('status', ['pending', 'approved', 'processing'])->isNotEmpty();

        $availableDataTypes = DataDeletionRequest::availableDataTypes();

        return view('security.gdpr', compact(
            'exportRequests',
            'deletionRequests',
            'hasActiveDeletion',
            'availableDataTypes'
        ));
    }

    /**
     * Solicitar exportación de datos
     */
    public function requestExport(Request $request)
    {
        $exportRequest = $this->gdprService->requestExport($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitud de exportación creada. Te notificaremos cuando esté lista.',
                'request_id' => $exportRequest->id,
            ]);
        }

        return back()->with('success', 'Solicitud de exportación creada. Te notificaremos cuando esté lista (puede tardar unos minutos).');
    }

    /**
     * Descargar exportación de datos
     */
    public function downloadExport(string $token)
    {
        $exportRequest = DataExportRequest::where('download_token', $token)
            ->where('status', 'completed')
            ->firstOrFail();

        if ($exportRequest->isExpired()) {
            abort(410, 'El enlace de descarga ha expirado.');
        }

        if (!$exportRequest->file_path || !Storage::disk('local')->exists($exportRequest->file_path)) {
            abort(404, 'El archivo de exportación no está disponible.');
        }

        $exportRequest->incrementDownloadCount();

        return Storage::disk('local')->download(
            $exportRequest->file_path,
            $exportRequest->file_name ?? 'mis-datos.zip'
        );
    }

    /**
     * Solicitar eliminación de datos
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'reason'        => 'nullable|string|max:500',
            'deletion_type' => 'required|in:partial,full_account',
            'data_types'    => 'nullable|array',
        ]);

        $deletionRequest = $this->gdprService->requestDeletion(
            $request->user(),
            $request->input('reason')
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitud de eliminación creada.',
                'requires_approval' => $deletionRequest->requiresApproval(),
            ]);
        }

        if ($request->input('deletion_type') === 'full_account') {
            return back()->with('warning', 'Solicitud de eliminación de cuenta creada. Requiere revisión del administrador (plazo: 30 días).');
        }

        return back()->with('success', 'Solicitud de eliminación de datos creada correctamente.');
    }

    /**
     * Cancelar solicitud de eliminación
     */
    public function cancelDeletion(Request $request, DataDeletionRequest $deletion)
    {
        if ($deletion->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!in_array($deletion->status, ['pending'])) {
            return back()->withErrors(['error' => 'Esta solicitud no puede cancelarse en su estado actual.']);
        }

        $deletion->cancel('Cancelado por el usuario');

        return back()->with('success', 'Solicitud de eliminación cancelada.');
    }
}
