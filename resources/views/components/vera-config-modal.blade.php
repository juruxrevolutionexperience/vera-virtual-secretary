<!-- Vera Config Modal -->
<div x-data="veraConfigModal()" @open-vera-config.window="openModal()" x-show="isOpen" x-cloak
    class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">

    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity backdrop-blur-sm" @click="closeModal()"
        x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    <!-- Modal Panel -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl" @click.away="closeModal()" x-show="isOpen"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95">

            <!-- Header -->
            <div
                class="flex items-center justify-between p-8 border-b border-gray-200 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-t-2xl">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div class="text-white">
                        <h2 class="text-3xl font-bold">Vera AI Assistant</h2>
                        <p class="text-purple-100 mt-1">Tu asistente inteligente de emails</p>
                    </div>
                </div>
                <button @click="closeModal()" class="text-white/80 hover:text-white transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-8">
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                    <p class="text-gray-600 mt-4">Cargando configuración...</p>
                </div>

                <!-- Sin Configuración (Primera Vez) -->
                <div x-show="!loading && !hasConfig" class="text-center py-12">
                    <div class="w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-14 h-14 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">¡Bienvenido a Vera!</h3>
                    <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                        Vera analizará tus emails para aprender tu estilo de comunicación y ayudarte a gestionar tu
                        bandeja de entrada automáticamente.
                    </p>

                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6 mb-8">
                        <h4 class="font-semibold text-gray-900 mb-4">¿Qué hace Vera?</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Filtra automáticamente</p>
                                    <p class="text-sm text-gray-600">Archiva newsletters y spam</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Genera respuestas</p>
                                    <p class="text-sm text-gray-600">Imita tu estilo de escritura</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Aprende continuamente</p>
                                    <p class="text-sm text-gray-600">Mejora con cada email</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">100% privado</p>
                                    <p class="text-sm text-gray-600">Tus datos no salen de Vera</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-center">
                        <button @click="goToOnboarding()"
                            class="px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                            Configurar Vera Ahora
                        </button>
                        <button @click="closeModal()"
                            class="px-8 py-4 border-2 border-gray-300 hover:border-gray-400 text-gray-700 rounded-xl font-semibold transition-colors">
                            Tal vez después
                        </button>
                    </div>
                </div>

                <!-- Con Configuración (Usuario Existente) -->
                <div x-show="!loading && hasConfig">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">Tu Configuración Actual</h3>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                                ✓ Activo
                            </span>
                        </div>
                    </div>

                    <!-- Config Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Objetivo Principal</p>
                            <p class="font-semibold text-gray-900" x-text="configSummary.primary_goal"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Estilo de Escritura</p>
                            <p class="font-semibold text-gray-900" x-text="configSummary.writing_style"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Nivel de Autonomía</p>
                            <p class="font-semibold text-gray-900" x-text="configSummary.autonomy_level"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Último Análisis</p>
                            <p class="font-semibold text-gray-900" x-text="configSummary.last_analysis"></p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToConfig()"
                            class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition-colors shadow-sm">
                            Editar Configuración
                        </button>
                        <button @click="runAnalysis()"
                            class="px-6 py-3 border-2 border-purple-600 text-purple-600 hover:bg-purple-50 rounded-lg font-semibold transition-colors">
                            Re-analizar Emails
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function veraConfigModal() {
        return {
            isOpen: false,
            loading: false,
            hasConfig: false,
            configSummary: {},

            openModal() {
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
                this.loadConfig();
            },

            closeModal() {
                this.isOpen = false;
                document.body.style.overflow = 'auto';
            },

            async loadConfig() {
                this.loading = true;

                try {
                    const response = await fetch('/vera/stats', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Error loading config');

                    const data = await response.json();

                    if (data.config) {
                        this.hasConfig = true;
                        this.configSummary = {
                            primary_goal: this.formatGoal(data.config.primary_goal),
                            writing_style: this.formatStyle(data.config.writing_style),
                            autonomy_level: this.formatAutonomy(data.config.autonomy_level),
                            last_analysis: data.last_analysis ?
                                new Date(data.last_analysis).toLocaleDateString('es-ES') : 'Nunca'
                        };
                    } else {
                        this.hasConfig = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.hasConfig = false;
                } finally {
                    this.loading = false;
                }
            },

            goToOnboarding() {
                window.location.href = '/vera/onboarding';
            },

            goToConfig() {
                window.location.href = '/vera/config';
            },

            async runAnalysis() {
                const confirmed = confirm('¿Quieres re-analizar todos tus emails? Esto puede tardar unos minutos.');
                if (!confirmed) return;

                try {
                    const response = await fetch('/vera/analyze', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        alert('✅ Análisis completado exitosamente');
                        this.loadConfig();
                    }
                } catch (error) {
                    alert('Error al ejecutar análisis');
                    console.error('Error:', error);
                }
            },

            formatGoal(goal) {
                const goals = {
                    'filter': '📥 Filtrar y organizar',
                    'respond': '✉️ Ayudar a responder',
                    'balanced': '⚖️ Ambos (filtrar y responder)'
                };
                return goals[goal] || goal;
            },

            formatStyle(style) {
                const styles = {
                    'formal': '🎩 Formal',
                    'professional': '💼 Profesional',
                    'casual': '😊 Casual'
                };
                return styles[style] || style;
            },

            formatAutonomy(level) {
                const levels = {
                    'conservative': '🔒 Conservador',
                    'moderate': '⚡ Moderado',
                    'aggressive': '🚀 Agresivo'
                };
                return levels[level] || level;
            }
        }
    }
</script>
