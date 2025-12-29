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

                    <!-- USO -->
                    <button @click="activeCategory = 'usage'; loadUsageData()"
                        :class="activeCategory === 'usage' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="font-medium">Uso</span>
                    </button>

                    <!-- PLANES -->
                    <button @click="activeCategory = 'plans'; loadPlansData()"
                        :class="activeCategory === 'plans' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <span class="font-medium">Planes</span>
                    </button>

                    <!-- NOTIFICACIONES -->
                    <button @click="activeCategory = 'notifications'; loadNotificationsData()"
                        :class="activeCategory === 'notifications' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="font-medium">Notificaciones</span>
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

                <!-- Usage Settings -->
                <div x-show="activeCategory === 'usage'" class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Uso de Vera</h2>
                    <p class="text-gray-600 mb-6">Monitorea tu consumo mensual de emails procesados con Vera.</p>

                    <!-- Loading State -->
                    <div x-show="!usageData" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    </div>

                    <!-- Usage Content -->
                    <div x-show="usageData" class="space-y-6">
                        <!-- Plan Info -->
                        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-700">Plan Actual</h3>
                                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="usageData?.user?.plan_type ? usageData.user.plan_type.charAt(0).toUpperCase() + usageData.user.plan_type.slice(1) : '-'"></p>
                                </div>
                                <span x-text="alertBadge" :class="`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${alertBadgeClass}`"></span>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Límite mensual:</span>
                                    <span class="font-semibold text-gray-900" x-text="`${usageData?.usage?.email_limit || 0} emails`"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Emails procesados:</span>
                                    <span class="font-semibold text-gray-900" x-text="`${usageData?.usage?.emails_processed || 0} emails`"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Emails restantes:</span>
                                    <span class="font-semibold" :class="(usageData?.usage?.emails_remaining || 0) < 10 ? 'text-red-600' : ((usageData?.usage?.emails_remaining || 0) < 30 ? 'text-yellow-600' : 'text-gray-900')" x-text="`${usageData?.usage?.emails_remaining || 0} emails`"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="bg-white rounded-lg p-6 border border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">Progreso de Uso</h3>
                            <div class="mb-2 flex justify-between text-sm text-gray-600">
                                <span x-text="`${usageData?.usage?.usage_percentage || 0}% utilizado`"></span>
                                <span x-text="`${usageData?.usage?.emails_processed || 0} / ${usageData?.usage?.email_limit || 0}`"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="h-full transition-all duration-500" :class="progressBarColor" :style="`width: ${Math.min(usageData?.usage?.usage_percentage || 0, 100)}%`"></div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-xs text-gray-600 mb-1">Ciclo actual</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatDateRange"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-xs text-gray-600 mb-1">Tokens consumidos</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatNumber(usageData?.usage?.total_tokens || 0)"></p>
                            </div>
                        </div>

                        <!-- Upgrade CTA -->
                        <div x-show="(usageData?.usage?.emails_remaining || 100) < 20 || usageData?.alert_level === 'blocked'"
                             class="bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <span x-show="usageData?.alert_level === 'blocked'">Has alcanzado el límite de tu plan</span>
                                <span x-show="usageData?.alert_level !== 'blocked'">Te estás quedando sin emails</span>
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Considera actualizar tu plan para obtener más capacidad
                            </p>
                            <button @click="activeCategory = 'plans'; loadPlansData()"
                                    class="px-6 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-semibold transition-colors text-sm">
                                Ver Planes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notifications Settings -->
                <div x-show="activeCategory === 'notifications'" class="space-y-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Preferencias de Notificaciones</h2>
                        <p class="text-gray-600 mt-2">Configura cómo y cuándo quieres recibir actualizaciones de Vera</p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="!notificationsData" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    </div>

                    <!-- Notifications Content -->
                    <div x-show="notificationsData" class="space-y-6">
                        <!-- Success Message -->
                        <div x-show="notificationsSaved" x-transition class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Preferencias guardadas correctamente</span>
                        </div>

                        <!-- Canales de Notificación -->
                        <div class="bg-gray-50 rounded-lg border border-gray-200">
                            <div class="px-5 py-3 border-b border-gray-200 bg-white rounded-t-lg">
                                <h3 class="text-sm font-semibold text-gray-900">Canales de Notificación</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Elige cómo quieres recibir notificaciones</p>
                            </div>
                            <div class="p-5 space-y-3">
                                <label class="flex items-start gap-3 p-3 rounded-lg hover:bg-white transition-colors cursor-pointer">
                                    <input type="checkbox" x-model="notificationsData.email_enabled" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">📧 Notificaciones por Email</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Recibe notificaciones importantes en tu correo</div>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 p-3 rounded-lg hover:bg-white transition-colors cursor-pointer">
                                    <input type="checkbox" x-model="notificationsData.push_enabled" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">📱 Notificaciones Push</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Notificaciones instantáneas en el navegador</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Resúmenes Automáticos -->
                        <div class="bg-gray-50 rounded-lg border border-gray-200">
                            <div class="px-5 py-3 border-b border-gray-200 bg-white rounded-t-lg">
                                <h3 class="text-sm font-semibold text-gray-900">Resúmenes Automáticos</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Recibe resúmenes periódicos de la actividad de Vera</p>
                            </div>
                            <div class="p-5 space-y-4">
                                <!-- Resumen Diario -->
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <label class="flex items-start gap-3 cursor-pointer mb-3">
                                        <input type="checkbox" x-model="notificationsData.daily_summary_enabled" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">📅 Resumen Diario</div>
                                            <div class="text-xs text-gray-500 mt-0.5">Actividad del día: emails procesados, respuestas enviadas</div>
                                        </div>
                                    </label>
                                    <div x-show="notificationsData.daily_summary_enabled" x-transition class="ml-7 flex items-center gap-2">
                                        <span class="text-xs text-gray-600">Hora:</span>
                                        <input type="time" x-model="notificationsData.daily_summary_time" class="px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <span class="text-xs text-gray-400">(18:00 recomendado)</span>
                                    </div>
                                </div>

                                <!-- Resumen Semanal -->
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <label class="flex items-start gap-3 cursor-pointer mb-3">
                                        <input type="checkbox" x-model="notificationsData.weekly_summary_enabled" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">📊 Resumen Semanal</div>
                                            <div class="text-xs text-gray-500 mt-0.5">Estadísticas semanales: tiempo ahorrado, tendencias</div>
                                        </div>
                                    </label>
                                    <div x-show="notificationsData.weekly_summary_enabled" x-transition class="ml-7 flex items-center gap-2 flex-wrap">
                                        <span class="text-xs text-gray-600">Día:</span>
                                        <select x-model="notificationsData.weekly_summary_day" class="px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            <option value="monday">Lunes</option>
                                            <option value="tuesday">Martes</option>
                                            <option value="wednesday">Miércoles</option>
                                            <option value="thursday">Jueves</option>
                                            <option value="friday">Viernes</option>
                                            <option value="saturday">Sábado</option>
                                            <option value="sunday">Domingo</option>
                                        </select>
                                        <span class="text-xs text-gray-600">Hora:</span>
                                        <input type="time" x-model="notificationsData.weekly_summary_time" class="px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                </div>

                                <!-- Resumen Mensual -->
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <label class="flex items-start gap-3 cursor-pointer mb-3">
                                        <input type="checkbox" x-model="notificationsData.monthly_summary_enabled" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">📈 Resumen Mensual</div>
                                            <div class="text-xs text-gray-500 mt-0.5">Informe completo: métricas, consumo de plan</div>
                                        </div>
                                    </label>
                                    <div x-show="notificationsData.monthly_summary_enabled" x-transition class="ml-7 flex items-center gap-2 flex-wrap">
                                        <span class="text-xs text-gray-600">Día del mes:</span>
                                        <input type="number" x-model="notificationsData.monthly_summary_day" min="1" max="31" class="px-2 py-1 border border-gray-300 rounded text-xs w-16 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <span class="text-xs text-gray-600">Hora:</span>
                                        <input type="time" x-model="notificationsData.monthly_summary_time" class="px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alertas -->
                        <div class="bg-gray-50 rounded-lg border border-gray-200">
                            <div class="px-5 py-3 border-b border-gray-200 bg-white rounded-t-lg">
                                <h3 class="text-sm font-semibold text-gray-900">Alertas Importantes</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Recibe alertas cuando ocurran eventos importantes</p>
                            </div>
                            <div class="p-5 space-y-3">
                                <!-- Límite de Uso -->
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <label class="flex items-start gap-3 cursor-pointer mb-3">
                                        <input type="checkbox" x-model="notificationsData.limit_warnings_enabled" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">⚠️ Advertencias de Límite de Uso</div>
                                            <div class="text-xs text-gray-500 mt-0.5">Aviso cuando estés cerca de tu límite mensual</div>
                                        </div>
                                    </label>
                                    <div x-show="notificationsData.limit_warnings_enabled" x-transition class="ml-7 flex items-center gap-2">
                                        <span class="text-xs text-gray-600">Alertar al:</span>
                                        <input type="number" x-model="notificationsData.warning_threshold_percentage" min="50" max="100" class="px-2 py-1 border border-gray-300 rounded text-xs w-16 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <span class="text-xs text-gray-600">%</span>
                                        <span class="text-xs text-gray-400">(80% recomendado)</span>
                                    </div>
                                </div>

                                <label class="flex items-start gap-3 p-3 rounded-lg hover:bg-white transition-colors cursor-pointer border border-gray-200 bg-white">
                                    <input type="checkbox" x-model="notificationsData.high_activity_alerts" class="w-4 h-4 mt-0.5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">🔥 Alertas de Alta Actividad</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Aviso si detectamos picos inusuales de emails</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex items-center justify-end pt-2">
                            <button @click="saveNotifications()" :disabled="savingNotifications"
                                    class="px-6 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!savingNotifications">Guardar Cambios</span>
                                <span x-show="savingNotifications">Guardando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Plans Settings -->
                <div x-show="activeCategory === 'plans'" class="space-y-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Planes de Vera</h2>
                        <p class="text-gray-600 mt-2">Elige el plan que mejor se adapte a tus necesidades</p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="!plansData" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    </div>

                    <!-- Plans Grid -->
                    <div x-show="plansData" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <template x-for="(plan, planKey) in plansData?.plans || {}" :key="planKey">
                            <div class="relative">
                                <!-- Popular Badge -->
                                <div x-show="plan.popular" class="absolute -top-2 left-1/2 transform -translate-x-1/2 z-10">
                                    <span class="bg-violet-600 text-white px-3 py-0.5 rounded-full text-xs font-semibold shadow-lg">
                                        Más Popular
                                    </span>
                                </div>

                                <!-- Plan Card -->
                                <div class="bg-white rounded-lg border-2 p-5 h-full flex flex-col"
                                     :class="plan.is_current ? 'border-violet-500 shadow-md' : 'border-gray-200'">

                                    <!-- Current Badge -->
                                    <div x-show="plan.is_current" class="mb-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-violet-100 text-violet-800 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Plan Actual
                                        </span>
                                    </div>

                                    <!-- Name & Price -->
                                    <h3 class="text-xl font-bold text-gray-900 mb-1" x-text="plan.name"></h3>
                                    <div class="mb-4">
                                        <span class="text-3xl font-bold text-gray-900" x-text="'$' + plan.price"></span>
                                        <span class="text-gray-600 text-sm">/mes</span>
                                    </div>

                                    <!-- Email Limit -->
                                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                        <div class="text-gray-600 text-xs mb-0.5">Emails con IA mensuales</div>
                                        <div class="text-2xl font-bold text-gray-900" x-text="plan.email_limit"></div>
                                    </div>

                                    <!-- Features -->
                                    <div class="flex-grow mb-4">
                                        <div class="space-y-2">
                                            <template x-for="feature in plan.features" :key="feature">
                                                <div class="flex items-start text-xs">
                                                    <svg class="w-4 h-4 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <span class="text-gray-700" x-text="feature"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Action Button -->
                                    <button x-show="plan.is_current" disabled
                                            class="w-full bg-gray-100 text-gray-400 px-4 py-2 rounded-lg font-semibold cursor-not-allowed text-sm">
                                        Plan Actual
                                    </button>
                                    <button x-show="!plan.is_current" @click="updatePlan(planKey, plan.name, plan.price, plan.email_limit)"
                                            class="w-full bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors text-sm"
                                            :id="'btn-' + planKey">
                                        <span x-text="planKey === 'basic' ? 'Cambiar a Basic' : 'Actualizar a ' + plan.name"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
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
            usageData: null,
            plansData: null,
            notificationsData: {
                email_enabled: true,
                push_enabled: false,
                daily_summary_enabled: false,
                daily_summary_time: '18:00',
                weekly_summary_enabled: false,
                weekly_summary_day: 'monday',
                weekly_summary_time: '09:00',
                monthly_summary_enabled: false,
                monthly_summary_day: 1,
                monthly_summary_time: '09:00',
                limit_warnings_enabled: true,
                warning_threshold_percentage: 80,
                high_activity_alerts: true
            },
            savingNotifications: false,
            notificationsSaved: false,

            init() {
                // Verificar si necesita onboarding automático
                this.checkAutoOnboarding();

                // Escuchar evento para cambiar de pestaña
                window.addEventListener('switch-settings-tab', (e) => {
                    if (e.detail && e.detail.category) {
                        this.activeCategory = e.detail.category;
                        // Cargar datos si es necesario
                        if (e.detail.category === 'usage') {
                            this.loadUsageData();
                        } else if (e.detail.category === 'plans') {
                            this.loadPlansData();
                        }
                    }
                });
            },

            // Computed properties for usage
            get alertBadge() {
                const level = this.usageData?.alert_level;
                if (level === 'blocked') return 'Bloqueado';
                if (level === 'critical') return 'Crítico';
                if (level === 'warning') return 'Advertencia';
                if (level === 'info') return 'Info';
                return 'Normal';
            },

            get alertBadgeClass() {
                const level = this.usageData?.alert_level;
                if (level === 'blocked') return 'bg-red-100 text-red-800';
                if (level === 'critical') return 'bg-orange-100 text-orange-800';
                if (level === 'warning') return 'bg-yellow-100 text-yellow-800';
                if (level === 'info') return 'bg-blue-100 text-blue-800';
                return 'bg-green-100 text-green-800';
            },

            get progressBarColor() {
                const percentage = this.usageData?.usage?.usage_percentage || 0;
                if (percentage >= 90) return 'bg-red-500';
                if (percentage >= 75) return 'bg-orange-500';
                if (percentage >= 50) return 'bg-yellow-500';
                return 'bg-green-500';
            },

            get formatDateRange() {
                if (!this.usageData?.usage) return '-';
                const start = new Date(this.usageData.usage.billing_cycle_start);
                const end = new Date(this.usageData.usage.billing_cycle_end);
                return `${start.toLocaleDateString('es-ES')} - ${end.toLocaleDateString('es-ES')}`;
            },

            formatNumber(num) {
                return new Intl.NumberFormat('es-ES').format(num || 0);
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

            async loadUsageData() {
                if (this.usageData) return; // Already loaded

                try {
                    const response = await fetch('/settings/usage/current', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Error loading usage data');

                    const data = await response.json();
                    this.usageData = data;
                } catch (error) {
                    console.error('Error loading usage:', error);
                    this.usageData = { error: true };
                }
            },

            async loadPlansData() {
                if (this.plansData) return; // Already loaded

                try {
                    // Cargar datos de uso primero para mostrar uso actual
                    const usageResponse = await fetch('/settings/usage/current', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    let currentUsage = null;
                    if (usageResponse.ok) {
                        currentUsage = await usageResponse.json();
                    }

                    // Simular datos de planes (igual que el controlador)
                    const user = currentUsage?.user || {};
                    this.plansData = {
                        plans: {
                            basic: {
                                name: 'Basic',
                                price: 5,
                                email_limit: 100,
                                features: [
                                    'Análisis automático de emails',
                                    'Respuestas sugeridas',
                                    'Reglas básicas de filtrado',
                                    'Soporte por email (48h)'
                                ],
                                is_current: user.plan_type === 'basic'
                            },
                            starter: {
                                name: 'Starter',
                                price: 15,
                                email_limit: 250,
                                features: [
                                    'Todo de Basic',
                                    'Respuestas automáticas',
                                    'Análisis semanal',
                                    'Perfiles de comunicación',
                                    'Soporte prioritario (24h)'
                                ],
                                is_current: user.plan_type === 'starter',
                                popular: true
                            },
                            pro: {
                                name: 'Pro',
                                price: 30,
                                email_limit: 450,
                                features: [
                                    'Todo de Starter',
                                    'Reglas ilimitadas',
                                    'API access',
                                    'Analytics avanzados',
                                    'Soporte 24/7'
                                ],
                                is_current: user.plan_type === 'pro'
                            }
                        },
                        currentUsage
                    };
                } catch (error) {
                    console.error('Error loading plans:', error);
                    this.plansData = { error: true };
                }
            },

            async updatePlan(planKey, planName, planPrice, emailLimit) {
                const confirmed = confirm(`¿Confirmas el cambio al plan ${planName} ($${planPrice}/mes - ${emailLimit} emails)?\n\nTu límite se reiniciará inmediatamente.`);
                if (!confirmed) return;

                const button = document.getElementById(`btn-${planKey}`);
                const originalText = button ? button.textContent : '';

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Procesando...';
                }

                try {
                    const response = await fetch('/settings/plans/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ plan: planKey })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('✅ Plan actualizado exitosamente');
                        // Reload data
                        this.plansData = null;
                        this.usageData = null;
                        await this.loadPlansData();
                        await this.loadUsageData();
                    } else {
                        alert('Error al actualizar el plan: ' + (data.message || 'Error desconocido'));
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al actualizar el plan. Por favor, intenta de nuevo.');
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
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
            },

            async loadNotificationsData() {
                // Permitir recargar datos incluso si ya existen valores por defecto
                try {
                    const response = await fetch('/settings/notifications/data', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Error loading notifications data');

                    const data = await response.json();
                    this.notificationsData = data.preferences;
                } catch (error) {
                    console.error('Error loading notifications:', error);
                    // Valores por defecto si falla
                    this.notificationsData = {
                        email_enabled: true,
                        push_enabled: false,
                        daily_summary_enabled: false,
                        daily_summary_time: '18:00',
                        weekly_summary_enabled: false,
                        weekly_summary_day: 'monday',
                        weekly_summary_time: '09:00',
                        monthly_summary_enabled: false,
                        monthly_summary_day: 1,
                        monthly_summary_time: '09:00',
                        limit_warnings_enabled: true,
                        warning_threshold_percentage: 80,
                        high_activity_alerts: false
                    };
                }
            },

            async saveNotifications() {
                this.savingNotifications = true;
                this.notificationsSaved = false;

                try {
                    const response = await fetch('/settings/notifications', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.notificationsData)
                    });

                    const data = await response.json();

                    if (data.success || response.ok) {
                        this.notificationsSaved = true;
                        setTimeout(() => {
                            this.notificationsSaved = false;
                        }, 3000);
                    } else {
                        alert('Error al guardar preferencias: ' + (data.message || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al guardar preferencias. Por favor, intenta de nuevo.');
                } finally {
                    this.savingNotifications = false;
                }
            }
        }
    }
</script>
