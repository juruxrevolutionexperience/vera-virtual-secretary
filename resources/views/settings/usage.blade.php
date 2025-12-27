<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uso de Vera - Virtual Employee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="antialiased overflow-x-hidden">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-effect border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center glow-violet">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text-violet">Virtual Employee</span>
                    </a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hidden sm:block text-gray-300 hover:text-white transition-colors duration-200">Dashboard</a>
                        <a href="{{ route('settings.plans') }}" class="hidden sm:block text-gray-300 hover:text-white transition-colors duration-200">Planes</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-300 hover:text-white transition-colors duration-200">
                                Cerrar sesión
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden grid-pattern pt-20">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-1/4 w-96 h-96 bg-violet-500/20 rounded-full blur-[128px] gradient-animate"></div>
            <div class="absolute bottom-1/4 -right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-[128px] gradient-animate" style="animation-delay: -5s;"></div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    <span class="gradient-text-violet">Uso de</span>
                    <span class="text-white"> Vera</span>
                </h1>
                <p class="text-xl text-gray-400">
                    Monitorea tu consumo de emails procesados
                </p>
            </div>

            <!-- Alert Messages -->
            @if (session('limit_exceeded'))
                <div class="card-gradient rounded-2xl p-6 mb-8 border border-red-500/20">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-300 font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Current Plan Card -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-8 h-8 text-violet-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        Plan {{ ucfirst($user->plan_type) }}
                    </h2>

                    @if($alertLevel !== 'normal')
                        @php
                            $alertColors = [
                                'info' => 'bg-blue-500/20 text-blue-400',
                                'warning' => 'bg-yellow-500/20 text-yellow-400',
                                'critical' => 'bg-orange-500/20 text-orange-400',
                                'blocked' => 'bg-red-500/20 text-red-400',
                            ];
                            $alertMessages = [
                                'info' => 'Consumo moderado',
                                'warning' => 'Acercándose al límite',
                                'critical' => 'Cerca del límite',
                                'blocked' => 'Límite alcanzado',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full {{ $alertColors[$alertLevel] ?? '' }} text-sm font-semibold">
                            <span class="w-2 h-2 {{ str_replace('text-', 'bg-', $alertColors[$alertLevel] ?? '') }} rounded-full mr-2 pulse-violet"></span>
                            {{ $alertMessages[$alertLevel] ?? '' }}
                        </span>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <span class="text-gray-400">Límite mensual:</span>
                        <span class="text-white font-semibold text-lg">{{ $usage['email_limit'] }} emails</span>
                    </div>
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <span class="text-gray-400">Emails procesados:</span>
                        <span class="text-white font-semibold text-lg">{{ $usage['emails_processed'] }} emails</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Emails restantes:</span>
                        <span class="text-white font-semibold text-lg {{ $usage['emails_remaining'] < 10 ? 'text-red-400' : ($usage['emails_remaining'] < 30 ? 'text-yellow-400' : '') }}">
                            {{ $usage['emails_remaining'] }} emails
                        </span>
                    </div>
                </div>
            </div>

            <!-- Usage Progress Card -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-white mb-6">Progreso de uso</h3>

                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-400">{{ $usage['usage_percentage'] }}% utilizado</span>
                        <span class="text-sm text-gray-400">{{ $usage['emails_processed'] }} / {{ $usage['email_limit'] }}</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-4 overflow-hidden">
                        @php
                            $progressColor = 'bg-gradient-to-r from-violet-500 to-purple-600';
                            if ($usage['usage_percentage'] >= 90) {
                                $progressColor = 'bg-gradient-to-r from-red-500 to-red-600';
                            } elseif ($usage['usage_percentage'] >= 75) {
                                $progressColor = 'bg-gradient-to-r from-orange-500 to-orange-600';
                            } elseif ($usage['usage_percentage'] >= 50) {
                                $progressColor = 'bg-gradient-to-r from-yellow-500 to-yellow-600';
                            }
                        @endphp
                        <div class="{{ $progressColor }} h-full rounded-full transition-all duration-500" style="width: {{ min($usage['usage_percentage'], 100) }}%"></div>
                    </div>
                </div>

                <!-- Usage Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-800/50 rounded-xl p-4">
                        <div class="text-gray-400 text-sm mb-1">Ciclo actual</div>
                        <div class="text-white font-semibold">
                            {{ \Carbon\Carbon::parse($usage['billing_cycle_start'])->format('d/m/Y') }} -
                            {{ \Carbon\Carbon::parse($usage['billing_cycle_end'])->format('d/m/Y') }}
                        </div>
                    </div>

                    @if($usage['projected_limit_date'])
                    <div class="bg-gray-800/50 rounded-xl p-4">
                        <div class="text-gray-400 text-sm mb-1">Proyección de límite</div>
                        <div class="text-white font-semibold">
                            {{ \Carbon\Carbon::parse($usage['projected_limit_date'])->format('d/m/Y') }}
                        </div>
                    </div>
                    @endif

                    @if(isset($usage['avg_emails_per_week']))
                    <div class="bg-gray-800/50 rounded-xl p-4">
                        <div class="text-gray-400 text-sm mb-1">Promedio semanal</div>
                        <div class="text-white font-semibold">{{ number_format($usage['avg_emails_per_week'], 1) }} emails/semana</div>
                    </div>
                    @endif

                    <div class="bg-gray-800/50 rounded-xl p-4">
                        <div class="text-gray-400 text-sm mb-1">Tokens consumidos</div>
                        <div class="text-white font-semibold">{{ number_format($usage['total_tokens']) }} tokens</div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            @if($usage['emails_remaining'] < 20 || $alertLevel === 'blocked')
            <div class="card-gradient rounded-2xl p-8 mb-8 border border-violet-500/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">
                            @if($alertLevel === 'blocked')
                                Has alcanzado el límite de tu plan
                            @else
                                Te estás quedando sin emails
                            @endif
                        </h3>
                        <p class="text-gray-400">
                            @if($alertLevel === 'blocked')
                                Vera seguirá procesando emails con reglas, pero sin análisis con IA. Actualiza tu plan para desbloquear más capacidad.
                            @else
                                Considera actualizar tu plan para obtener más capacidad
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('settings.plans') }}" class="button-primary px-6 py-3 rounded-xl font-semibold whitespace-nowrap ml-4">
                        Ver Planes
                    </a>
                </div>
            </div>
            @endif

            <!-- Additional Info -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 text-violet-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ¿Cómo funciona el límite?
                </h3>
                <div class="space-y-3 text-gray-400">
                    <p>• Cada email analizado con IA consume 1 email de tu límite mensual</p>
                    <p>• Los emails procesados solo con reglas no consumen límite</p>
                    <p>• Cuando alcanzas el límite, Vera sigue funcionando con reglas (sin IA)</p>
                    <p>• Tu límite se reinicia el primer día de cada mes</p>
                    <p>• El mismo email no se cuenta dos veces en el mismo mes</p>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-violet-400 hover:text-violet-300 transition-colors duration-200 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al Dashboard
                </a>
                <a href="{{ route('settings.plans') }}" class="text-violet-400 hover:text-violet-300 transition-colors duration-200 inline-flex items-center">
                    Ver todos los planes
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

</body>
</html>
