<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Models\VeraMemoryChunk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VeraController extends Controller
{
    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config || !$config->onboarding_completed) {
            return redirect()->route('vera.onboarding');
        }

        $stats = $this->getMeetingStats($user);

        $upcomingMeetings = Meeting::forUser($user->id)
            ->upcoming()
            ->confirmed()
            ->limit(5)
            ->get();

        $pendingMeetings = Meeting::forUser($user->id)
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $recentActivity = VeraActivityLog::byUser($user->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('vera.dashboard', compact('config', 'stats', 'upcomingMeetings', 'pendingMeetings', 'recentActivity'));
    }

    // ─── Onboarding ───────────────────────────────────────────────────────────

    public function onboarding()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        // Si ya completó onboarding, ir al dashboard
        if ($config && $config->onboarding_completed) {
            return redirect()->route('vera.dashboard');
        }

        // Calcular el paso de inicio según lo que ya tiene guardado
        $startStep = 1;
        if ($config) {
            if ($config->business_name)   $startStep = 2;
            if ($config->working_hours)   $startStep = 3;
            if ($config->vera_signature || $config->unknown_response) $startStep = 4;
        }

        return view('vera.onboarding', compact('config', 'startStep'));
    }

    public function saveOnboarding(Request $request)
    {
        $user = Auth::user();
        $step = $request->input('step', 1);

        return match ((int) $step) {
            1 => $this->saveOnboardingStep1($request, $user),
            2 => $this->saveOnboardingStep2($request, $user),
            3 => $this->saveOnboardingStep3($request, $user),
            4 => $this->saveOnboardingStep4($request, $user),
            default => back()->withErrors(['step' => 'Paso no válido']),
        };
    }

    private function saveOnboardingStep1(Request $request, $user)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:100',
            'owner_name'    => 'required|string|max:100',
            'tone'          => 'required|in:formal,cercano,profesional',
            'language'      => 'required|in:es,en,ca',
            'timezone'      => 'required|string|max:64',
        ]);

        VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json(['success' => true, 'next_step' => 2]);
    }

    private function saveOnboardingStep2(Request $request, $user)
    {
        $validated = $request->validate([
            'working_hours'    => 'required|array',
            'meeting_duration' => 'required|integer|in:30,45,60',
            'buffer_minutes'   => 'required|integer|in:0,10,15,30',
        ]);

        VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'working_hours'    => $validated['working_hours'],
                'meeting_duration' => $validated['meeting_duration'],
                'buffer_minutes'   => $validated['buffer_minutes'],
            ]
        );

        return response()->json(['success' => true, 'next_step' => 3]);
    }

    private function saveOnboardingStep3(Request $request, $user)
    {
        $validated = $request->validate([
            'unknown_response'    => 'nullable|string|max:500',
            'escalation_triggers' => 'nullable|array',
            'escalation_triggers.*' => 'string|max:50',
            'vera_signature'      => 'nullable|string|max:150',
            'business_description'=> 'nullable|string|max:1500',
        ]);

        VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'unknown_response'    => $validated['unknown_response'],
                'escalation_triggers' => $validated['escalation_triggers'] ?? [],
                'vera_signature'      => $validated['vera_signature'],
            ]
        );

        // Guardar descripción del negocio en RAG
        if (!empty($validated['business_description'])) {
            VeraMemoryChunk::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'user_profile', 'title' => 'Descripción del negocio'],
                [
                    'content'        => $validated['business_description'],
                    'keywords_text'  => 'negocio empresa sector cliente servicio producto',
                    'source'         => 'onboarding',
                    'is_active'      => true,
                    'relevance_tags' => ['user_profile'],
                ]
            );
        }

        return response()->json(['success' => true, 'next_step' => 4]);
    }

    private function saveOnboardingStep4(Request $request, $user)
    {
        // Paso 4: conexión de canales — solo marca onboarding completo
        // El OAuth de Google se gestiona en GoogleCalendarAuthController
        $config = VeraConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'onboarding_completed'    => true,
                'onboarding_completed_at' => now(),
                'mode'                    => 'supervised', // Siempre empezar en supervisado
            ]
        );

        Log::info('✅ Onboarding completado', ['user_id' => $user->id]);

        return response()->json([
            'success'  => true,
            'redirect' => route('vera.dashboard'),
        ]);
    }

    // ─── Configuración ────────────────────────────────────────────────────────

    public function config()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config) {
            return redirect()->route('vera.onboarding');
        }

        $businessDescription = VeraMemoryChunk::where('user_id', $user->id)
            ->where('type', 'user_profile')
            ->where('title', 'Descripción del negocio')
            ->where('is_active', true)
            ->value('content') ?? '';

        return view('vera.config', compact('config', 'businessDescription'));
    }

    public function updateConfig(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'business_name'        => 'sometimes|string|max:100',
            'owner_name'           => 'sometimes|string|max:100',
            'tone'                 => 'sometimes|in:formal,cercano,profesional',
            'language'             => 'sometimes|in:es,en,ca',
            'working_hours'        => 'sometimes|array',
            'meeting_duration'     => 'sometimes|integer|in:30,45,60',
            'buffer_minutes'       => 'sometimes|integer|in:0,10,15,30',
            'unknown_response'     => 'nullable|string|max:500',
            'escalation_triggers'  => 'nullable|array',
            'escalation_triggers.*'=> 'string|max:50',
            'vera_signature'       => 'nullable|string|max:150',
            'mode'                 => 'sometimes|in:supervised,autonomous',
            'confidence_threshold' => 'sometimes|numeric|min:0.5|max:1',
            'business_description' => 'nullable|string|max:2000',
        ]);

        $businessDescription = $validated['business_description'] ?? null;
        unset($validated['business_description']);

        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $config->update($validated);

        if (!is_null($businessDescription)) {
            VeraMemoryChunk::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'user_profile', 'title' => 'Descripción del negocio'],
                [
                    'content'        => $businessDescription,
                    'keywords_text'  => 'negocio empresa sector cliente servicio producto',
                    'source'         => 'config',
                    'is_active'      => true,
                    'relevance_tags' => ['user_profile'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada.',
        ]);
    }

    // Alias for user-profile route (kept for backward compat with route definition)
    public function saveUserProfile(Request $request)
    {
        return $this->updateConfig($request);
    }

    // ─── Estadísticas (API) ───────────────────────────────────────────────────

    public function stats()
    {
        $user  = Auth::user();
        $stats = $this->getMeetingStats($user);

        return response()->json($stats);
    }

    public function checkOnboardingNeeded()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        $needsOnboarding = !$config || !$config->onboarding_completed;

        return response()->json([
            'needs_onboarding' => $needsOnboarding,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getMeetingStats($user): array
    {
        $base = Meeting::forUser($user->id);

        return [
            'today'       => (clone $base)->whereDate('scheduled_at', today())->confirmed()->count(),
            'this_week'   => (clone $base)->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])->confirmed()->count(),
            'this_month'  => (clone $base)->whereMonth('scheduled_at', now()->month)->confirmed()->count(),
            'pending'     => (clone $base)->where('status', 'pending')->count(),
            'total'       => (clone $base)->confirmed()->count(),
        ];
    }
}
