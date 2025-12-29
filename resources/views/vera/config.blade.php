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

<body class="antialiased bg-gradient-to-br from-purple-50 via-white to-indigo-50 min-h-screen">

    <div class="max-w-6xl mx-auto px-4 py-12" x-data="veraConfig()">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">⚙️ Configuración de Vera</h1>
                <p class="text-gray-600 mt-1">Ajusta cómo Vera gestiona tus emails automáticamente</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-purple-600 hover:text-purple-700 font-medium">
                ← Volver al Dashboard
            </a>
        </div>

        <!-- Notification -->
        <div x-show="notification.show"
             x-transition
             :class="notification.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
             class="mb-6 p-4 rounded-lg border"
             style="display: none;">
            <p class="font-medium" x-text="notification.message"></p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
                <p class="text-sm text-gray-600 mb-1">📧 Emails Totales</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total_emails'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
                <p class="text-sm text-gray-600 mb-1">✅ Procesados</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['processed_emails'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
                <p class="text-sm text-gray-600 mb-1">💬 Respuestas Generadas</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['responses_generated'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
                <p class="text-sm text-gray-600 mb-1">🗄️ Auto Archivados</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['auto_archived'] }}</p>
            </div>
        </div>

        <!-- Config Form -->
        <form @submit.prevent="saveConfig" class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🎯 Configuración General</h2>

            <div class="space-y-8">
                <!-- Objetivo Principal -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Objetivo Principal de Vera
                    </label>
                    <select x-model="formData.primary_goal"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                        <option value="filter">🗂️ Filtrar y organizar mis emails</option>
                        <option value="respond">💬 Ayudarme a responder emails</option>
                        <option value="balanced">⚖️ Ambos (filtrar y responder)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Define el enfoque principal de Vera</p>
                </div>

                <!-- Modo de Respuesta Automática -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Modo de Respuesta Automática
                    </label>
                    <select x-model="formData.response_mode"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                        <option value="no">🔒 No responder automáticamente</option>
                        <option value="ask_first">❓ Solo emails con preguntas directas</option>
                        <option value="important_only">⭐ Solo emails importantes</option>
                        <option value="suggest_responses">💡 Sugerir respuestas (requiere aprobación)</option>
                        <option value="always">🤖 Siempre responder automáticamente</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Control sobre cuándo Vera genera respuestas</p>
                </div>

                <!-- Nivel de Autonomía -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Nivel de Autonomía
                    </label>
                    <select x-model="formData.autonomy_level"
                            @change="updateConfidenceThreshold()"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                        <option value="conservative">🛡️ Conservador - Preguntarme casi todo (90% confianza)</option>
                        <option value="moderate">⚖️ Moderado - Balance recomendado (75% confianza)</option>
                        <option value="aggressive">🚀 Agresivo - Máxima autonomía (60% confianza)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Umbral de confianza: <span class="font-semibold" x-text="Math.round(formData.confidence_threshold * 100) + '%'"></span></p>
                </div>

                <!-- Writing Style -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Estilo de Escritura
                    </label>
                    <select x-model="formData.writing_style"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                        <option value="formal">👔 Formal</option>
                        <option value="professional">💼 Profesional</option>
                        <option value="casual">😊 Casual</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Tono de las respuestas generadas</p>
                </div>

                <!-- Newsletter Handling -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Gestión de Newsletters
                    </label>
                    <select x-model="formData.newsletter_handling"
                            class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                        <option value="auto_archive">📥 Archivar automáticamente</option>
                        <option value="mark_read">✅ Marcar como leídos</option>
                        <option value="categorize">🏷️ Solo categorizar</option>
                        <option value="manual">👤 Gestión manual</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Cómo manejar emails promocionales y newsletters</p>
                </div>

                <!-- Horario de Disponibilidad -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            ⏰ Hora Inicio
                        </label>
                        <input type="time"
                               x-model="formData.active_hours_start"
                               class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            🌙 Hora Fin
                        </label>
                        <input type="time"
                               x-model="formData.active_hours_end"
                               class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2.5 shadow-sm">
                    </div>
                </div>
                <p class="text-xs text-gray-500 -mt-4">Vera solo procesará emails dentro de este horario</p>

                <!-- Opciones Adicionales -->
                <div class="border-t pt-6 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">⚡ Opciones Adicionales</h3>

                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               x-model="formData.auto_response_enabled"
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-5 h-5">
                        <div>
                            <span class="text-sm font-medium text-gray-800 group-hover:text-purple-600">
                                🤖 Activar respuestas automáticas
                            </span>
                            <p class="text-xs text-gray-500">Permitir que Vera genere y envíe respuestas</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               x-model="formData.auto_archive_newsletters"
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-5 h-5">
                        <div>
                            <span class="text-sm font-medium text-gray-800 group-hover:text-purple-600">
                                📬 Archivar newsletters automáticamente
                            </span>
                            <p class="text-xs text-gray-500">Mantener la bandeja limpia</p>
                        </div>
                    </label>
                </div>

                <!-- Confidence Threshold Manual -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        🎯 Umbral de Confianza Personalizado
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="range"
                               x-model.number="formData.confidence_threshold"
                               min="0.5"
                               max="1"
                               step="0.05"
                               class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        <span class="text-lg font-bold text-purple-600 w-16 text-right" x-text="Math.round(formData.confidence_threshold * 100) + '%'"></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Solo enviar respuestas con este nivel de confianza o superior</p>
                </div>

                <!-- Filtros de Respuesta Automática (NUEVO) -->
                <div class="border-t pt-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">🎯 Filtros de Respuesta Automática</h3>
                        <p class="text-sm text-gray-600 mb-4">Personaliza qué tipos de correos quieres que Vera responda automáticamente</p>
                    </div>

                    <!-- Categorías de Correo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            📧 Categorías de correo a responder
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors"
                                   :class="filters.allowedCategories.includes('trabajo') ? 'border-purple-500 bg-purple-50' : 'border-gray-200'">
                                <input type="checkbox"
                                       @change="toggleCategoryFilter('trabajo')"
                                       :checked="filters.allowedCategories.includes('trabajo')"
                                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-5 h-5">
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-800">💼 Trabajo</span>
                                    <p class="text-xs text-gray-500">Correos profesionales y laborales</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors"
                                   :class="filters.allowedCategories.includes('personal') ? 'border-purple-500 bg-purple-50' : 'border-gray-200'">
                                <input type="checkbox"
                                       @change="toggleCategoryFilter('personal')"
                                       :checked="filters.allowedCategories.includes('personal')"
                                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-5 h-5">
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-800">👤 Personal</span>
                                    <p class="text-xs text-gray-500">Correos de amigos y familia</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-red-50 transition-colors"
                                   :class="!filters.blockedCategories.includes('promociones') ? 'border-red-500 bg-red-50' : 'border-gray-200'">
                                <input type="checkbox"
                                       @change="toggleCategoryFilter('promociones', true)"
                                       :checked="!filters.blockedCategories.includes('promociones')"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 w-5 h-5">
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-800">🏷️ Promociones</span>
                                    <p class="text-xs text-gray-500">Ofertas y publicidad</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-red-50 transition-colors"
                                   :class="!filters.blockedCategories.includes('newsletters') ? 'border-red-500 bg-red-50' : 'border-gray-200'">
                                <input type="checkbox"
                                       @change="toggleCategoryFilter('newsletters', true)"
                                       :checked="!filters.blockedCategories.includes('newsletters')"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 w-5 h-5">
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-800">📰 Newsletters</span>
                                    <p class="text-xs text-gray-500">Boletines informativos</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Remitentes Bloqueados -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            🚫 Remitentes o dominios bloqueados
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input type="text"
                                   x-model="newBlockedSender"
                                   @keydown.enter.prevent="addBlockedSender()"
                                   placeholder="ej: noreply, marketing@, @spam.com"
                                   class="flex-1 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2 shadow-sm text-sm">
                            <button type="button"
                                    @click="addBlockedSender()"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                ➕ Agregar
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2" x-show="filters.blockedSenders.length > 0">
                            <template x-for="(sender, index) in filters.blockedSenders" :key="index">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                    <span x-text="sender"></span>
                                    <button type="button"
                                            @click="removeBlockedSender(sender)"
                                            class="ml-1 text-red-600 hover:text-red-800">
                                        ✕
                                    </button>
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Vera nunca responderá a estos remitentes</p>
                    </div>

                    <!-- Remitentes Prioritarios -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            ⭐ Remitentes prioritarios (siempre responder)
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input type="text"
                                   x-model="newAllowedSender"
                                   @keydown.enter.prevent="addAllowedSender()"
                                   placeholder="ej: jefe@empresa.com, @clientevip.com"
                                   class="flex-1 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2 shadow-sm text-sm">
                            <button type="button"
                                    @click="addAllowedSender()"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                ➕ Agregar
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2" x-show="filters.allowedSenders.length > 0">
                            <template x-for="(sender, index) in filters.allowedSenders" :key="index">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                    <span x-text="sender"></span>
                                    <button type="button"
                                            @click="removeAllowedSender(sender)"
                                            class="ml-1 text-green-600 hover:text-green-800">
                                        ✕
                                    </button>
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Vera siempre responderá a estos remitentes</p>
                    </div>

                    <!-- Palabras Clave Bloqueadas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            🔤 Palabras clave bloqueadas
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input type="text"
                                   x-model="newBlockedKeyword"
                                   @keydown.enter.prevent="addBlockedKeyword()"
                                   placeholder="ej: unsubscribe, oferta, descuento"
                                   class="flex-1 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-gray-900 bg-white px-4 py-2 shadow-sm text-sm">
                            <button type="button"
                                    @click="addBlockedKeyword()"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                ➕ Agregar
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2" x-show="filters.blockedKeywords.length > 0">
                            <template x-for="(keyword, index) in filters.blockedKeywords" :key="index">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">
                                    <span x-text="keyword"></span>
                                    <button type="button"
                                            @click="removeBlockedKeyword(keyword)"
                                            class="ml-1 text-orange-600 hover:text-orange-800">
                                        ✕
                                    </button>
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">No responder emails que contengan estas palabras</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 mt-8 pt-6 border-t">
                <button type="submit"
                        :disabled="saving"
                        :class="saving ? 'opacity-50 cursor-not-allowed' : 'hover:bg-purple-700'"
                        class="flex-1 bg-purple-600 text-white font-semibold py-3 px-6 rounded-lg transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl">
                    <span x-show="!saving">💾 Guardar Cambios</span>
                    <span x-show="saving" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardando...
                    </span>
                </button>
                <button type="button"
                        @click="runAnalysis()"
                        :disabled="analyzing"
                        class="px-6 py-3 border-2 border-purple-600 text-purple-600 hover:bg-purple-50 rounded-lg font-semibold transition-colors">
                    <span x-show="!analyzing">🔄 Re-analizar Emails</span>
                    <span x-show="analyzing">⏳ Analizando...</span>
                </button>
            </div>
        </form>

        <!-- Info Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Última actualización: {{ $stats['last_analysis'] ? $stats['last_analysis']->format('d/m/Y H:i') : 'Nunca' }}</p>
        </div>
    </div>

    <script>
        // Debug: Verificar que Alpine.js se cargó
        console.log('🔍 Script cargado. Alpine disponible:', typeof window.Alpine !== 'undefined');

        function veraConfig() {
            return {
                saving: false,
                analyzing: false,
                notification: {
                    show: false,
                    type: 'success',
                    message: ''
                },
                formData: {
                    primary_goal: '{{ $config->main_objective ?? 'balanced' }}',
                    response_mode: '{{ $config->auto_response_mode ?? 'suggest_responses' }}',
                    autonomy_level: '{{ $config->autonomy_level ?? 'moderate' }}',
                    writing_style: '{{ is_array($config->writing_style) ? ($config->writing_style['tone'] ?? 'professional') : 'professional' }}',
                    newsletter_handling: '{{ $config->newsletter_handling ?? 'auto_archive' }}',
                    active_hours_start: '{{ $config->availability_start ? substr($config->availability_start, 0, 5) : '00:00' }}',
                    active_hours_end: '{{ $config->availability_end ? substr($config->availability_end, 0, 5) : '23:59' }}',
                    confidence_threshold: {{ $config->confidence_threshold ?? 0.75 }},
                    auto_response_enabled: {{ $config->auto_response_enabled ? 'true' : 'false' }},
                    auto_archive_newsletters: {{ $config->auto_archive_newsletters ?? false ? 'true' : 'false' }}
                },

                // Filtros de respuesta (NUEVO)
                filters: {
                    allowedCategories: @json($config->response_allowed_categories ?? []),
                    blockedCategories: @json($config->response_blocked_categories ?? ['promociones', 'newsletters']),
                    blockedKeywords: @json($config->blocked_keywords ?? []),
                    blockedSenders: @json($config->blocked_senders ?? []),
                    allowedSenders: @json($config->allowed_senders ?? [])
                },
                newBlockedSender: '',
                newAllowedSender: '',
                newBlockedKeyword: '',

                init() {
                    console.log('✅ Alpine.js componente inicializado');
                    console.log('📋 FormData inicial:', this.formData);
                    console.log('🎯 Filtros iniciales:', this.filters);
                },

                updateConfidenceThreshold() {
                    const thresholds = {
                        'conservative': 0.90,
                        'moderate': 0.75,
                        'aggressive': 0.60
                    };
                    this.formData.confidence_threshold = thresholds[this.formData.autonomy_level] || 0.75;
                    console.log('🎯 Umbral actualizado:', this.formData.confidence_threshold);
                },

                async saveConfig() {
                    console.log('💾 Guardando configuración...');
                    console.log('📤 Datos a enviar:', this.formData);

                    this.saving = true;
                    this.hideNotification();

                    try {
                        // 1. Guardar configuración general
                        const url = '{{ route('vera.config.update') }}';
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                        console.log('🌐 URL:', url);
                        console.log('🔑 CSRF Token:', csrfToken ? 'Presente' : 'FALTA');

                        const response = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.formData)
                        });

                        console.log('📡 Response status:', response.status);
                        console.log('📡 Response ok:', response.ok);

                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('❌ Error response:', errorText);
                            throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                        }

                        const data = await response.json();
                        console.log('📥 Response data:', data);

                        if (!data.success) {
                            throw new Error(data.message || 'Error desconocido');
                        }

                        // 2. Guardar filtros de respuesta (NUEVO)
                        console.log('🎯 Guardando filtros...');
                        const filtersResponse = await fetch('{{ route('vera.filters.update') }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
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
                        console.log('📥 Filtros response:', filtersData);

                        if (filtersData.success) {
                            console.log('✅ Configuración y filtros guardados exitosamente');
                            this.showNotification('success', '✅ Configuración guardada exitosamente');

                            // Redirigir después de 1.5 segundos
                            setTimeout(() => {
                                console.log('🔄 Redirigiendo a /emails...');
                                window.location.href = '/emails';
                            }, 1500);
                        } else {
                            throw new Error(filtersData.message || 'Error al guardar filtros');
                        }
                    } catch (error) {
                        console.error('❌ Error completo:', error);
                        console.error('❌ Error stack:', error.stack);
                        this.showNotification('error', '❌ Error al guardar: ' + error.message);
                        alert('Error al guardar la configuración:\n' + error.message);
                    } finally {
                        this.saving = false;
                        console.log('🏁 Proceso finalizado');
                    }
                },

                async runAnalysis() {
                    console.log('🔄 Ejecutando análisis...');
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
                        console.log('📊 Análisis response:', data);

                        if (data.success) {
                            this.showNotification('success', '✅ Análisis completado exitosamente');
                            setTimeout(() => window.location.reload(), 2000);
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    } catch (error) {
                        console.error('❌ Error análisis:', error);
                        this.showNotification('error', '❌ Error al analizar: ' + error.message);
                        alert('Error al analizar:\n' + error.message);
                    } finally {
                        this.analyzing = false;
                    }
                },

                // === MÉTODOS DE GESTIÓN DE FILTROS (NUEVO) ===

                toggleCategoryFilter(category, isBlocked = false) {
                    console.log(`🔄 Toggling category: ${category}, isBlocked: ${isBlocked}`);

                    if (isBlocked) {
                        // Es una categoría bloqueada (promociones/newsletters)
                        const index = this.filters.blockedCategories.indexOf(category);
                        if (index > -1) {
                            // Está bloqueada, remover del bloqueo (permitir)
                            this.filters.blockedCategories.splice(index, 1);
                        } else {
                            // No está bloqueada, agregarla al bloqueo
                            this.filters.blockedCategories.push(category);
                        }
                    } else {
                        // Es una categoría permitida (trabajo/personal)
                        const index = this.filters.allowedCategories.indexOf(category);
                        if (index > -1) {
                            // Está permitida, removerla
                            this.filters.allowedCategories.splice(index, 1);
                        } else {
                            // No está permitida, agregarla
                            this.filters.allowedCategories.push(category);
                        }
                    }

                    console.log('📋 Filtros actualizados:', this.filters);
                },

                addBlockedSender() {
                    const sender = this.newBlockedSender.trim();
                    if (!sender) return;

                    if (this.filters.blockedSenders.includes(sender)) {
                        this.showNotification('error', '⚠️ Este remitente ya está bloqueado');
                        return;
                    }

                    this.filters.blockedSenders.push(sender);
                    this.newBlockedSender = '';
                    console.log('🚫 Remitente bloqueado agregado:', sender);
                },

                removeBlockedSender(sender) {
                    const index = this.filters.blockedSenders.indexOf(sender);
                    if (index > -1) {
                        this.filters.blockedSenders.splice(index, 1);
                        console.log('✅ Remitente bloqueado eliminado:', sender);
                    }
                },

                addAllowedSender() {
                    const sender = this.newAllowedSender.trim();
                    if (!sender) return;

                    if (this.filters.allowedSenders.includes(sender)) {
                        this.showNotification('error', '⚠️ Este remitente ya está en la lista prioritaria');
                        return;
                    }

                    this.filters.allowedSenders.push(sender);
                    this.newAllowedSender = '';
                    console.log('⭐ Remitente prioritario agregado:', sender);
                },

                removeAllowedSender(sender) {
                    const index = this.filters.allowedSenders.indexOf(sender);
                    if (index > -1) {
                        this.filters.allowedSenders.splice(index, 1);
                        console.log('✅ Remitente prioritario eliminado:', sender);
                    }
                },

                addBlockedKeyword() {
                    const keyword = this.newBlockedKeyword.trim().toLowerCase();
                    if (!keyword) return;

                    if (this.filters.blockedKeywords.includes(keyword)) {
                        this.showNotification('error', '⚠️ Esta palabra clave ya está bloqueada');
                        return;
                    }

                    this.filters.blockedKeywords.push(keyword);
                    this.newBlockedKeyword = '';
                    console.log('🔤 Palabra clave bloqueada agregada:', keyword);
                },

                removeBlockedKeyword(keyword) {
                    const index = this.filters.blockedKeywords.indexOf(keyword);
                    if (index > -1) {
                        this.filters.blockedKeywords.splice(index, 1);
                        console.log('✅ Palabra clave bloqueada eliminada:', keyword);
                    }
                },

                showNotification(type, message) {
                    console.log(`📢 Notificación [${type}]:`, message);
                    this.notification = { show: true, type, message };
                    setTimeout(() => this.hideNotification(), 5000);
                },

                hideNotification() {
                    this.notification.show = false;
                }
            }
        }

        // Debug: Verificar carga del DOM
        document.addEventListener('DOMContentLoaded', () => {
            console.log('📄 DOM cargado completamente');
        });
    </script>
</body>
</html>
