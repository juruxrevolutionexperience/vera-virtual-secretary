<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\GDPRComplianceService;
use App\Models\DataExportRequest;
use App\Models\DataDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GDPRController extends Controller
{
    public function __construct(protected GDPRComplianceService $gdprService) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Security/Privacy', [
            'exportRequests' => DataExportRequest::where('user_id', $user->id)
                ->latest()->take(5)->get(),
            'deletionRequests' => DataDeletionRequest::where('user_id', $user->id)
                ->latest()->take(5)->get(),
        ]);
    }

    public function requestExport(Request $request)
    {
        $exportRequest = $this->gdprService->requestExport($request->user());
        return back()->with('success', 'Solicitud de exportación creada. Te notificaremos cuando esté lista.');
    }

    public function downloadExport(string $token)
    {
        $request = DataExportRequest::where('token', $token)
            ->where('status', 'completed')
            ->firstOrFail();

        if ($request->isExpired()) {
            abort(410, 'El enlace ha expirado.');
        }

        return Storage::disk('local')->download($request->file_path);
    }

    public function requestDeletion(Request $request)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $deletionRequest = $this->gdprService->requestDeletion(
            $request->user(),
            $request->input('reason')
        );

        return back()->with('warning', 'Solicitud de eliminación creada. Requiere aprobación del administrador.');
    }
}

