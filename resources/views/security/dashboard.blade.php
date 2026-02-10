<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Centro de Seguridad</h2>
                <p class="mt-1 text-sm text-gray-600">Gestiona toda la seguridad de tu cuenta desde un solo lugar</p>
            </div>

            <!-- Alertas importantes -->
            @if($isExpired)
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-medium text-red-800">⚠️ Tu contraseña ha expirado</h3>
                            <p class="text-sm text-red-700 mt-0.5">Cámbiala ahora para mantener la seguridad de tu cuenta.</p>
                        </div>
                        <a href="{{ route('security.password-policy.index') }}" class="ml-4 text-sm font-medium text-red-700 underline hover:text-red-900">
                            Cambiar ahora
                        </a>
                    </div>
                </div>
            @elseif($shouldWarn)
                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-medium text-yellow-800">Tu contraseña expira en {{ $daysUntilExpiry }} días</h3>
                        </div>
                        <a href="{{ route('security.password-policy.index') }}" class="ml-4 text-sm font-medium text-yellow-700 underline hover:text-yellow-900">
                            Cambiar ahora
                        </a>
                    </div>
                </div>
            @endif

            @if($auditStats['critical'] > 0)
                <div class="mb-4 bg-orange-50 border-l-4 border-orange-400 p-4 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-orange-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-medium text-orange-800">{{ $auditStats['critical'] }} evento(s) crítico(s) en tu historial</h3>
                        </div>
                        <a href="{{ route('security.audit.index', ['severity' => 'critical']) }}" class="ml-4 text-sm font-medium text-orange-700 underline hover:text-orange-900">
                            Ver eventos
                        </a>
                    </div>
                </div>
            @endif

            <!-- Tarjetas de secciones de seguridad -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

                <!-- Sesiones -->
                <a href="{{ route('security.sessions.index') }}"
                   class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-purple-300 transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                            </svg>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Sesiones Activas</h3>
                    <p class="text-xs text-gray-500 mb-3">Gestiona los dispositivos conectados</p>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="text-blue-600 font-medium">{{ $sessionStats['active'] ?? 0 }} activas</span>
                        <span class="text-gray-400">·</span>
                        <span class="text-gray-500">{{ $sessionStats['total'] ?? 0 }} total</span>
                    </div>
                </a>

                <!-- Contraseña -->
                <a href="{{ route('security.password-policy.index') }}"
                   class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-purple-300 transition-all group {{ $isExpired ? 'border-red-300 bg-red-50' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        @if($isExpired)
                            <span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-0.5 rounded-full animate-pulse">Expirada</span>
                        @elseif($shouldWarn)
                            <span class="text-xs font-medium text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded-full">{{ $daysUntilExpiry }}d</span>
                        @else
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Contraseña</h3>
                    <p class="text-xs text-gray-500 mb-3">Política y cambio de contraseña</p>
                    <div class="text-xs">
                        @if($policy)
                            <span class="text-purple-600 font-medium">Política activa</span>
                        @else
                            <span class="text-gray-400">Sin política activa</span>
                        @endif
                    </div>
                </a>

                <!-- RGPD -->
                <a href="{{ route('gdpr.index') }}"
                   class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-purple-300 transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition-colors">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Privacidad (RGPD)</h3>
                    <p class="text-xs text-gray-500 mb-3">Exporta o elimina tus datos</p>
                    <div class="text-xs text-green-600 font-medium">Tus derechos garantizados</div>
                </a>

                <!-- Auditoría -->
                <a href="{{ route('security.audit.index') }}"
                   class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-purple-300 transition-all group {{ $auditStats['critical'] > 0 ? 'border-orange-300' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        @if($auditStats['critical'] > 0)
                            <span class="text-xs font-medium text-orange-600 bg-orange-100 px-2 py-0.5 rounded-full">{{ $auditStats['critical'] }} críticos</span>
                        @else
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Auditoría</h3>
                    <p class="text-xs text-gray-500 mb-3">Historial completo de actividad</p>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-orange-600 font-medium">{{ $auditStats['today'] }} hoy</span>
                        <span class="text-gray-400">·</span>
                        <span class="text-gray-500">{{ number_format($auditStats['total']) }} total</span>
                    </div>
                </a>
            </div>

            <!-- Dos columnas: actividad reciente + eventos críticos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Actividad reciente -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Actividad Reciente</h3>
                        <a href="{{ route('security.audit.index') }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Ver todo →</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse($recentActivity as $log)
                            <div class="px-6 py-3 flex items-start gap-3">
                                @php
                                    $catIcons = [
                                        'auth'     => ['icon' => '🔑', 'bg' => 'bg-blue-50'],
                                        'email'    => ['icon' => '📧', 'bg' => 'bg-purple-50'],
                                        'security' => ['icon' => '🛡️', 'bg' => 'bg-red-50'],
                                        'settings' => ['icon' => '⚙️', 'bg' => 'bg-yellow-50'],
                                        'data'     => ['icon' => '💾', 'bg' => 'bg-green-50'],
                                    ];
                                    $catInfo = $catIcons[$log->event_category] ?? ['icon' => '📋', 'bg' => 'bg-gray-50'];
                                @endphp
                                <div class="w-7 h-7 {{ $catInfo['bg'] }} rounded-full flex items-center justify-center flex-shrink-0 text-sm">
                                    {{ $catInfo['icon'] }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 truncate">{{ $log->description ?? $log->event_type ?? 'Acción registrada' }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                                @if($log->severity === 'critical')
                                    <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded flex-shrink-0">Crítico</span>
                                @endif
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center">
                                <p class="text-sm text-gray-500">Sin actividad reciente</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Eventos críticos -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Eventos Críticos</h3>
                        <a href="{{ route('security.audit.index', ['severity' => 'critical']) }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Ver todo →</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse($criticalEvents as $event)
                            <div class="px-6 py-3 flex items-start gap-3">
                                <div class="w-7 h-7 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 truncate">{{ $event->description ?? $event->event_type ?? 'Evento crítico' }}</p>
                                    <p class="text-xs text-gray-500">{{ $event->created_at->diffForHumans() }} · IP: {{ $event->ip_address ?? '-' }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-900">Todo en orden</p>
                                <p class="text-xs text-gray-500 mt-1">No hay eventos críticos registrados</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Info de sesiones -->
            @if(isset($sessionStats['suspicious_count']) && $sessionStats['suspicious_count'] > 0)
                <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-xl p-5">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-medium text-yellow-800">Actividad sospechosa detectada en sesiones</h3>
                            <p class="text-sm text-yellow-700">Hemos detectado accesos desde múltiples ubicaciones o dispositivos inusuales.</p>
                        </div>
                        <a href="{{ route('security.sessions.index') }}" class="ml-4 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Revisar sesiones
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
