<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Models\VeraMemoryChunk;
use App\Models\VeraService;
use App\Services\VeraMemoryService;
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

        // Incidencias/quejas escaladas y aún no gestionadas (Bandeja de gestión).
        $escalations = \App\Models\MeetingRequest::forUser($user->id)
            ->where('state', 'escalated')
            ->whereNull('context->handled_at')
            ->with('contact')
            ->latest()->get();

        // Consultas sin respuesta pendientes (Fase C): el dueño responde vía Vera
        // y puede guardarlas como FAQ para que Vera aprenda.
        $inquiries = \App\Models\VeraInquiry::forUser($user->id)
            ->pending()
            ->with('contact')
            ->latest()->get();

        [, $bandejaFingerprint] = self::bandejaState($user->id);

        return view('vera.dashboard', compact('config', 'stats', 'upcomingMeetings', 'pendingMeetings', 'recentActivity', 'escalations', 'inquiries', 'bandejaFingerprint'));
    }

    /**
     * Estado de la Bandeja para el sondeo en vivo: nº de pendientes y una huella
     * que cambia con cualquier novedad (mensaje nuevo en un hilo, cita pendiente
     * nueva, incidencia cerrada...). La comparten el dashboard y el ping.
     */
    public static function bandejaState(int $userId): array
    {
        $esc = \App\Models\MeetingRequest::forUser($userId)
            ->where('state', 'escalated')
            ->whereNull('context->handled_at')
            ->orderBy('id')
            ->get(['id', 'updated_at']);

        $pend = Meeting::forUser($userId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get(['id', 'updated_at']);

        $inq = \App\Models\VeraInquiry::forUser($userId)
            ->pending()
            ->orderBy('id')
            ->get(['id', 'updated_at']);

        $pending     = $esc->count() + $pend->count() + $inq->count();
        $fingerprint = md5(
            $esc->map(fn ($r) => $r->id . '|' . $r->updated_at)->implode(',')
            . '#' . $pend->map(fn ($m) => $m->id . '|' . $m->updated_at)->implode(',')
            . '#' . $inq->map(fn ($i) => $i->id . '|' . $i->updated_at)->implode(',')
        );

        return [$pending, $fingerprint];
    }

    /** Sondeo JS de la Bandeja: contador de pendientes + huella de cambios. */
    public function bandejaPing()
    {
        [$pending, $fingerprint] = self::bandejaState(Auth::id());

        return response()->json(['pending' => $pending, 'fingerprint' => $fingerprint]);
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
            if ($startStep >= 4) $startStep = 5; // canales es el paso final
        }

        $onboardingServiceTemplates = collect(VeraService::templates())
            ->mapWithKeys(fn($t, $k) => [$k => $t['services']])
            ->toArray();

        $existingOnboardingServices = VeraService::where('user_id', $user->id)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['name', 'duration_minutes'])
            ->toArray();

        return view('vera.onboarding', compact('config', 'startStep', 'onboardingServiceTemplates', 'existingOnboardingServices'));
    }

    public function saveOnboarding(Request $request)
    {
        $user = Auth::user();
        $step = $request->input('step', 1);

        return match ((int) $step) {
            1 => $this->saveOnboardingStep1($request, $user),
            2 => $this->saveOnboardingStep2($request, $user),
            3 => response()->json(['success' => true]), // servicios guardados via /vera/services
            4 => $this->saveOnboardingStep3($request, $user),
            5 => $this->saveOnboardingStep4($request, $user),
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
            'escalation_triggers.*' => 'string|max:80',
            'vera_signature'      => 'nullable|string|max:150',
            'business_description'=> 'nullable|string|max:1500',
        ], [
            'escalation_triggers.*.max'    => 'Cada palabra o frase clave no puede pasar de 80 caracteres.',
            'escalation_triggers.*.string' => 'Las palabras clave deben ser texto.',
            'unknown_response.max'         => 'La respuesta cuando Vera no sabe no puede pasar de 500 caracteres.',
            'vera_signature.max'           => 'La firma de Vera no puede pasar de 150 caracteres.',
            'business_description.max'     => 'La descripción del negocio no puede pasar de 1500 caracteres.',
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

        // Servicios cargados (chunks product_info manuales). El precio se guarda al
        // final del contenido como " | Precio: X"; lo separamos para el formulario.
        $services = VeraMemoryChunk::where('user_id', $user->id)
            ->where('type', 'product_info')
            ->where('source', 'manual')
            ->where('is_active', true)
            ->get()
            ->map(function ($c) {
                $price = '';
                $desc  = $c->content;
                if (preg_match('/^(.*)\s\|\sPrecio:\s(.+)$/s', $c->content, $m)) {
                    $desc  = trim($m[1]);
                    $price = trim($m[2]);
                }
                return ['name' => $c->title, 'price' => $price, 'description' => $desc];
            })->values();

        // FAQs cargadas (chunks faq manuales). El contenido es "Pregunta: ..\nRespuesta: .."
        $faqs = VeraMemoryChunk::where('user_id', $user->id)
            ->where('type', 'faq')
            ->where('source', 'manual')
            ->where('is_active', true)
            ->get()
            ->map(function ($c) {
                $answer = $c->content;
                if (preg_match('/Respuesta:\s(.+)$/s', $c->content, $m)) {
                    $answer = trim($m[1]);
                }
                return ['question' => $c->title, 'answer' => $answer];
            })->values();

        $veraServices = VeraService::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'name'             => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'price'            => $s->price ?? '',
                'description'      => $s->description ?? '',
                'editing'          => false,
            ])->values();

        $serviceTemplates = VeraService::templates();

        return view('vera.config', compact('config', 'businessDescription', 'services', 'faqs', 'veraServices', 'serviceTemplates'));
    }

    public function updateConfig(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'business_name'        => 'sometimes|string|max:100',
            'owner_name'           => 'sometimes|string|max:100',
            'notification_email'   => 'nullable|email|max:150',
            'tone'                 => 'sometimes|in:formal,cercano,profesional',
            'language'             => 'sometimes|in:es,en,ca',
            'timezone'             => 'sometimes|timezone',
            'business_address'     => 'nullable|string|max:500',
            'maps_url'             => 'nullable|string|max:500',
            'working_hours'        => 'sometimes|array',
            'meeting_duration'     => 'sometimes|integer|in:30,45,60',
            'buffer_minutes'       => 'sometimes|integer|in:0,10,15,30',
            'reminder_1_hours'     => 'sometimes|integer|in:12,24,48,72',
            'reminder_2_hours'     => 'sometimes|integer|in:1,2,3',
            'unknown_response'     => 'nullable|string|max:500',
            'escalation_triggers'  => 'nullable|array',
            'escalation_triggers.*'=> 'string|max:50',
            'vera_signature'       => 'nullable|string|max:150',
            'mode'                 => 'sometimes|in:supervised,autonomous',
            'confidence_threshold' => 'sometimes|numeric|min:0.5|max:1',
            'business_description' => 'nullable|string|max:2000',
            'auto_confirm_bookings'=> 'sometimes|boolean',
            'services'             => 'nullable|array',
            'services.*.name'      => 'nullable|string|max:120',
            'services.*.price'     => 'nullable|string|max:60',
            'services.*.description'=> 'nullable|string|max:500',
            'faqs'                 => 'nullable|array',
            'faqs.*.question'      => 'nullable|string|max:200',
            'faqs.*.answer'        => 'nullable|string|max:1000',
        ]);

        $businessDescription = $validated['business_description'] ?? null;
        $services            = $validated['services'] ?? null;
        $faqs                = $validated['faqs'] ?? null;
        unset($validated['business_description'], $validated['services'], $validated['faqs']);

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

        // Servicios y FAQs → memoria RAG. La config es la fuente de verdad: si el
        // formulario envía las listas, borramos los manuales anteriores y recreamos.
        // (Si no se envían las claves, no tocamos nada — guardados parciales seguros.)
        $memory = new VeraMemoryService();

        if (!is_null($services)) {
            $this->resetManualChunks($user->id, 'product_info', $memory);
            foreach ($services as $s) {
                $name = trim($s['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $desc  = trim($s['description'] ?? '');
                $price = trim($s['price'] ?? '');
                // Guardamos el precio dentro del contenido con un separador parseable.
                $content = $price !== '' ? trim($desc . ' | Precio: ' . $price) : $desc;
                $memory->addProductInfo($user->id, $name, $content);
            }
        }

        if (!is_null($faqs)) {
            $this->resetManualChunks($user->id, 'faq', $memory);
            foreach ($faqs as $f) {
                $q = trim($f['question'] ?? '');
                $a = trim($f['answer'] ?? '');
                if ($q === '' || $a === '') {
                    continue;
                }
                $memory->addFAQ($user->id, $q, $a);
            }
        }

        // Sincronización AUTOMÁTICA del conocimiento de Vera a ChromaDB, en segundo
        // plano (cola): si el negocio cambia descripción/servicios/FAQs, Vera queda
        // actualizada al instante sin que nadie ejecute comandos. En cola para no
        // bloquear el guardado y reintentar si el ai-service tarda.
        if (!is_null($businessDescription) || !is_null($services) || !is_null($faqs)) {
            try {
                // Sincroniza el conocimiento NUEVO/actualizado a ChromaDB...
                \Illuminate\Support\Facades\Artisan::queue('vera:sync-memory-to-chroma', ['--user' => (string) $user->id]);
                // ...y elimina de ChromaDB el conocimiento VIEJO (chunks desactivados al
                // editar), para que Vera nunca responda con info obsoleta. Ambas en cola.
                \Illuminate\Support\Facades\Artisan::queue('vera:cleanup-chroma', ['--user' => (string) $user->id]);
            } catch (\Throwable $e) {
                \Log::warning('No se pudo encolar la sincronización/limpieza de memoria de Vera', ['error' => $e->getMessage()]);
            }
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

    /**
     * Desactiva los chunks manuales de un tipo (en MySQL y ChromaDB) antes de
     * recrearlos desde el formulario de configuración. Así la config es la fuente
     * de verdad y editar/borrar en el formulario se refleja sin duplicar.
     */
    private function resetManualChunks(int $userId, string $type, VeraMemoryService $memory): void
    {
        $chunks = VeraMemoryChunk::where('user_id', $userId)
            ->where('type', $type)
            ->where('source', 'manual')
            ->where('is_active', true)
            ->get();

        foreach ($chunks as $chunk) {
            $memory->deactivateChunk($userId, $chunk->id);
        }
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

    // ─── Servicios ────────────────────────────────────────────────────────────

    public function storeService(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'name'             => 'required|string|max:120',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'price'            => 'nullable|string|max:60',
            'description'      => 'nullable|string|max:500',
        ]);

        $sort = VeraService::where('user_id', $user->id)->max('sort_order') + 1;
        $svc  = VeraService::create(array_merge($data, ['user_id' => $user->id, 'sort_order' => $sort]));

        $this->syncServicesToMemory($user);

        return response()->json(['ok' => true, 'service' => $svc]);
    }

    public function updateService(Request $request, VeraService $service)
    {
        $user = Auth::user();
        if ($service->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'             => 'sometimes|string|max:120',
            'duration_minutes' => 'sometimes|integer|min:5|max:480',
            'price'            => 'nullable|string|max:60',
            'description'      => 'nullable|string|max:500',
            'is_active'        => 'sometimes|boolean',
            'sort_order'       => 'sometimes|integer',
        ]);

        $service->update($data);
        $this->syncServicesToMemory($user);
        return response()->json(['ok' => true]);
    }

    public function destroyService(VeraService $service)
    {
        $user = Auth::user();
        if ($service->user_id !== $user->id) {
            abort(403);
        }
        $service->delete();
        $this->syncServicesToMemory($user);
        return response()->json(['ok' => true]);
    }

    public function applyServiceTemplate(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate(['template' => 'required|string']);
        $templates = VeraService::templates();

        // Caso especial: limpiar todos los servicios sin aplicar plantilla
        if ($data['template'] === '__clear__') {
            VeraService::where('user_id', $user->id)->delete();
            $this->syncServicesToMemory($user);
            return response()->json(['ok' => true, 'services' => []]);
        }

        if (!isset($templates[$data['template']])) {
            return response()->json(['error' => 'Plantilla no válida'], 422);
        }

        // Elimina los servicios actuales y aplica la plantilla
        VeraService::where('user_id', $user->id)->delete();
        foreach ($templates[$data['template']]['services'] as $i => $svc) {
            VeraService::create(array_merge($svc, ['user_id' => $user->id, 'sort_order' => $i]));
        }

        $this->syncServicesToMemory($user);

        $services = VeraService::where('user_id', $user->id)->orderBy('sort_order')->get();
        return response()->json(['ok' => true, 'services' => $services]);
    }

    private function syncServicesToMemory($user): void
    {
        $aiUrl    = config('services.ai_service.url');
        $aiSecret = config('services.ai_service.secret');

        // 1. Borrar FÍSICAMENTE todos los product_info de ChromaDB (incluye huérfanos
        //    que ya no están en MySQL pero siguen activos en el vector store).
        try {
            \Illuminate\Support\Facades\Http::timeout(8)
                ->withHeaders(['X-Service-Token' => $aiSecret])
                ->delete("{$aiUrl}/memory/type/{$user->id}/product_info");
        } catch (\Throwable $e) {
            \Log::warning('syncServicesToMemory: no se pudo limpiar product_info en ChromaDB', ['error' => $e->getMessage()]);
        }

        // 2. Desactivar en MySQL y eliminar registros previos de product_info
        VeraMemoryChunk::where('user_id', $user->id)
            ->where('type', 'product_info')
            ->delete();

        // 3. Crear chunks frescos desde VeraService
        $services = VeraService::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($services as $svc) {
            $parts = [];
            if ($svc->description) $parts[] = $svc->description;
            $parts[] = 'Duracion: ' . $svc->duration_minutes . ' minutos.';
            if ($svc->price) {
                $price = trim((string) $svc->price);
                if ($price !== '' && !str_contains($price, '€') && !str_contains($price, 'euro')) {
                    $price .= '€';
                }
                $parts[] = 'Precio: ' . $price . '.';
            }

            VeraMemoryChunk::create([
                'user_id'        => $user->id,
                'type'           => 'product_info',
                'title'          => $svc->name,
                'content'        => implode(' ', $parts),
                'keywords_text'  => $svc->name . ' ' . ($svc->description ?? ''),
                'relevance_tags' => [],
                'source'         => 'vera_service',
                'is_active'      => true,
            ]);
        }

        // 4. Sincronizar los chunks nuevos a ChromaDB (vía queue — no bloquea la respuesta)
        try {
            \Illuminate\Support\Facades\Artisan::queue('vera:sync-memory-to-chroma', ['--user' => (string) $user->id]);
        } catch (\Throwable $e) {
            \Log::warning('syncServicesToMemory: error al encolar sync', ['error' => $e->getMessage()]);
        }
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
