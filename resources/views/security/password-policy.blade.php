@extends('layouts.app')

@section('content')
    <div class="py-12" x-data="passwordPolicyApp()">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Seguridad de Contraseña</h2>
                    <p class="mt-1 text-sm text-gray-600">Gestiona tu contraseña y revisa las políticas de seguridad</p>
                </div>
                <a href="{{ route('security.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a Seguridad
                </a>
            </div>

            <!-- Alertas de sesión -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-4 py-3">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif

            <!-- Alerta de contraseña expirada -->
            @if($isExpired)
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="font-medium text-red-800">Tu contraseña ha expirado</h3>
                            <p class="mt-1 text-sm text-red-700">Debes cambiar tu contraseña para continuar usando la aplicación de forma segura.</p>
                        </div>
                    </div>
                </div>
            @elseif($shouldWarn)
                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="font-medium text-yellow-800">Tu contraseña expirará pronto</h3>
                            <p class="mt-1 text-sm text-yellow-700">Le quedan <strong>{{ $daysUntilExpiry }} días</strong> para que tu contraseña expire. Te recomendamos cambiarla ahora.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ========== CAMBIAR CONTRASEÑA ========== -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900">Cambiar Contraseña</h3>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="{{ route('security.password-policy.change') }}">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900 mb-1">Contraseña actual</label>
                                        <input type="password" name="current_password" required
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none @error('current_password') border-red-500 @enderror"
                                               placeholder="••••••••">
                                        @error('current_password')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900 mb-1">Nueva contraseña</label>
                                        <input type="password" name="new_password" id="new_password" required
                                               x-model="newPassword"
                                               @input="checkStrength"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none @error('new_password') border-red-500 @enderror"
                                               placeholder="••••••••">
                                        @error('new_password')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        <!-- Indicador de fortaleza -->
                                        <div class="mt-2" x-show="newPassword.length > 0">
                                            <div class="flex gap-1 mb-1">
                                                <template x-for="i in 4" :key="i">
                                                    <div class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                                                         :class="i <= strength ? (strength <= 1 ? 'bg-red-500' : strength <= 2 ? 'bg-yellow-500' : strength <= 3 ? 'bg-blue-500' : 'bg-green-500') : 'bg-gray-200'"></div>
                                                </template>
                                            </div>
                                            <p class="text-xs" :class="strength <= 1 ? 'text-red-600' : strength <= 2 ? 'text-yellow-600' : strength <= 3 ? 'text-blue-600' : 'text-green-600'"
                                               x-text="strengthLabel"></p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900 mb-1">Confirmar nueva contraseña</label>
                                        <input type="password" name="new_password_confirmation" required
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none"
                                               placeholder="••••••••">
                                    </div>
                                    <button type="submit"
                                            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                                        Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ========== HISTORIAL DE CAMBIOS ========== -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900">Historial de Cambios</h3>
                            </div>
                            <span class="text-xs text-gray-500">{{ $historyStats['total_changes'] ?? 0 }} cambios en total</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse($passwordHistory as $change)
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                Contraseña cambiada por
                                                <span class="text-purple-600">{{ ucfirst($change['changed_by'] ?? 'usuario') }}</span>
                                            </p>
                                            <p class="text-xs text-gray-500">IP: {{ $change['ip_address'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400">
                                        {{ isset($change['changed_at']) ? \Carbon\Carbon::parse($change['changed_at'])->diffForHumans() : '-' }}
                                    </span>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-sm text-gray-500">Sin historial de cambios</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- ========== POLÍTICA ACTIVA ========== -->
                <div class="space-y-6">
                    @if($policy)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h3 class="font-semibold text-gray-900">Requisitos Actuales</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Política: {{ $policy->name }}</p>
                            </div>
                            <div class="p-6 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Longitud mínima</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $policy->min_length }} caracteres</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Mayúsculas</span>
                                    @if($policy->require_uppercase)
                                        <span class="inline-flex items-center text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Requerido
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Opcional</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Minúsculas</span>
                                    @if($policy->require_lowercase)
                                        <span class="inline-flex items-center text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Requerido
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Opcional</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Números</span>
                                    @if($policy->require_numbers)
                                        <span class="inline-flex items-center text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Requerido
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Opcional</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Caracteres especiales</span>
                                    @if($policy->require_special_chars)
                                        <span class="inline-flex items-center text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Requerido
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Opcional</span>
                                    @endif
                                </div>
                                <div class="border-t border-gray-100 pt-3 mt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700">Expiración</span>
                                        @if($policy->expires)
                                            <span class="text-xs text-orange-600 font-medium">Cada {{ $policy->expiry_days }} días</span>
                                        @else
                                            <span class="text-xs text-gray-400">Nunca expira</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm text-gray-700">No reutilizar</span>
                                        @if($policy->prevent_reuse)
                                            <span class="text-xs text-purple-600 font-medium">Últimas {{ $policy->history_count }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">Sin restricción</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm text-gray-700">Bloqueo por intentos</span>
                                        @if($policy->lockout_enabled)
                                            <span class="text-xs text-red-600 font-medium">{{ $policy->max_attempts }} intentos</span>
                                        @else
                                            <span class="text-xs text-gray-400">Desactivado</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info de expiración -->
                        @if($policy->expires && $daysUntilExpiry !== null)
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                                <h4 class="font-medium text-gray-900 mb-3 text-sm">Estado de tu contraseña</h4>
                                <div class="relative pt-1">
                                    @php
                                        $percent = $policy->expiry_days > 0 ? min(100, max(0, (($policy->expiry_days - $daysUntilExpiry) / $policy->expiry_days) * 100)) : 0;
                                        $barColor = $percent >= 80 ? 'bg-red-500' : ($percent >= 60 ? 'bg-yellow-500' : 'bg-green-500');
                                    @endphp
                                    <div class="flex mb-1 items-center justify-between">
                                        <span class="text-xs text-gray-600">Días restantes: <strong>{{ $daysUntilExpiry }}</strong></span>
                                        <span class="text-xs text-gray-500">de {{ $policy->expiry_days }}</span>
                                    </div>
                                    <div class="overflow-hidden h-2 rounded-full bg-gray-200">
                                        <div class="h-2 rounded-full transition-all {{ $barColor }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <p class="text-sm text-gray-500">Sin política activa</p>
                        </div>
                    @endif

                    <!-- Stats -->
                    @if($historyStats['total_changes'] > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                            <h4 class="font-medium text-gray-900 mb-3 text-sm">Estadísticas</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total de cambios</span>
                                    <span class="font-medium text-gray-900">{{ $historyStats['total_changes'] }}</span>
                                </div>
                                @if($historyStats['last_change'])
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Último cambio</span>
                                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($historyStats['last_change'])->diffForHumans() }}</span>
                                    </div>
                                @endif
                                @if($historyStats['average_days_between_changes'])
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Promedio entre cambios</span>
                                        <span class="font-medium text-gray-900">{{ round($historyStats['average_days_between_changes']) }} días</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    function passwordPolicyApp() {
        return {
            newPassword: '',
            strength: 0,
            strengthLabel: '',
            checkStrength() {
                const p = this.newPassword;
                let score = 0;
                if (p.length >= 8) score++;
                if (p.length >= 12) score++;
                if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
                if (/[0-9]/.test(p)) score++;
                if (/[^A-Za-z0-9]/.test(p)) score++;
                this.strength = Math.min(4, Math.ceil(score * 4 / 5));
                const labels = ['', 'Muy débil', 'Débil', 'Buena', 'Muy segura'];
                this.strengthLabel = labels[this.strength] || '';
            }
        }
    }
    </script>
@endsection
