<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VeraIntelligentAnalyzer;
use App\Services\VeraOnboardingService;
use App\Services\VeraReportGenerator;
use App\Services\VeraWritingStyleService;
use App\Services\VeraMemoryService;
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

        // RAG: re-indexar memoria con la nueva configuración
        try {
            (new VeraMemoryService())->indexUserConfig($user);
        } catch (\Exception $e) {
            Log::warning('⚠️  No se pudo indexar memoria RAG al guardar config', ['error' => $e->getMessage()]);
        }

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
            return redirect()->route('vera.onboarding');
        }

        // Cargar perfil RAG del usuario (para pre-rellenar el formulario)
        $userProfileChunks = \App\Models\VeraMemoryChunk::where('user_id', $user->id)
            ->where('type', 'user_profile')
            ->where('is_active', true)
            ->get()
            ->keyBy('title');

        $userProfile = [
            'business_description' => $userProfileChunks->get('Descripción del negocio')?->content ?? '',
            'response_style'       => $userProfileChunks->get('Estilo de respuesta')?->content ?? '',
            'email_signature'      => $userProfileChunks->get('Firma de email')?->content ?? '',
            'never_say'            => $userProfileChunks->get('Cosas que nunca diría')?->content ?? '',
        ];

        $stats = $this->getVeraStats($user);

        return view('vera.config', compact('config', 'stats', 'userProfile'));
    }

    /**
     * Guarda el perfil de usuario en el RAG (VeraMemoryChunks)
     */
    public function saveUserProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'business_description' => 'nullable|string|max:1000',
            'response_style'       => 'nullable|string|max:1000',
            'email_signature'      => 'nullable|string|max:500',
            'never_say'            => 'nullable|string|max:500',
        ]);

        $chunks = [
            'business_description' => [
                'title'    => 'Descripción del negocio',
                'keywords' => 'negocio empresa trabajo profesión dedicación sector industria cliente',
            ],
            'response_style' => [
                'title'    => 'Estilo de respuesta',
                'keywords' => 'estilo tono respuesta clientes desconocidos amigos formal informal',
            ],
            'email_signature' => [
                'title'    => 'Firma de email',
                'keywords' => 'firma nombre cargo teléfono email contacto',
            ],
            'never_say' => [
                'title'    => 'Cosas que nunca diría',
                'keywords' => 'evitar nunca prohibido límite tabú inapropiado',
            ],
        ];

        foreach ($chunks as $key => $meta) {
            if (!empty($validated[$key])) {
                \App\Models\VeraMemoryChunk::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type'    => 'user_profile',
                        'title'   => $meta['title'],
                    ],
                    [
                        'content'        => $validated[$key],
                        'keywords_text'  => $meta['keywords'],
                        'source'         => 'user_config',
                        'is_active'      => true,
                        'relevance_tags' => ['user_profile'],
                    ]
                );
            } else {
                // Si está vacío, desactivar el chunk si existía
                \App\Models\VeraMemoryChunk::where('user_id', $user->id)
                    ->where('type', 'user_profile')
                    ->where('title', $meta['title'])
                    ->update(['is_active' => false]);
            }
        }

        Log::info('👤 Perfil RAG del usuario actualizado', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil guardado. Vera lo tendrá en cuenta en sus respuestas.',
        ]);
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
            'response_delay_minutes' => 'sometimes|integer|min:0|max:1440',
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

        if (isset($validated['response_delay_minutes'])) {
            $updateData['response_delay_minutes'] = $validated['response_delay_minutes'];
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

        // Analizar
        $analysis = $this->analyzer->analyzeUser($user);

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
     * Guardar feedback del usuario sobre una respuesta de Vera → aprende en RAG
     */
    public function saveResponseFeedback(Request $request, \App\Models\VeraEmailAction $response)
    {
        if ($response->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'reason'      => 'required|string|in:tone,length,missing_info,signature,other',
            'custom_text' => 'nullable|string|max:500',
        ]);

        $reasonMap = [
            'tone'         => 'El usuario indica que el tono de las respuestas generadas no refleja su estilo personal. Ajustar el tono para que sea más natural y auténtico.',
            'length'       => 'El usuario indica que las respuestas son demasiado largas o cortas. Generar respuestas más equilibradas en longitud.',
            'missing_info' => 'El usuario indica que faltaba información importante en la respuesta. Incluir más contexto y detalles relevantes.',
            'signature'    => 'El usuario indica que la firma o cierre de la respuesta estaba mal. Revisar y ajustar el formato de despedida y firma.',
            'other'        => $validated['custom_text'] ?? 'El usuario proporcionó feedback personalizado sobre la respuesta.',
        ];

        $content = $reasonMap[$validated['reason']];

        // Guardar en RAG como aprendizaje de estilo de comunicación
        \App\Models\VeraMemoryChunk::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'type'    => 'communication_style',
                'title'   => 'Feedback respuesta Vera: ' . $validated['reason'],
            ],
            [
                'content'        => $content,
                'keywords_text'  => 'feedback tono estilo respuesta comunicacion preferencia',
                'relevance_tags' => ['feedback', 'communication_style', $validated['reason']],
                'source'         => 'user_feedback',
                'is_active'      => true,
            ]
        );

        // Guardar también en el action para trazabilidad
        $response->update(['user_notes' => ($response->user_notes ? $response->user_notes . ' | ' : '') . $content]);

        return response()->json(['success' => true, 'message' => 'Feedback guardado. Vera aprenderá de esto.']);
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

                // Actualizar actividad para reflejar que fue enviada
                $activity = \App\Models\VeraActivityLog::where('email_id', $email->id)
                    ->where('activity_type', 'email_replied')
                    ->latest()
                    ->first();

                if ($activity) {
                    $metadata = $activity->metadata ?? [];
                    $metadata['response_sent'] = true;
                    $metadata['manual_send'] = true;
                    $activity->update([
                        'metadata' => $metadata,
                        'description' => $activity->description . ' (Enviada manualmente)',
                    ]);
                }

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
     * Obtener configuración de filtros de respuesta
     */
    public function getResponseFilters()
    {
        $user = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config) {
            // Retornar valores por defecto si no existe configuración
            return response()->json([
                'success' => true,
                'filters' => VeraConfig::getDefaultFilters(),
            ]);
        }

        return response()->json([
            'success' => true,
            'filters' => [
                'response_allowed_categories' => $config->response_allowed_categories,
                'response_blocked_categories' => $config->response_blocked_categories,
                'blocked_keywords' => $config->blocked_keywords,
                'blocked_senders' => $config->blocked_senders,
                'allowed_senders' => $config->allowed_senders,
            ],
        ]);
    }

    /**
     * Actualizar filtros de respuesta automática
     */
    public function updateResponseFilters(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'response_allowed_categories' => 'nullable|array',
            'response_allowed_categories.*' => 'string|in:trabajo,personal,promociones,newsletters,social',
            'response_blocked_categories' => 'nullable|array',
            'response_blocked_categories.*' => 'string|in:trabajo,personal,promociones,newsletters,social',
            'blocked_keywords' => 'nullable|array',
            'blocked_keywords.*' => 'string|max:100',
            'blocked_senders' => 'nullable|array',
            'blocked_senders.*' => 'string|max:255',
            'allowed_senders' => 'nullable|array',
            'allowed_senders.*' => 'string|max:255',
        ]);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $config->update($validated);

        Log::info('🎯 Filtros de respuesta actualizados', [
            'user_id' => $user->id,
            'filters' => $validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Filtros actualizados exitosamente',
            'filters' => [
                'response_allowed_categories' => $config->response_allowed_categories,
                'response_blocked_categories' => $config->response_blocked_categories,
                'blocked_keywords' => $config->blocked_keywords,
                'blocked_senders' => $config->blocked_senders,
                'allowed_senders' => $config->allowed_senders,
            ],
        ]);
    }

    /**
     * Agregar un patrón a un filtro específico
     */
    public function addFilterPattern(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'filter_type' => 'required|string|in:blocked_keywords,blocked_senders,allowed_senders',
            'pattern' => 'required|string|max:255',
        ]);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $filterType = $validated['filter_type'];
        $pattern = trim($validated['pattern']);

        // Obtener array actual
        $currentFilters = $config->{$filterType} ?? [];

        // Verificar si ya existe
        if (in_array($pattern, $currentFilters)) {
            return response()->json([
                'success' => false,
                'message' => 'Este patrón ya existe en el filtro',
            ], 400);
        }

        // Agregar el nuevo patrón
        $currentFilters[] = $pattern;

        $config->update([
            $filterType => $currentFilters,
        ]);

        Log::info('➕ Patrón agregado al filtro', [
            'user_id' => $user->id,
            'filter_type' => $filterType,
            'pattern' => $pattern,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patrón agregado exitosamente',
            'filter_type' => $filterType,
            'patterns' => $currentFilters,
        ]);
    }

    /**
     * Eliminar un patrón de un filtro específico
     */
    public function removeFilterPattern(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'filter_type' => 'required|string|in:blocked_keywords,blocked_senders,allowed_senders,response_allowed_categories,response_blocked_categories',
            'pattern' => 'required|string',
        ]);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $filterType = $validated['filter_type'];
        $pattern = $validated['pattern'];

        // Obtener array actual
        $currentFilters = $config->{$filterType} ?? [];

        // Eliminar el patrón
        $currentFilters = array_values(array_filter($currentFilters, fn($item) => $item !== $pattern));

        $config->update([
            $filterType => $currentFilters,
        ]);

        Log::info('➖ Patrón eliminado del filtro', [
            'user_id' => $user->id,
            'filter_type' => $filterType,
            'pattern' => $pattern,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patrón eliminado exitosamente',
            'filter_type' => $filterType,
            'patterns' => $currentFilters,
        ]);
    }

    /**
     * Agregar/quitar categoría del filtro de categorías
     */
    public function toggleCategory(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category' => 'required|string|in:trabajo,personal,promociones,newsletters,social',
            'filter_type' => 'required|string|in:allowed,blocked',
        ]);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $category = $validated['category'];
        $filterType = $validated['filter_type'] === 'allowed'
            ? 'response_allowed_categories'
            : 'response_blocked_categories';

        $currentCategories = $config->{$filterType} ?? [];

        // Toggle: si existe, quitar; si no existe, agregar
        if (in_array($category, $currentCategories)) {
            $currentCategories = array_values(array_filter($currentCategories, fn($c) => $c !== $category));
            $action = 'removed';
        } else {
            $currentCategories[] = $category;
            $action = 'added';
        }

        $config->update([
            $filterType => $currentCategories,
        ]);

        Log::info('🔄 Categoría toggleada', [
            'user_id' => $user->id,
            'category' => $category,
            'filter_type' => $filterType,
            'action' => $action,
        ]);

        return response()->json([
            'success' => true,
            'message' => $action === 'added' ? 'Categoría agregada' : 'Categoría eliminada',
            'category' => $category,
            'filter_type' => $validated['filter_type'],
            'categories' => $currentCategories,
            'action' => $action,
        ]);
    }

    /**
     * Obtener sugerencias de contactos con estadísticas (NUEVO)
     */
    public function getContactsSuggestions()
    {
        $user = Auth::user();

        // Obtener todos los remitentes únicos con estadísticas
        $contacts = Email::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->select('from_email', 'from_name')
        ->selectRaw('COUNT(*) as email_count')
        ->selectRaw('MAX(received_at) as last_received')
        ->selectRaw('MIN(received_at) as first_received')
        ->groupBy('from_email', 'from_name')
        ->orderByDesc('email_count')
        ->get()
        ->map(function ($contact) {
            $email = strtolower($contact->from_email ?? '');
            $name = $contact->from_name ?? 'Sin nombre';

            // Detectar características automáticamente
            $isNoreply = str_contains($email, 'noreply') || str_contains($email, 'no-reply') || str_contains($email, 'donotreply');
            $isMarketing = str_contains($email, 'marketing') || str_contains($email, 'promo') || str_contains($email, 'newsletter');

            // Categoría más común de este remitente
            $categoryInfo = Email::whereHas('emailAccount', function ($query) use ($contact) {
                $query->where('user_id', auth()->id());
            })
            ->where('from_email', $contact->from_email)
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('COUNT(*) as cat_count')
            ->groupBy('category')
            ->orderByDesc('cat_count')
            ->first();

            $category = $categoryInfo->category ?? null;

            // Sugerencia de acción
            $suggestedAction = 'neutral';
            if ($isNoreply || $isMarketing || in_array($category, ['promociones', 'newsletters', 'updates'])) {
                $suggestedAction = 'block';
            }

            return [
                'email' => $contact->from_email,
                'name' => $name,
                'count' => $contact->email_count,
                'last_received' => $contact->last_received,
                'first_received' => $contact->first_received,
                'is_noreply' => $isNoreply,
                'is_marketing' => $isMarketing,
                'category' => $category,
                'suggested_action' => $suggestedAction, // 'block', 'allow', 'neutral'
            ];
        });

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
            'total' => $contacts->count(),
        ]);
    }

    /**
     * Obtener sugerencias de palabras clave bloqueadas (NUEVO)
     */
    public function getKeywordsSuggestions()
    {
        $user = Auth::user();

        // Palabras clave comunes encontradas en emails
        $commonKeywords = [
            'unsubscribe', 'darse de baja', 'cancelar suscripción',
            'oferta', 'descuento', 'promoción', 'sale', 'black friday',
            'newsletter', 'boletín', 'actualización',
            'no reply', 'do not reply', 'noreply',
        ];

        // Buscar cuántas veces aparecen estas keywords en los emails del usuario
        $suggestions = [];

        foreach ($commonKeywords as $keyword) {
            $count = Email::whereHas('emailAccount', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where(function ($query) use ($keyword) {
                $query->where('subject', 'LIKE', "%{$keyword}%")
                      ->orWhere('body_text', 'LIKE', "%{$keyword}%");
            })
            ->count();

            if ($count > 0) {
                $suggestions[] = [
                    'keyword' => $keyword,
                    'count' => $count,
                    'type' => $this->getKeywordType($keyword),
                ];
            }
        }

        // Ordenar por cantidad de apariciones
        usort($suggestions, fn($a, $b) => $b['count'] <=> $a['count']);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'total' => count($suggestions),
        ]);
    }

    /**
     * Helper: Determinar tipo de keyword
     */
    private function getKeywordType($keyword)
    {
        $marketingKeywords = ['oferta', 'descuento', 'promoción', 'sale', 'black friday'];
        $newsletterKeywords = ['newsletter', 'boletín', 'actualización'];
        $unsubscribeKeywords = ['unsubscribe', 'darse de baja', 'cancelar suscripción'];

        if (in_array($keyword, $unsubscribeKeywords)) {
            return 'unsubscribe';
        }
        if (in_array($keyword, $marketingKeywords)) {
            return 'marketing';
        }
        if (in_array($keyword, $newsletterKeywords)) {
            return 'newsletter';
        }

        return 'other';
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

    /**
     * Analiza el estilo de escritura del usuario desde sus emails enviados (Gmail SENT)
     * y guarda el perfil en vera_configs.writing_style
     */
    public function analyzeWritingStyle(VeraWritingStyleService $service)
    {
        $user = Auth::user();

        try {
            $result = $service->analyzeAndSave($user->id);
            $profile = $result['profile'];

            $toneLabels = [
                'formal'       => 'Formal',
                'professional' => 'Profesional',
                'casual'       => 'Casual',
                'friendly'     => 'Amigable',
            ];

            $toneRaw = $profile['tone'] ?? 'professional';
            // Normalizar: 'friendly' no existe en el select, mapear a 'casual'
            $toneForSelect = in_array($toneRaw, ['formal', 'professional', 'casual'])
                ? $toneRaw
                : 'professional';

            return response()->json([
                'success' => true,
                'message' => "Vera ha aprendido tu estilo analizando {$result['emails_analyzed']} emails enviados.",
                'profile' => [
                    'tone'             => $toneLabels[$toneRaw] ?? ucfirst($toneRaw),
                    'tone_raw'         => $toneForSelect,
                    'formality'        => round(($profile['formality_score'] ?? 0.5) * 100) . '%',
                    'uses_emojis'      => $profile['uses_emojis'] ?? false,
                    'greetings'        => $profile['greetings'] ?? [],
                    'closings'         => $profile['closings'] ?? [],
                    'analysis_summary' => $profile['analysis_summary'] ?? '',
                    'emails_analyzed'  => $result['emails_analyzed'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en analyzeWritingStyle', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Aplicar directamente la acción de una sugerencia semanal.
     * Cada action_type modifica una config específica de Vera.
     */
    public function applyWeeklySuggestion(Request $request)
    {
        $validated = $request->validate([
            'suggestion_id' => 'required|string|max:50',
            'action_type'   => 'required|string|in:enable_newsletter_archive,enable_auto_response,configure_writing_style,enable_priority_sort',
        ]);

        $config = VeraConfig::where('user_id', Auth::id())->firstOrFail();

        $message = '';

        switch ($validated['action_type']) {
            case 'enable_newsletter_archive':
                $config->update(['newsletter_handling' => 'auto_archive']);
                $message = '¡Activado! Vera archivará automáticamente tus newsletters y promociones.';
                break;

            case 'enable_auto_response':
                $config->update([
                    'auto_response_enabled'          => true,
                    'auto_response_mode'             => 'important_only',
                    'auto_response_require_approval' => true,
                ]);
                $message = '¡Activado! Vera sugerirá respuestas para tus emails importantes.';
                break;

            case 'configure_writing_style':
                // No hay config directa, redirigir a la sección correcta
                return response()->json([
                    'success'  => true,
                    'redirect' => route('vera.config'),
                    'message'  => 'Te llevamos a la configuración de estilo.',
                ]);

            case 'enable_priority_sort':
                $config->update(['autonomy_level' => 'moderate']);
                $message = '¡Activado! Vera priorizará y destacará tus emails urgentes.';
                break;
        }

        // También descartar la sugerencia (ya no es necesario mostrarla)
        $suggestions = $config->weekly_suggestions ?? [];
        $suggestions['dismissed_ids'][] = $validated['suggestion_id'];
        $config->update(['weekly_suggestions' => $suggestions]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Confirmar un insight aprendido por Vera.
     * Marca el insight como confirmado y aplica la acción asociada si existe.
     */
    public function confirmInsight(Request $request)
    {
        $validated = $request->validate([
            'insight_id' => 'required|string|max:50',
        ]);

        $config = VeraConfig::where('user_id', Auth::id())->firstOrFail();

        $suggestions = $config->weekly_suggestions ?? [];
        $insights = $suggestions['confirmable_insights'] ?? [];

        // Encontrar el insight y marcarlo como confirmado
        $insight = null;
        foreach ($insights as $i) {
            if (($i['id'] ?? '') === $validated['insight_id']) {
                $insight = $i;
                break;
            }
        }

        if (!$insight) {
            return response()->json(['success' => false, 'message' => 'Insight no encontrado'], 404);
        }

        // Aplicar acción si existe
        if (!empty($insight['action_on_confirm'])) {
            match ($insight['action_on_confirm']) {
                'prioritize_urgent' => $config->update(['autonomy_level' => 'moderate']),
                default => null,
            };
        }

        // Eliminar el insight de la lista (ya no necesita confirmación)
        $suggestions['confirmable_insights'] = array_values(
            array_filter($insights, fn($i) => ($i['id'] ?? '') !== $validated['insight_id'])
        );
        $config->update(['weekly_suggestions' => $suggestions]);

        Log::info('✅ Vera insight confirmado', [
            'user_id'    => Auth::id(),
            'insight_id' => $validated['insight_id'],
            'statement'  => $insight['statement'] ?? '',
        ]);

        return response()->json(['success' => true, 'message' => 'Gracias, Vera lo tendrá en cuenta.']);
    }

    /**
     * Denegar un insight aprendido por Vera.
     * Desactiva el chunk de memoria relacionado y elimina el insight.
     */
    public function denyInsight(Request $request)
    {
        $validated = $request->validate([
            'insight_id' => 'required|string|max:50',
        ]);

        $config = VeraConfig::where('user_id', Auth::id())->firstOrFail();

        $suggestions = $config->weekly_suggestions ?? [];
        $insights = $suggestions['confirmable_insights'] ?? [];

        // Eliminar el insight de la lista
        $suggestions['confirmable_insights'] = array_values(
            array_filter($insights, fn($i) => ($i['id'] ?? '') !== $validated['insight_id'])
        );
        $config->update(['weekly_suggestions' => $suggestions]);

        Log::info('❌ Vera insight denegado', [
            'user_id'    => Auth::id(),
            'insight_id' => $validated['insight_id'],
        ]);

        return response()->json(['success' => true, 'message' => 'Entendido, Vera lo ignorará.']);
    }

    /**
     * Descartar una sugerencia semanal de Vera.
     * Añade el ID a dismissed_ids para que no vuelva a aparecer.
     */
    public function dismissWeeklySuggestion(Request $request)
    {
        $validated = $request->validate([
            'suggestion_id' => 'required|string|max:50',
        ]);

        $config = VeraConfig::where('user_id', Auth::id())->firstOrFail();

        $suggestions = $config->weekly_suggestions ?? [];

        if (!isset($suggestions['dismissed_ids'])) {
            $suggestions['dismissed_ids'] = [];
        }

        if (!in_array($validated['suggestion_id'], $suggestions['dismissed_ids'])) {
            $suggestions['dismissed_ids'][] = $validated['suggestion_id'];
        }

        $config->update(['weekly_suggestions' => $suggestions]);

        return response()->json(['success' => true]);
    }
}
