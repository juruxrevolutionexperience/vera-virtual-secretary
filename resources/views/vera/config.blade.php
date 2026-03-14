@php
    $ws = is_array($config->writing_style ?? null) ? $config->writing_style : [];
    $styleProfileData = !empty($ws['analyzed_at']) ? [
        'tone'             => ucfirst($ws['tone'] ?? 'professional'),
        'tone_raw'         => $ws['tone'] ?? 'professional',
        'formality'        => round(($ws['formality_score'] ?? 0.5) * 100) . '%',
        'greetings'        => $ws['greetings'] ?? [],
        'closings'         => $ws['closings'] ?? [],
        'analysis_summary' => $ws['analysis_summary'] ?? '',
    ] : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuración - Vera AI</title>
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased bg-gray-50 min-h-screen">

<div class="max-w-3xl mx-auto px-4 py-10" x-data="veraConfig()">

    {{-- ── HEADER ────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuración de Vera</h1>
            <p class="text-sm text-gray-500 mt-0.5">Ajusta cómo Vera gestiona y responde tus emails</p>
        </div>
        <a href="{{ route('emails.index') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver
        </a>
    </div>

    {{-- ── NOTIFICATION ──────────────────────────────────── --}}
    <div x-show="notification.show"
         x-transition
         x-cloak
         :class="notification.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
         class="mb-6 p-4 rounded-xl border text-sm font-medium flex items-center gap-2">
        <span x-text="notification.type === 'success' ? '✅' : '❌'"></span>
        <span x-text="notification.message"></span>
    </div>

    {{-- ── STATS ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-4 gap-3 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Emails totales</p>
            <p class="text-xl font-bold text-gray-900">{{ $stats['total_emails'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Procesados</p>
            <p class="text-xl font-bold text-purple-600">{{ $stats['processed_emails'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Respuestas</p>
            <p class="text-xl font-bold text-blue-600">{{ $stats['responses_generated'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Archivados</p>
            <p class="text-xl font-bold text-green-600">{{ $stats['auto_archived'] }}</p>
        </div>
    </div>

    <form @submit.prevent="saveConfig" class="space-y-6">

        {{-- ══════════════════════════════════════════════════
             SECCIÓN 1 — ¿QUÉ HACE VERA?
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-gray-900 mb-1">¿Qué quieres que haga Vera?</h2>
            <p class="text-sm text-gray-500 mb-5">Elige el modo principal. Puedes cambiarlo en cualquier momento.</p>

            <div class="grid grid-cols-1 gap-3">

                {{-- Card 1: Solo organizar --}}
                <label @click="selectMode('filter_focus')"
                       :class="formData.response_mode === 'filter_focus'
                           ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-300'
                           : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'"
                       class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all">
                    <div :class="formData.response_mode === 'filter_focus' ? 'bg-purple-600' : 'bg-gray-200'"
                         class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 transition-colors">
                        <span :class="formData.response_mode === 'filter_focus' ? '' : 'grayscale'">🗂️</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-gray-900">Solo organizar</p>
                            <div :class="formData.response_mode === 'filter_focus' ? 'bg-purple-600 border-purple-600' : 'border-gray-300'"
                                 class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors">
                                <div x-show="formData.response_mode === 'filter_focus'" class="w-2.5 h-2.5 bg-white rounded-full"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">Vera categoriza, filtra y archiva tus emails. Tú decides si responder o no.</p>
                    </div>
                </label>

                {{-- Card 2: Sugerir respuestas --}}
                <label @click="selectMode('suggest_responses')"
                       :class="formData.response_mode === 'suggest_responses'
                           ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-300'
                           : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'"
                       class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all">
                    <div :class="formData.response_mode === 'suggest_responses' ? 'bg-purple-600' : 'bg-gray-200'"
                         class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 transition-colors">
                        <span>💡</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-gray-900">Sugerir respuestas</p>
                            <div :class="formData.response_mode === 'suggest_responses' ? 'bg-purple-600 border-purple-600' : 'border-gray-300'"
                                 class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors">
                                <div x-show="formData.response_mode === 'suggest_responses'" class="w-2.5 h-2.5 bg-white rounded-full"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">Vera prepara borradores de respuesta. Tú los revisas y apruebas antes de enviar.</p>
                        <p class="text-xs text-purple-600 font-medium mt-1">Recomendado para empezar</p>
                    </div>
                </label>

                {{-- Card 3: Responder automáticamente --}}
                <label @click="selectMode('always')"
                       :class="formData.response_mode === 'always'
                           ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-300'
                           : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'"
                       class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all">
                    <div :class="formData.response_mode === 'always' ? 'bg-purple-600' : 'bg-gray-200'"
                         class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 transition-colors">
                        <span>🤖</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-gray-900">Responder automáticamente</p>
                            <div :class="formData.response_mode === 'always' ? 'bg-purple-600 border-purple-600' : 'border-gray-300'"
                                 class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors">
                                <div x-show="formData.response_mode === 'always'" class="w-2.5 h-2.5 bg-white rounded-full"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">Vera responde en tu nombre sin que tengas que aprobar cada email.</p>

                        {{-- Slider de confianza + delay — solo visible en modo "always" --}}
                        <div x-show="formData.response_mode === 'always'" x-transition class="mt-4 pt-4 border-t border-purple-200 space-y-5">

                            {{-- Umbral de confianza --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium text-gray-700">Umbral de confianza</p>
                                    <span class="text-sm font-bold text-purple-600" x-text="Math.round(formData.confidence_threshold * 100) + '%'"></span>
                                </div>
                                <input type="range"
                                       x-model.number="formData.confidence_threshold"
                                       @click.stop
                                       min="0.5" max="1" step="0.05"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600">
                                <div class="flex justify-between text-xs text-gray-400 mt-1">
                                    <span>50% — más emails respondidos</span>
                                    <span>100% — más seguro</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Vera solo envía automáticamente cuando su seguridad supera este umbral.
                                </p>
                            </div>

                            {{-- Tiempo de espera antes de enviar --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium text-gray-700">Tiempo de espera antes de enviar</p>
                                    <span class="text-sm font-bold text-purple-600"
                                          x-text="formData.response_delay_minutes === 0 ? 'Inmediato' :
                                                  formData.response_delay_minutes < 60 ? formData.response_delay_minutes + ' min' :
                                                  (formData.response_delay_minutes / 60) + ' h'"></span>
                                </div>
                                <input type="range"
                                       x-model.number="formData.response_delay_minutes"
                                       @click.stop
                                       min="0" max="240" step="1"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600">
                                <div class="flex justify-between text-xs text-gray-400 mt-1">
                                    <span>Inmediato</span>
                                    <span>4 horas</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Da margen para cancelar si cambias de opinión. La respuesta se enviará tras este tiempo.
                                </p>
                            </div>

                        </div>
                    </div>
                </label>

            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             SECCIÓN 2 — ESTILO DE ESCRITURA
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-gray-900 mb-1">Cómo escribe Vera</h2>
            <p class="text-sm text-gray-500 mb-5">Vera aprende tu estilo leyendo tus emails enviados</p>

            {{-- Estado A: Perfil aprendido --}}
            <div x-show="styleProfile">
                <div class="rounded-xl border-2 border-green-200 bg-green-50 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-green-600">✓</span>
                            <span class="font-semibold text-green-800 text-sm">Vera conoce tu estilo de escritura</span>
                        </div>
                        <button type="button"
                                @click="analyzeStyle()"
                                :disabled="styleLoading"
                                class="text-xs text-green-700 border border-green-300 rounded-lg px-3 py-1 hover:bg-green-100 transition-colors disabled:opacity-50">
                            <span x-show="!styleLoading">Reanalizar</span>
                            <span x-show="styleLoading">Analizando...</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Tono</p>
                            <p class="font-semibold text-gray-800" x-text="styleProfile?.tone"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Formalidad</p>
                            <p class="font-semibold text-gray-800" x-text="styleProfile?.formality"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Saludos</p>
                            <p class="font-medium text-gray-700 italic" x-text="(styleProfile?.greetings || []).join(' · ')"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Despedidas</p>
                            <p class="font-medium text-gray-700 italic" x-text="(styleProfile?.closings || []).join(' · ')"></p>
                        </div>
                    </div>
                    <p x-show="styleProfile?.analysis_summary"
                       class="mt-3 text-xs text-gray-500 border-t border-green-200 pt-2 italic"
                       x-text="styleProfile?.analysis_summary"></p>
                </div>
                <div class="mt-3">
                    <button type="button"
                            @click="showStyleOverride = !showStyleOverride"
                            class="text-xs text-gray-400 hover:text-purple-600 flex items-center gap-1 transition-colors">
                        <span x-text="showStyleOverride ? '▲ Ocultar ajuste manual' : '▼ Sobrescribir tono manualmente'"></span>
                    </button>
                    <div x-show="showStyleOverride" x-transition class="mt-2">
                        <select x-model="formData.writing_style"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm text-sm">
                            <option value="formal">Formal</option>
                            <option value="professional">Profesional</option>
                            <option value="casual">Casual</option>
                        </select>
                        <p class="text-xs text-amber-600 mt-1">Esto sobrescribe el tono aprendido. Los saludos y despedidas se mantienen.</p>
                    </div>
                </div>
            </div>

            {{-- Estado B: Sin perfil --}}
            <div x-show="!styleProfile">
                <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                    <p class="text-2xl mb-2">🤔</p>
                    <p class="font-semibold text-gray-700 mb-1">Vera aún no conoce tu estilo</p>
                    <p class="text-sm text-gray-500 mb-4">Analiza tus emails enviados para que Vera escriba exactamente como tú</p>
                    <button type="button"
                            @click="analyzeStyle()"
                            :disabled="styleLoading"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 text-sm">
                        <span x-show="!styleLoading">✨ Analizar mis emails enviados</span>
                        <span x-show="styleLoading">Analizando...</span>
                    </button>
                    <p class="text-xs text-gray-400 mt-2">Analiza ~50 emails · Coste: &lt;€0.01</p>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Tono mientras tanto</label>
                    <select x-model="formData.writing_style"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm text-sm">
                        <option value="formal">Formal</option>
                        <option value="professional">Profesional</option>
                        <option value="casual">Casual</option>
                    </select>
                </div>
            </div>

            <div x-show="styleError" x-transition
                 class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"
                 x-text="styleError"></div>
        </div>

        {{-- ══════════════════════════════════════════════════
             SECCIÓN 3 — PERFIL DE USUARIO (RAG)
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Cuéntale a Vera quién eres</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Vera usará esta información para personalizar cada respuesta que genere</p>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-purple-100 text-purple-700 rounded-full shrink-0 ml-3">Mejora las respuestas</span>
            </div>

            <div class="mt-5 space-y-4">

                {{-- ¿A qué te dedicas? --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        ¿A qué te dedicas? ¿Cuál es tu negocio?
                    </label>
                    <textarea x-model="userProfile.business_description"
                              rows="2"
                              placeholder="Ej: Soy consultor de marketing digital freelance, trabajo principalmente con startups y PYMEs del sector tecnológico."
                              class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 text-sm shadow-sm resize-none"></textarea>
                </div>

                {{-- ¿Cómo sueles responder? --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        ¿Cómo sueles responder? ¿Hay algún estilo diferente según el tipo de persona?
                    </label>
                    <textarea x-model="userProfile.response_style"
                              rows="2"
                              placeholder="Ej: Con clientes soy formal y directo. Con amigos y colegas soy más cercano. Siempre intento responder el mismo día."
                              class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 text-sm shadow-sm resize-none"></textarea>
                </div>

                {{-- Firma --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        ¿Tienes una firma? (nombre, cargo, teléfono...)
                    </label>
                    <textarea x-model="userProfile.email_signature"
                              rows="2"
                              placeholder="Ej: Un saludo, Carlos López | Consultor Marketing | +34 600 000 000"
                              class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 text-sm shadow-sm resize-none"></textarea>
                </div>

                {{-- Nunca diría --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        ¿Hay algo que NUNCA dirías en un email?
                    </label>
                    <textarea x-model="userProfile.never_say"
                              rows="2"
                              placeholder="Ej: Nunca prometo plazos sin consultarme antes. No doy descuentos sin negociar. Nunca uso emojis en emails de trabajo."
                              class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 text-sm shadow-sm resize-none"></textarea>
                </div>

            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-gray-400">Vera usará esto como contexto en cada respuesta que genere para ti</p>
                <button type="button"
                        @click="saveUserProfile()"
                        :disabled="profileSaving"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50">
                    <span x-show="!profileSaving">Guardar perfil</span>
                    <span x-show="profileSaving">Guardando...</span>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             SECCIÓN 4 — AJUSTES AVANZADOS (acordeón)
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <button type="button"
                    @click="showAdvanced = !showAdvanced"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-base font-bold text-gray-900">Ajustes avanzados</span>
                    <span class="text-xs text-gray-400 font-normal">Horario · Remitentes · Palabras clave</span>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="showAdvanced && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="showAdvanced" x-transition class="px-6 pb-6 space-y-6 border-t border-gray-100">

                {{-- Horario --}}
                <div class="pt-5">
                    <p class="text-sm font-semibold text-gray-800 mb-1">Horario de actividad</p>
                    <p class="text-xs text-gray-500 mb-3">Vera solo procesará emails dentro de este rango</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Hora inicio</label>
                            <input type="time"
                                   x-model="formData.active_hours_start"
                                   class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Hora fin</label>
                            <input type="time"
                                   x-model="formData.active_hours_end"
                                   class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                </div>

                {{-- Newsletters --}}
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-1">Newsletters y promociones</p>
                    <select x-model="formData.newsletter_handling"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 text-sm shadow-sm">
                        <option value="auto_archive">Archivar automáticamente</option>
                        <option value="mark_read">Marcar como leídos</option>
                        <option value="categorize">Solo categorizar</option>
                        <option value="manual">Gestión manual</option>
                    </select>
                </div>

                {{-- Remitentes Bloqueados --}}
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-1">Remitentes bloqueados</p>
                    <p class="text-xs text-gray-500 mb-3">Vera nunca responderá a estos remitentes</p>

                    <button type="button"
                            @click="contactsBlockedShow = !contactsBlockedShow"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-left flex items-center justify-between text-sm text-gray-700">
                        <span>
                            <span x-show="filters.blockedSenders.length === 0">Seleccionar remitentes...</span>
                            <span x-show="filters.blockedSenders.length > 0" x-text="filters.blockedSenders.length + ' bloqueado(s)'"></span>
                        </span>
                        <svg class="w-4 h-4 transition-transform" :class="contactsBlockedShow && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="contactsBlockedShow" x-transition class="mt-2 border border-gray-200 rounded-xl bg-white shadow p-4">
                        <div class="mb-3">
                            <input type="text" x-model="contactsBlockedSearch"
                                   placeholder="Buscar por email o nombre..."
                                   class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-sm px-3 py-2 text-gray-900 bg-white">
                        </div>
                        <div class="mb-3 flex gap-2">
                            <input type="text" x-model="contactsBlockedManual"
                                   @keydown.enter.prevent="addBlockedSenderManual()"
                                   placeholder="ej. @spam.com o noreply"
                                   class="flex-1 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-sm px-3 py-2 text-gray-900 bg-white">
                            <button type="button" @click="addBlockedSenderManual()"
                                    class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-medium">
                                Añadir
                            </button>
                        </div>
                        <div x-show="contactsBlockedLoading" class="text-center py-4 text-gray-400 text-sm">Cargando...</div>
                        <div x-show="!contactsBlockedLoading" class="max-h-48 overflow-y-auto space-y-1">
                            <template x-for="contact in getFilteredContacts(contactsBlockedSearch, allContacts)" :key="contact.email">
                                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-red-50 cursor-pointer"
                                       :class="filters.blockedSenders.includes(contact.email) ? 'bg-red-50 border border-red-200' : 'border border-transparent'">
                                    <input type="checkbox"
                                           :checked="filters.blockedSenders.includes(contact.email)"
                                           @change="toggleBlockedSender(contact.email)"
                                           class="rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 truncate" x-text="contact.email"></p>
                                        <p class="text-xs text-gray-400" x-text="contact.name + ' · ' + contact.count + ' emails'"></p>
                                    </div>
                                    <span x-show="contact.is_noreply" class="text-xs px-1.5 py-0.5 bg-red-100 text-red-600 rounded">No-reply</span>
                                </label>
                            </template>
                            <p x-show="getFilteredContacts(contactsBlockedSearch, allContacts).length === 0 && !contactsBlockedLoading"
                               class="text-center py-4 text-gray-400 text-sm">Sin resultados</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3" x-show="filters.blockedSenders.length > 0">
                        <template x-for="(sender, index) in filters.blockedSenders" :key="index">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                                <span x-text="sender"></span>
                                <button type="button" @click="removeBlockedSender(sender)" class="text-red-500 hover:text-red-700 ml-1">✕</button>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Remitentes Prioritarios --}}
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-1">Remitentes prioritarios</p>
                    <p class="text-xs text-gray-500 mb-3">Vera siempre responderá a estos remitentes, sin importar los otros filtros</p>

                    <button type="button"
                            @click="contactsAllowedShow = !contactsAllowedShow"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-left flex items-center justify-between text-sm text-gray-700">
                        <span>
                            <span x-show="filters.allowedSenders.length === 0">Seleccionar remitentes...</span>
                            <span x-show="filters.allowedSenders.length > 0" x-text="filters.allowedSenders.length + ' prioritario(s)'"></span>
                        </span>
                        <svg class="w-4 h-4 transition-transform" :class="contactsAllowedShow && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="contactsAllowedShow" x-transition class="mt-2 border border-gray-200 rounded-xl bg-white shadow p-4">
                        <div class="mb-3">
                            <input type="text" x-model="contactsAllowedSearch"
                                   placeholder="Buscar por email o nombre..."
                                   class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm px-3 py-2 text-gray-900 bg-white">
                        </div>
                        <div class="mb-3 flex gap-2">
                            <input type="text" x-model="contactsAllowedManual"
                                   @keydown.enter.prevent="addAllowedSenderManual()"
                                   placeholder="ej. jefe@empresa.com"
                                   class="flex-1 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm px-3 py-2 text-gray-900 bg-white">
                            <button type="button" @click="addAllowedSenderManual()"
                                    class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-medium">
                                Añadir
                            </button>
                        </div>
                        <div x-show="contactsAllowedLoading" class="text-center py-4 text-gray-400 text-sm">Cargando...</div>
                        <div x-show="!contactsAllowedLoading" class="max-h-48 overflow-y-auto space-y-1">
                            <template x-for="contact in getFilteredContacts(contactsAllowedSearch, allContacts)" :key="contact.email">
                                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-green-50 cursor-pointer"
                                       :class="filters.allowedSenders.includes(contact.email) ? 'bg-green-50 border border-green-200' : 'border border-transparent'">
                                    <input type="checkbox"
                                           :checked="filters.allowedSenders.includes(contact.email)"
                                           @change="toggleAllowedSender(contact.email)"
                                           class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 truncate" x-text="contact.email"></p>
                                        <p class="text-xs text-gray-400" x-text="contact.name + ' · ' + contact.count + ' emails'"></p>
                                    </div>
                                </label>
                            </template>
                            <p x-show="getFilteredContacts(contactsAllowedSearch, allContacts).length === 0 && !contactsAllowedLoading"
                               class="text-center py-4 text-gray-400 text-sm">Sin resultados</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3" x-show="filters.allowedSenders.length > 0">
                        <template x-for="(sender, index) in filters.allowedSenders" :key="index">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                <span x-text="sender"></span>
                                <button type="button" @click="removeAllowedSender(sender)" class="text-green-600 hover:text-green-800 ml-1">✕</button>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Palabras Clave Bloqueadas --}}
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-1">Palabras clave bloqueadas</p>
                    <p class="text-xs text-gray-500 mb-3">Vera no responderá emails que contengan estas palabras</p>

                    <button type="button"
                            @click="keywordsShow = !keywordsShow"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-left flex items-center justify-between text-sm text-gray-700 mb-3">
                        <span>Ver sugerencias automáticas</span>
                        <svg class="w-4 h-4 transition-transform" :class="keywordsShow && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="keywordsShow" x-transition class="mb-3 border border-gray-200 rounded-xl bg-white shadow p-4">
                        <p class="text-xs text-gray-500 mb-3">Basadas en tus emails:</p>
                        <div x-show="keywordsLoading" class="text-center py-3 text-gray-400 text-sm">Cargando...</div>
                        <div x-show="!keywordsLoading" class="space-y-1 max-h-40 overflow-y-auto">
                            <template x-for="suggestion in keywordsSuggestions" :key="suggestion.keyword">
                                <label class="flex items-center justify-between p-2 border rounded-lg hover:bg-orange-50 cursor-pointer"
                                       :class="filters.blockedKeywords.includes(suggestion.keyword) ? 'border-orange-400 bg-orange-50' : 'border-gray-200'">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox"
                                               :checked="filters.blockedKeywords.includes(suggestion.keyword)"
                                               @change="toggleKeyword(suggestion.keyword)"
                                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 w-4 h-4">
                                        <span class="text-sm text-gray-900" x-text="suggestion.keyword"></span>
                                        <span class="text-xs text-gray-400" x-text="suggestion.count + ' emails'"></span>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500" x-text="suggestion.type"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-2">
                        <input type="text"
                               x-model="newBlockedKeyword"
                               @keydown.enter.prevent="addBlockedKeyword()"
                               placeholder="ej: unsubscribe, oferta..."
                               class="flex-1 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 px-4 py-2 text-sm text-gray-900 bg-white shadow-sm">
                        <button type="button" @click="addBlockedKeyword()"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium">
                            Añadir
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2" x-show="filters.blockedKeywords.length > 0">
                        <template x-for="(keyword, index) in filters.blockedKeywords" :key="index">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">
                                <span x-text="keyword"></span>
                                <button type="button" @click="removeBlockedKeyword(keyword)" class="text-orange-500 hover:text-orange-700 ml-1">✕</button>
                            </span>
                        </template>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── BOTONES ──────────────────────────────────────── --}}
        <div class="flex gap-3">
            <button type="submit"
                    :disabled="saving"
                    class="flex-1 bg-purple-600 text-white font-semibold py-3 px-6 rounded-xl hover:bg-purple-700 transition-colors shadow disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span x-show="!saving">Guardar configuración</span>
                <span x-show="saving" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 14.627 0 12 0v4a8 8 0 00-8 8H0z"></path>
                    </svg>
                    Guardando...
                </span>
            </button>
            <button type="button"
                    @click="runAnalysis()"
                    :disabled="analyzing"
                    class="px-5 py-3 border-2 border-gray-300 text-gray-600 hover:border-purple-400 hover:text-purple-600 rounded-xl font-semibold transition-colors text-sm disabled:opacity-50">
                <span x-show="!analyzing">Reanalizar emails</span>
                <span x-show="analyzing">Analizando...</span>
            </button>
        </div>

        <p class="text-center text-xs text-gray-400">
            Última actualización: {{ $stats['last_analysis'] ? $stats['last_analysis']->format('d/m/Y H:i') : 'Nunca' }}
        </p>

    </form>
</div>

<script>
function veraConfig() {
    return {
        saving: false,
        analyzing: false,
        showAdvanced: false,
        notification: { show: false, type: 'success', message: '' },

        formData: {
            primary_goal: '{{ $config->main_objective ?? 'balanced' }}',
            response_mode: '{{ $config->auto_response_mode ?? 'suggest_responses' }}',
            autonomy_level: 'moderate',
            writing_style: '{{ is_array($config->writing_style) ? ($config->writing_style['tone'] ?? 'professional') : 'professional' }}',
            newsletter_handling: '{{ $config->newsletter_handling ?? 'auto_archive' }}',
            active_hours_start: '{{ $config->availability_start ? substr($config->availability_start, 0, 5) : '00:00' }}',
            active_hours_end: '{{ $config->availability_end ? substr($config->availability_end, 0, 5) : '23:59' }}',
            confidence_threshold: {{ $config->confidence_threshold ?? 0.75 }},
            response_delay_minutes: {{ $config->response_delay_minutes ?? 0 }},
            auto_response_enabled: {{ $config->auto_response_enabled ? 'true' : 'false' }},
            auto_archive_newsletters: {{ $config->auto_archive_newsletters ?? false ? 'true' : 'false' }}
        },

        userProfile: {
            business_description: @json($userProfile['business_description'] ?? ''),
            response_style: @json($userProfile['response_style'] ?? ''),
            email_signature: @json($userProfile['email_signature'] ?? ''),
            never_say: @json($userProfile['never_say'] ?? ''),
        },
        profileSaving: false,

        filters: {
            allowedCategories: @json($config->response_allowed_categories ?? []),
            blockedCategories: @json($config->response_blocked_categories ?? ['promociones', 'newsletters']),
            blockedKeywords: @json($config->blocked_keywords ?? []),
            blockedSenders: @json($config->blocked_senders ?? []),
            allowedSenders: @json($config->allowed_senders ?? [])
        },
        newBlockedKeyword: '',

        styleLoading: false,
        styleError: null,
        showStyleOverride: false,
        styleProfile: @json($styleProfileData),

        contactsBlockedShow: false,
        contactsBlockedLoading: true,
        contactsBlockedSearch: '',
        contactsBlockedManual: '',
        contactsAllowedShow: false,
        contactsAllowedLoading: true,
        contactsAllowedSearch: '',
        contactsAllowedManual: '',
        allContacts: [],
        keywordsShow: false,
        keywordsLoading: true,
        keywordsSuggestions: [],

        async init() {
            await this.loadContacts();
            await this.loadKeywordsSuggestions();
        },

        // Seleccionar modo y actualizar flags derivados
        selectMode(mode) {
            this.formData.response_mode = mode;
            if (mode === 'filter_focus') {
                this.formData.auto_response_enabled = false;
            } else {
                this.formData.auto_response_enabled = true;
            }
        },

        async loadContacts() {
            try {
                const response = await fetch('{{ route('vera.suggestions.contacts') }}');
                const data = await response.json();
                if (data.success) {
                    this.allContacts = data.contacts;
                }
            } catch (error) {
                console.error('Error cargando contactos:', error);
            } finally {
                this.contactsBlockedLoading = false;
                this.contactsAllowedLoading = false;
            }
        },

        async loadKeywordsSuggestions() {
            try {
                const response = await fetch('{{ route('vera.suggestions.keywords') }}');
                const data = await response.json();
                if (data.success) {
                    this.keywordsSuggestions = data.suggestions;
                }
            } catch (error) {
                console.error('Error cargando keywords:', error);
            } finally {
                this.keywordsLoading = false;
            }
        },

        getFilteredContacts(searchQuery, contacts) {
            if (!searchQuery) return contacts;
            const query = searchQuery.toLowerCase();
            return contacts.filter(c =>
                c.email.toLowerCase().includes(query) ||
                c.name.toLowerCase().includes(query)
            );
        },

        toggleBlockedSender(email) {
            const i = this.filters.blockedSenders.indexOf(email);
            i > -1 ? this.filters.blockedSenders.splice(i, 1) : this.filters.blockedSenders.push(email);
        },
        addBlockedSenderManual() {
            const email = this.contactsBlockedManual.trim();
            if (email && !this.filters.blockedSenders.includes(email)) {
                this.filters.blockedSenders.push(email);
            }
            this.contactsBlockedManual = '';
        },
        removeBlockedSender(sender) {
            const i = this.filters.blockedSenders.indexOf(sender);
            if (i > -1) this.filters.blockedSenders.splice(i, 1);
        },

        toggleAllowedSender(email) {
            const i = this.filters.allowedSenders.indexOf(email);
            i > -1 ? this.filters.allowedSenders.splice(i, 1) : this.filters.allowedSenders.push(email);
        },
        addAllowedSenderManual() {
            const email = this.contactsAllowedManual.trim();
            if (email && !this.filters.allowedSenders.includes(email)) {
                this.filters.allowedSenders.push(email);
            }
            this.contactsAllowedManual = '';
        },
        removeAllowedSender(sender) {
            const i = this.filters.allowedSenders.indexOf(sender);
            if (i > -1) this.filters.allowedSenders.splice(i, 1);
        },

        toggleKeyword(keyword) {
            const i = this.filters.blockedKeywords.indexOf(keyword);
            i > -1 ? this.filters.blockedKeywords.splice(i, 1) : this.filters.blockedKeywords.push(keyword);
        },
        addBlockedKeyword() {
            const kw = this.newBlockedKeyword.trim().toLowerCase();
            if (kw && !this.filters.blockedKeywords.includes(kw)) {
                this.filters.blockedKeywords.push(kw);
            }
            this.newBlockedKeyword = '';
        },
        removeBlockedKeyword(keyword) {
            const i = this.filters.blockedKeywords.indexOf(keyword);
            if (i > -1) this.filters.blockedKeywords.splice(i, 1);
        },

        async saveConfig() {
            this.saving = true;
            this.hideNotification();

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;

                const response = await fetch('{{ route('vera.config.update') }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Error desconocido');

                const filtersResponse = await fetch('{{ route('vera.filters.update') }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        response_allowed_categories: this.filters.allowedCategories,
                        response_blocked_categories: this.filters.blockedCategories,
                        blocked_keywords: this.filters.blockedKeywords,
                        blocked_senders: this.filters.blockedSenders,
                        allowed_senders: this.filters.allowedSenders
                    })
                });

                const filtersData = await filtersResponse.json();
                if (!filtersData.success) throw new Error(filtersData.message || 'Error al guardar filtros');

                this.showNotification('success', 'Configuración guardada correctamente');
                setTimeout(() => { window.location.href = '/emails'; }, 1500);

            } catch (error) {
                this.showNotification('error', 'Error al guardar: ' + error.message);
            } finally {
                this.saving = false;
            }
        },

        async saveUserProfile() {
            this.profileSaving = true;
            try {
                const response = await fetch('{{ route('vera.user-profile.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.userProfile)
                });
                const data = await response.json();
                if (data.success) {
                    this.showNotification('success', data.message);
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            } catch (err) {
                this.showNotification('error', 'Error al guardar el perfil: ' + err.message);
            } finally {
                this.profileSaving = false;
            }
        },

        async analyzeStyle() {
            this.styleLoading = true;
            this.styleError = null;
            try {
                const response = await fetch('{{ route('vera.analyze.writing.style') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await response.json();
                if (data.success) {
                    this.formData.writing_style = data.profile.tone_raw;
                    this.styleProfile = data.profile;
                    this.showNotification('success', data.message);
                } else {
                    this.styleError = data.message || 'Error al analizar el estilo.';
                }
            } catch (err) {
                this.styleError = 'Error de conexión. Inténtalo de nuevo.';
            } finally {
                this.styleLoading = false;
            }
        },

        async runAnalysis() {
            this.analyzing = true;
            this.hideNotification();
            try {
                const response = await fetch('{{ route('vera.analyze') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showNotification('success', 'Análisis completado');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            } catch (error) {
                this.showNotification('error', 'Error al analizar: ' + error.message);
            } finally {
                this.analyzing = false;
            }
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => this.hideNotification(), 5000);
        },
        hideNotification() {
            this.notification.show = false;
        }
    }
}
</script>
</body>
</html>
