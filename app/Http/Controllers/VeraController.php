<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VeraIntelligentAnalyzer;
use App\Services\VeraOnboardingService;
use App\Services\VeraReportGenerator;
use App\Models\VeraConfig;
use App\Models\Email;
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
        $emailsCount = Email::whereHas('emailAccount', function($query) use ($user) {
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
        $emails = Email::whereHas('emailAccount', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        // Analizar emails
        $analysis = $this->analyzer->analyze($emails);

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
            'response_mode' => 'required|string|in:filter_focus,suggest_responses',
            'newsletter_handling' => 'required|string|in:auto_archive,mark_read,categorize,manual',
            'priority_contacts' => 'nullable|array',
            'priority_contacts.*' => 'email',
            'active_hours_start' => 'required|string',
            'active_hours_end' => 'required|string',
            'autonomy_level' => 'required|string|in:conservative,moderate,aggressive',
            'confidence_threshold' => 'nullable|integer|min:0|max:100',
        ]);

        // Crear o actualizar configuración
        $config = VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'primary_goal' => $validated['primary_goal'],
                'writing_style' => $validated['writing_style'],
                'response_mode' => $validated['response_mode'],
                'newsletter_handling' => $validated['newsletter_handling'],
                'priority_contacts' => $validated['priority_contacts'] ?? [],
                'active_hours_start' => $validated['active_hours_start'],
                'active_hours_end' => $validated['active_hours_end'],
                'autonomy_level' => $validated['autonomy_level'],
                'confidence_threshold' => $validated['confidence_threshold'] ?? $this->getDefaultThreshold($validated['autonomy_level']),
                'auto_respond_enabled' => $validated['response_mode'] === 'suggest_responses',
                'auto_archive_newsletters' => $validated['newsletter_handling'] === 'auto_archive',
                'periodic_analysis_enabled' => true,
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
            'response_mode' => 'sometimes|string|in:filter_focus,suggest_responses',
            'newsletter_handling' => 'sometimes|string|in:auto_archive,mark_read,categorize,manual',
            'priority_contacts' => 'nullable|array',
            'active_hours_start' => 'sometimes|string',
            'active_hours_end' => 'sometimes|string',
            'autonomy_level' => 'sometimes|string|in:conservative,moderate,aggressive',
            'confidence_threshold' => 'nullable|integer|min:0|max:100',
            'auto_respond_enabled' => 'sometimes|boolean',
            'auto_archive_newsletters' => 'sometimes|boolean',
        ]);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $config->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada',
            'config' => $config,
        ]);
    }

    /**
     * Ejecuta análisis manualmente
     */
    public function runAnalysis()
    {
        $user = Auth::user();

        // Obtener emails del usuario
        $emails = Email::whereHas('emailAccount', function($query) use ($user) {
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
     */
    public function checkOnboardingNeeded()
    {
        $user = Auth::user();

        $needsOnboarding = cache()->get("vera_needs_onboarding_{$user->id}", false);

        // Si necesita onboarding, limpiar el flag después de mostrarlo
        if ($needsOnboarding) {
            cache()->forget("vera_needs_onboarding_{$user->id}");
        }

        return response()->json([
            'needs_onboarding' => $needsOnboarding,
        ]);
    }

    /**
     * Helper: Obtener umbral de confianza por defecto según autonomía
     */
    private function getDefaultThreshold($autonomy)
    {
        return match($autonomy) {
            'conservative' => 90,
            'moderate' => 75,
            'aggressive' => 60,
            default => 75,
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
            'total_emails' => Email::whereHas('emailAccount', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count(),
            'processed_emails' => Email::whereHas('emailAccount', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('vera_processed', true)->count(),
            'auto_archived' => Email::whereHas('emailAccount', function($query) use ($user) {
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
