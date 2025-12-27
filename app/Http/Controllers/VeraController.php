<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VeraIntelligentAnalyzer;
use App\Services\VeraOnboardingService;
use App\Services\VeraReportGenerator;
use App\Models\VeraConfig;
use App\Models\Email;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VeraController extends Controller
{
    protected $analyzer;
    protected $onboardingService;
    protected $reportGenerator;

    public function __construct(
        VeraIntelligentAnalyzer $analyzer,
        VeraOnboardingService $onboardingService,
        VeraReportGenerator $reportGenerator
    ) {
        $this->analyzer = $analyzer;
        $this->onboardingService = $onboardingService;
        $this->reportGenerator = $reportGenerator;
    }

    /**
     * Muestra el dashboard de Vera con análisis y estado
     */
    public function dashboard()
    {
        $user = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        // Verificar si tiene emails para analizar
        $emailsCount = Email::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        return view('vera.dashboard', compact('config', 'emailsCount'));
    }

    /**
     * Muestra el onboarding de Vera (análisis + preguntas)
     */
    public function onboarding()
    {
        $user = Auth::user();

        // Verificar si ya tiene configuración
        $existingConfig = VeraConfig::where('user_id', $user->id)->first();

        // Obtener emails del usuario
        $emails = Email::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        // Analizar emails
        $analysis = $this->analyzer->analyzeUser($user);

        // Generar reporte visual
        $report = $this->reportGenerator->generateReport($analysis);

        // Generar preguntas personalizadas
        $questions = $this->onboardingService->generateQuestions($analysis);

        return view('vera.onboarding', compact('report', 'questions', 'analysis', 'existingConfig'));
    }

    /**
     * Guarda la configuración del onboarding
     */
    public function saveOnboarding(Request $request)
    {
        $user = Auth::user();

        // Validar datos
        $validated = $request->validate([
            'primary_goal' => 'required|string|in:filter,respond,balanced',
            'writing_style' => 'required|string|in:formal,professional,casual',
            'response_mode' => 'required|string|in:filter_focus,suggest_responses,always,important_only,ask_first,no',
            'newsletter_handling' => 'required|string|in:auto_archive,mark_read,categorize,manual',
            'priority_contacts' => 'nullable|array',
            'priority_contacts.*' => 'email',
            'active_hours_start' => 'required|string',
            'active_hours_end' => 'required|string',
            'autonomy_level' => 'required|string|in:conservative,moderate,aggressive',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        // Determinar si requiere aprobación manual según el modo
        $requireApproval = !in_array($validated['response_mode'], ['always']);

        // Crear o actualizar configuración
        $config = VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'main_objective' => $validated['primary_goal'],
                'writing_style' => ['tone' => $validated['writing_style']],
                'auto_response_mode' => $validated['response_mode'],
                'auto_response_enabled' => \in_array($validated['response_mode'], ['suggest_responses', 'always', 'important_only', 'ask_first']),
                'auto_response_require_approval' => $requireApproval,
                'newsletter_handling' => $validated['newsletter_handling'],
                'important_contacts' => $validated['priority_contacts'] ?? [],
                'availability_start' => $validated['active_hours_start'],
                'availability_end' => $validated['active_hours_end'],
                'autonomy_level' => $validated['autonomy_level'],
                'confidence_threshold' => $validated['confidence_threshold'] ?? $this->getDefaultThreshold($validated['autonomy_level']),
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
                'last_analysis_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada exitosamente',
            'config' => $config,
        ]);
    }

    /**
     * Muestra la configuración actual de Vera
     */
    public function config()
    {
        $user = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config) {
            // Si no tiene configuración, redirigir a onboarding
            return redirect()->route('vera.onboarding');
        }

        // Obtener estadísticas de Vera
        $stats = $this->getVeraStats($user);

        return view('vera.config', compact('config', 'stats'));
    }

    /**
     * Actualiza la configuración de Vera
     */
    public function updateConfig(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'primary_goal' => 'sometimes|string|in:filter,respond,balanced',
            'writing_style' => 'sometimes|string|in:formal,professional,casual',
            'response_mode' => 'sometimes|string|in:no,ask_first,important_only,suggest_responses,always,filter_focus',
            'newsletter_handling' => 'sometimes|string|in:auto_archive,mark_read,categorize,manual',
            'priority_contacts' => 'nullable|array',
            'active_hours_start' => 'sometimes|string',
            'active_hours_end' => 'sometimes|string',
            'autonomy_level' => 'sometimes|string|in:conservative,moderate,aggressive',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'auto_response_enabled' => 'sometimes|boolean',
            'auto_archive_newsletters' => 'sometimes|boolean',
        ]);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        // Mapear campos del formulario a los campos de la BD
        $updateData = [];

        if (isset($validated['primary_goal'])) {
            $updateData['main_objective'] = $validated['primary_goal'];
        }

        if (isset($validated['response_mode'])) {
            $updateData['auto_response_mode'] = $validated['response_mode'];
            // Actualizar auto_response_enabled basado en el modo
            $updateData['auto_response_enabled'] = !in_array($validated['response_mode'], ['no', 'filter_focus']);
            // Actualizar auto_response_require_approval
            $updateData['auto_response_require_approval'] = !in_array($validated['response_mode'], ['always']);
        }

        if (isset($validated['writing_style'])) {
            // Mantener el formato existente de writing_style y solo actualizar el tone
            $currentStyle = $config->writing_style ?? [];
            $currentStyle['tone'] = $validated['writing_style'];
            $updateData['writing_style'] = $currentStyle;
        }

        if (isset($validated['newsletter_handling'])) {
            $updateData['newsletter_handling'] = $validated['newsletter_handling'];
        }

        if (isset($validated['autonomy_level'])) {
            $updateData['autonomy_level'] = $validated['autonomy_level'];
        }

        if (isset($validated['confidence_threshold'])) {
            $updateData['confidence_threshold'] = $validated['confidence_threshold'];
        }

        if (isset($validated['active_hours_start'])) {
            $updateData['availability_start'] = $validated['active_hours_start'];
        }

        if (isset($validated['active_hours_end'])) {
            $updateData['availability_end'] = $validated['active_hours_end'];
        }

        if (isset($validated['auto_response_enabled'])) {
            $updateData['auto_response_enabled'] = $validated['auto_response_enabled'];
        }

        if (isset($validated['auto_archive_newsletters'])) {
            $updateData['auto_archive_newsletters'] = $validated['auto_archive_newsletters'];
        }

        if (isset($validated['priority_contacts'])) {
            $updateData['important_contacts'] = $validated['priority_contacts'];
        }

        $config->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada exitosamente',
            'config' => $config->fresh(),
        ]);
    }

    /**
     * Ejecuta análisis manualmente
     */
    public function runAnalysis()
    {
        $user = Auth::user();

        // Obtener emails del usuario
        $emails = Email::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        // Analizar
        $analysis = $this->analyzer->analyze($emails);

        // Generar reporte
        $report = $this->reportGenerator->generateReport($analysis);

        // Actualizar fecha de último análisis
        VeraConfig::where('user_id', $user->id)->update([
            'last_analysis_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'report' => $report,
        ]);
    }

    /**
     * Obtiene estadísticas de Vera
     */
    public function stats()
    {
        $user = Auth::user();
        $stats = $this->getVeraStats($user);

        return response()->json($stats);
    }

    /**
     * Verifica si el usuario necesita onboarding
     * Cambio: Ahora busca en BD y verifica que tenga emails sincronizados
     */
    public function checkOnboardingNeeded()
    {
        $user = Auth::user();

        // Verificar si tiene cuentas de email conectadas y sincronizadas
        $hasEmailAccount = EmailAccount::where('user_id', $user->id)
            ->whereNotNull('initial_sync_completed_at')
            ->exists();

        // Si no tiene cuenta de email sincronizada, no mostrar modal aún
        if (!$hasEmailAccount) {
            return response()->json([
                'needs_onboarding' => false,
                'reason' => 'no_email_account_synced',
            ]);
        }

        // Verificar si tiene emails
        $hasEmails = Email::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->exists();

        // Si no tiene emails aún, no mostrar modal
        if (!$hasEmails) {
            return response()->json([
                'needs_onboarding' => false,
                'reason' => 'no_emails_yet',
            ]);
        }

        // Buscar configuración de Vera en la base de datos
        $config = VeraConfig::where('user_id', $user->id)->first();

        // Necesita onboarding si:
        // 1. Tiene emails sincronizados
        // 2. Y (No tiene configuración de Vera O tiene config pero no completó onboarding)
        $needsOnboarding = !$config || !$config->onboarding_completed;

        return response()->json([
            'needs_onboarding' => $needsOnboarding,
            'reason' => $needsOnboarding ? 'ready_for_onboarding' : 'onboarding_completed',
        ]);
    }

    /**
     * Helper: Obtener umbral de confianza por defecto según autonomía
     */
    private function getDefaultThreshold($autonomy)
    {
        return match ($autonomy) {
            'conservative' => 0.90,
            'moderate' => 0.75,
            'aggressive' => 0.60,
            default => 0.75,
        };
    }

    /**
     * Mostrar página de respuestas de Vera
     */
    public function responsesIndex()
    {
        return view('vera.responses');
    }

    /**
     * Obtener respuestas pendientes de aprobación
     */
    public function getPendingResponses()
    {
        $user = Auth::user();

        $responses = \App\Models\VeraEmailAction::where('user_id', $user->id)
            ->where('action_type', 'replied')
            ->where('user_feedback', 'pending')
            ->where('response_sent', false)
            ->with('email')
            ->orderBy('confidence_score', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $responses->count(),
            'responses' => $responses,
        ]);
    }

    /**
     * Obtener una respuesta específica
     */
    public function getResponse(\App\Models\VeraEmailAction $response)
    {
        if ($response->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'response' => $response->load('email'),
        ]);
    }

    /**
     * Aprobar una respuesta
     */
    public function approveResponse(\App\Models\VeraEmailAction $response)
    {
        if ($response->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $response->update([
            'user_feedback' => 'approved',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta aprobada',
            'response' => $response,
        ]);
    }

    /**
     * Rechazar una respuesta
     */
    public function rejectResponse(Request $request, \App\Models\VeraEmailAction $response)
    {
        if ($response->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $response->update([
            'user_feedback' => 'rejected',
            'user_notes' => $request->input('reason', ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta rechazada',
        ]);
    }

    /**
     * Actualizar una respuesta (modificar texto)
     */
    public function updateResponse(Request $request, \App\Models\VeraEmailAction $response)
    {
        if ($response->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'generated_response' => 'required|string',
        ]);

        $response->update([
            'generated_response' => $validated['generated_response'],
            'user_feedback' => 'modified',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta actualizada',
            'response' => $response,
        ]);
    }

    /**
     * Enviar una respuesta aprobada
     */
    public function sendResponse(\App\Models\VeraEmailAction $response)
    {
        if ($response->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $email = $response->email;
            $account = $email->emailAccount;

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la cuenta de email',
                ], 404);
            }

            // Usar el servicio apropiado según el provider
            if ($account->provider === 'gmail') {
                $service = new \App\Services\GmailSyncService();
            } else {
                $service = new \App\Services\OutlookSyncService();
            }

            // Preparar asunto con Re:
            $subject = $email->subject;
            if (!str_starts_with(strtolower($subject), 're:')) {
                $subject = 'Re: ' . $subject;
            }

            // Enviar email
            $sentEmail = $service->sendEmail($account, [
                'from' => $account->email_address,
                'to' => $email->from_email,
                'subject' => $subject,
                'body' => $response->generated_response,
            ]);

            if ($sentEmail) {
                // Actualizar respuesta
                $response->update([
                    'response_sent' => true,
                    'response_sent_at' => now(),
                ]);

                // Actualizar email original
                $email->update([
                    'replied_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Respuesta enviada exitosamente',
                    'response' => $response,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la respuesta',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error enviando respuesta de Vera', [
                'response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Obtener estadísticas de Vera
     */
    private function getVeraStats($user)
    {
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config) {
            return [
                'total_emails' => 0,
                'processed_emails' => 0,
                'auto_archived' => 0,
                'responses_generated' => 0,
                'last_analysis' => null,
            ];
        }

        return [
            'total_emails' => Email::whereHas('emailAccount', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count(),
            'processed_emails' => Email::whereHas('emailAccount', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('vera_processed', true)->count(),
            'auto_archived' => Email::whereHas('emailAccount', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('is_archived', true)->where('vera_processed', true)->count(),
            'responses_generated' => \DB::table('vera_email_actions')
                ->where('user_id', $user->id)
                ->where('action_type', 'replied')
                ->count(),
            'last_analysis' => $config->last_analysis_at,
            'config' => $config,
        ];
    }
}
