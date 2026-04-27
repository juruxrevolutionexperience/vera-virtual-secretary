<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Uso de Vera') }}
        </h2>
    </x-slot>

    <div class="py-6 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Alert Messages -->
            @if (session('limit_exceeded'))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-medium">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Current Plan -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            Plan Actual
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Monitorea tu consumo mensual de emails procesados con Vera.
                        </p>
                    </header>

                    <div class="space-y-4">
                        <!-- Plan Badge -->
                        <div class="flex items-center justify-between pb-4 border-b">
                            <span class="text-gray-700">Plan:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                @if($alertLevel === 'blocked') bg-red-100 text-red-800
                                @elseif($alertLevel === 'critical') bg-orange-100 text-orange-800
                                @elseif($alertLevel === 'warning') bg-yellow-100 text-yellow-800
                                @elseif($alertLevel === 'info') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ ucfirst($user->plan_type) }}
                            </span>
                        </div>

                        <!-- Limits -->
                        <div class="flex items-center justify-between pb-4 border-b">
                            <span class="text-gray-700">Límite mensual:</span>
                            <span class="text-gray-900 font-semibold">{{ $usage['email_limit'] }} emails</span>
                        </div>

                        <div class="flex items-center justify-between pb-4 border-b">
                            <span class="text-gray-700">Emails procesados:</span>
                            <span class="text-gray-900 font-semibold">{{ $usage['emails_processed'] }} emails</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Emails restantes:</span>
                            <span class="font-semibold {{ $usage['emails_remaining'] < 10 ? 'text-red-600' : ($usage['emails_remaining'] < 30 ? 'text-yellow-600' : 'text-gray-900') }}">
                                {{ $usage['emails_remaining'] }} emails
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Progress -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            Progreso de Uso
                        </h2>
                    </header>

                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">{{ $usage['usage_percentage'] }}% utilizado</span>
                            <span class="text-sm text-gray-600">{{ $usage['emails_processed'] }} / {{ $usage['email_limit'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            @php
                                $progressColor = 'bg-green-500';
                                if ($usage['usage_percentage'] >= 90) {
                                    $progressColor = 'bg-red-500';
                                } elseif ($usage['usage_percentage'] >= 75) {
                                    $progressColor = 'bg-orange-500';
                                } elseif ($usage['usage_percentage'] >= 50) {
                                    $progressColor = 'bg-yellow-500';
                                }
                            @endphp
                            <div class="{{ $progressColor }} h-full transition-all duration-500" style="width: {{ min($usage['usage_percentage'], 100) }}%"></div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-gray-600 text-sm mb-1">Ciclo actual</div>
                            <div class="text-gray-900 font-medium text-sm">
                                {{ \Carbon\Carbon::parse($usage['billing_cycle_start'])->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($usage['billing_cycle_end'])->format('d/m/Y') }}
                            </div>
                        </div>

                        @if($usage['projected_limit_date'])
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-gray-600 text-sm mb-1">Proyección de límite</div>
                            <div class="text-gray-900 font-medium text-sm">
                                {{ \Carbon\Carbon::parse($usage['projected_limit_date'])->format('d/m/Y') }}
                            </div>
                        </div>
                        @endif

                        @if(isset($usage['avg_emails_per_week']))
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-gray-600 text-sm mb-1">Promedio semanal</div>
                            <div class="text-gray-900 font-medium text-sm">{{ number_format($usage['avg_emails_per_week'], 1) }} emails/semana</div>
                        </div>
                        @endif

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-gray-600 text-sm mb-1">Tokens consumidos</div>
                            <div class="text-gray-900 font-medium text-sm">{{ number_format($usage['total_tokens']) }} tokens</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upgrade CTA -->
            @if($usage['emails_remaining'] < 20 || $alertLevel === 'blocked')
            <div class="p-4 sm:p-8 bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-200 shadow sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            @if($alertLevel === 'blocked')
                                Has alcanzado el límite de tu plan
                            @else
                                Te estás quedando sin emails
                            @endif
                        </h3>
                        <p class="text-sm text-gray-600">
                            @if($alertLevel === 'blocked')
                                Vera seguirá procesando emails con reglas, pero sin análisis con IA. Actualiza tu plan para desbloquear más capacidad.
                            @else
                                Considera actualizar tu plan para obtener más capacidad
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('settings.plans') }}" class="ml-4 inline-flex items-center px-4 py-2 bg-violet-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-violet-700 focus:bg-violet-700 active:bg-violet-900 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Ver Planes
                    </a>
                </div>
            </div>
            @endif

            <!-- Info -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header class="mb-4">
                        <h2 class="text-lg font-medium text-gray-900">
                            ¿Cómo funciona el límite?
                        </h2>
                    </header>

                    <div class="space-y-3 text-sm text-gray-600">
                        <p>• Cada email analizado con IA consume 1 email de tu límite mensual</p>
                        <p>• Los emails procesados solo con reglas no consumen límite</p>
                        <p>• Cuando alcanzas el límite, Vera sigue funcionando con reglas (sin IA)</p>
                        <p>• Tu límite se reinicia el primer día de cada mes</p>
                        <p>• El mismo email no se cuenta dos veces en el mismo mes</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
