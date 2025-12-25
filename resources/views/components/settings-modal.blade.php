<!-- Settings Modal (Estilo OpenAI) -->
<div x-data="settingsModal()" @open-settings-modal.window="openModal()" x-show="isOpen" x-cloak
    class="fixed inset-0 z-[100] overflow-hidden" style="display: none;">

    <!-- Overlay -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"
        x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    <!-- Modal Panel -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl h-[85vh] flex"
             @click.away="closeModal()" x-show="isOpen"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95">

            <!-- Sidebar -->
            <div class="w-80 bg-gray-50 border-r border-gray-200 rounded-l-2xl p-6 flex flex-col">
                <!-- Close Button -->
                <button @click="closeModal()"
                    class="self-start mb-8 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Categories -->
                <nav class="flex-1 space-y-2">
                    <button @click="activeCategory = 'general'"
                        :class="activeCategory === 'general' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">General</span>
                    </button>

                    <button @click="activeCategory = 'profile'"
                        :class="activeCategory === 'profile' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="font-medium">Perfil</span>
                    </button>

                    <!-- VERA CONFIG -->
                    <button @click="activeCategory = 'vera'"
                        :class="activeCategory === 'vera' ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md' : 'text-purple-600 hover:text-purple-700 hover:bg-purple-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <span class="font-medium">Configurador VERA</span>
                        <span class="ml-auto text-xs px-2 py-0.5 rounded-full font-semibold"
                              :class="activeCategory === 'vera' ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-700'">AI</span>
                    </button>

                    <button @click="activeCategory = 'account'"
                        :class="activeCategory === 'account' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Cuenta</span>
                    </button>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-8 bg-white">
                <!-- General Settings -->
                <div x-show="activeCategory === 'general'" class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">General</h2>
                    <p class="text-gray-600">Configuración general de la aplicación</p>
                </div>

                <!-- Profile Settings -->
                <div x-show="activeCategory === 'profile'" class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Perfil</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                            <input type="text" value="{{ auth()->user()->name }}" readonly
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" value="{{ auth()->user()->email }}" readonly
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="inline-block px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                            Editar Perfil Completo
                        </a>
                    </div>
                </div>

                <!-- VERA Settings -->
                <div x-show="activeCategory === 'vera'" class="space-y-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Configurador VERA</h2>
                            <p class="text-gray-600 mt-1">Tu asistente inteligente de emails</p>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1 bg-green-50 text-green-600 rounded-full text-sm font-medium border border-green-200" x-show="hasVeraConfig">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            Activo
                        </div>
                    </div>

                    <!-- Sin Configuración -->
                    <div x-show="!hasVeraConfig" class="py-12 bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl border-2 border-purple-200">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">¡Configura Vera por primera vez!</h3>
                            <p class="text-gray-600 mb-2 max-w-2xl mx-auto text-base">
                                Vera necesita conocerte para poder ayudarte de manera inteligente
                            </p>
                        </div>

                        <!-- Explicación del proceso -->
                        <div class="max-w-3xl mx-auto px-8 mb-8">
                            <div class="bg-white rounded-lg p-6 shadow-sm border border-purple-100">
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    ¿Por qué es necesario configurar Vera?
                                </h4>
                                <div class="space-y-3 text-sm text-gray-700">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                            <span class="text-purple-600 font-bold text-xs">1</span>
                                        </div>
                                        <p><strong>Análisis inicial:</strong> Vera analizará tus emails existentes para entender tu estilo de comunicación, tus contactos frecuentes y el tipo de emails que recibes.</p>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                            <span class="text-purple-600 font-bold text-xs">2</span>
                                        </div>
                                        <p><strong>Personalización:</strong> Con esta información, Vera aprenderá a filtrar, organizar y sugerir respuestas que coincidan con tu forma de trabajar.</p>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                            <span class="text-purple-600 font-bold text-xs">3</span>
                                        </div>
                                        <p><strong>Aprendizaje continuo:</strong> Este proceso se repetirá automáticamente cada semana para que Vera se adapte a los cambios en tu comunicación y mejore constantemente.</p>
                                    </div>
                                </div>

                                <div class="mt-5 pt-5 border-t border-purple-100">
                                    <p class="text-xs text-gray-500 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <strong>Tu privacidad es importante:</strong> Vera solo analiza tus emails localmente y nunca comparte tu información con terceros.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button @click="goToOnboarding()"
                                class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                                Comenzar Configuración
                            </button>
                            <p class="text-xs text-gray-500 mt-3">Este proceso tomará solo unos minutos</p>
                        </div>
                    </div>

                    <!-- Con Configuración -->
                    <div x-show="hasVeraConfig" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Objetivo Principal</p>
                                <p class="font-semibold text-gray-900" x-text="veraConfig.primary_goal"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Nivel de Autonomía</p>
                                <p class="font-semibold text-gray-900" x-text="veraConfig.autonomy_level"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Estilo de Escritura</p>
                                <p class="font-semibold text-gray-900" x-text="veraConfig.writing_style"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Último Análisis</p>
                                <p class="font-semibold text-gray-900" x-text="veraConfig.last_analysis"></p>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button @click="goToConfig()"
                                class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition-colors shadow-sm">
                                Editar Configuración
                            </button>
                            <button @click="runAnalysis()"
                                class="px-6 py-3 border-2 border-purple-600 text-purple-600 hover:bg-purple-50 rounded-lg font-semibold transition-all">
                                Re-analizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Account Settings -->
                <div x-show="activeCategory === 'account'" class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Cuenta</h2>
                    <p class="text-gray-600">Gestiona tu cuenta y preferencias</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function settingsModal() {
        return {
            isOpen: false,
            activeCategory: 'general',
            hasVeraConfig: false,
            veraConfig: {},

            init() {
                // Verificar si necesita onboarding automático
                this.checkAutoOnboarding();
            },

            async checkAutoOnboarding() {
                try {
                    const response = await fetch('/vera/check-onboarding', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();

                        if (data.needs_onboarding) {
                            // Esperar 1 segundo y abrir modal en categoría Vera
                            setTimeout(() => {
                                this.activeCategory = 'vera';
                                this.openModal();
                            }, 1000);
                        }
                    }
                } catch (error) {
                    console.error('Error checking onboarding:', error);
                }
            },

            openModal() {
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
                this.loadVeraConfig();
            },

            closeModal() {
                this.isOpen = false;
                document.body.style.overflow = 'auto';
            },

            async loadVeraConfig() {
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
                        this.hasVeraConfig = true;
                        this.veraConfig = {
                            primary_goal: this.formatGoal(data.config.primary_goal),
                            writing_style: this.formatStyle(data.config.writing_style),
                            autonomy_level: this.formatAutonomy(data.config.autonomy_level),
                            last_analysis: data.last_analysis ? new Date(data.last_analysis).toLocaleDateString('es-ES') : 'Nunca'
                        };
                    } else {
                        this.hasVeraConfig = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.hasVeraConfig = false;
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
                        this.loadVeraConfig();
                    }
                } catch (error) {
                    alert('Error al ejecutar análisis');
                    console.error('Error:', error);
                }
            },

            formatGoal(goal) {
                const goals = {
                    'filter': 'Filtrar y organizar',
                    'respond': 'Ayudar a responder',
                    'balanced': 'Ambos (filtrar y responder)'
                };
                return goals[goal] || goal;
            },

            formatStyle(style) {
                const styles = {
                    'formal': 'Formal',
                    'professional': 'Profesional',
                    'casual': 'Casual'
                };
                return styles[style] || style;
            },

            formatAutonomy(level) {
                const levels = {
                    'conservative': 'Conservador',
                    'moderate': 'Moderado',
                    'aggressive': 'Agresivo'
                };
                return levels[level] || level;
            }
        }
    }
</script>
