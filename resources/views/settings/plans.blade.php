<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Planes de Vera - Virtual Employee</title>
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
                        <a href="{{ route('settings.usage') }}" class="hidden sm:block text-gray-300 hover:text-white transition-colors duration-200">Mi Uso</a>
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
    <section class="relative min-h-screen overflow-hidden grid-pattern pt-20">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-1/4 w-96 h-96 bg-violet-500/20 rounded-full blur-[128px] gradient-animate"></div>
            <div class="absolute bottom-1/4 -right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-[128px] gradient-animate" style="animation-delay: -5s;"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    <span class="gradient-text-violet">Planes de</span>
                    <span class="text-white"> Vera</span>
                </h1>
                <p class="text-xl text-gray-400 mb-4">
                    Elige el plan que mejor se adapte a tus necesidades
                </p>
                @if($currentUsage)
                    <p class="text-sm text-gray-500">
                        Uso actual: {{ $currentUsage['emails_processed'] }} / {{ $currentUsage['email_limit'] }} emails este mes
                    </p>
                @endif
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                @foreach($plans as $planKey => $plan)
                    <div class="relative">
                        <!-- Popular Badge -->
                        @if(isset($plan['popular']) && $plan['popular'])
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 z-10">
                                <span class="bg-gradient-to-r from-violet-500 to-purple-600 text-white px-4 py-1 rounded-full text-sm font-semibold shadow-lg">
                                    Más Popular
                                </span>
                            </div>
                        @endif

                        <!-- Plan Card -->
                        <div class="card-dark rounded-2xl p-8 h-full flex flex-col {{ $plan['is_current'] ? 'border-2 border-violet-500' : '' }} {{ isset($plan['popular']) && $plan['popular'] ? 'transform scale-105' : '' }}">
                            <!-- Current Plan Badge -->
                            @if($plan['is_current'])
                                <div class="mb-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-violet-500/20 text-violet-400 text-sm font-semibold">
                                        <span class="w-2 h-2 bg-violet-400 rounded-full mr-2 pulse-violet"></span>
                                        Plan Actual
                                    </span>
                                </div>
                            @endif

                            <!-- Plan Name & Price -->
                            <h3 class="text-2xl font-bold text-white mb-2">{{ $plan['name'] }}</h3>
                            <div class="mb-6">
                                <span class="text-5xl font-bold text-white">${{ $plan['price'] }}</span>
                                <span class="text-gray-400">/mes</span>
                            </div>

                            <!-- Email Limit -->
                            <div class="mb-6 p-4 bg-gray-800/50 rounded-xl">
                                <div class="text-gray-400 text-sm mb-1">Emails con IA mensuales</div>
                                <div class="text-3xl font-bold text-white">{{ $plan['email_limit'] }}</div>
                                <div class="text-gray-500 text-xs mt-1">≈ {{ number_format($plan['token_limit']) }} tokens</div>
                            </div>

                            <!-- Features List -->
                            <div class="flex-grow mb-6">
                                <div class="space-y-3">
                                    @foreach($plan['features'] as $feature)
                                        <div class="flex items-start">
                                            <svg class="w-5 h-5 text-violet-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="text-gray-300 text-sm">{{ $feature }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Action Button -->
                            @if($plan['is_current'])
                                <button disabled class="w-full bg-gray-700 text-gray-400 px-6 py-3 rounded-xl font-semibold cursor-not-allowed">
                                    Plan Actual
                                </button>
                            @else
                                <button
                                    onclick="updatePlan('{{ $planKey }}')"
                                    class="w-full button-primary px-6 py-3 rounded-xl font-semibold"
                                    id="btn-{{ $planKey }}"
                                >
                                    @if($planKey === 'basic')
                                        Cambiar a Basic
                                    @else
                                        Actualizar a {{ $plan['name'] }}
                                    @endif
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Additional Info -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 text-violet-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Información sobre los planes
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-400">
                    <div>
                        <h4 class="text-white font-semibold mb-2">Cambios de plan</h4>
                        <ul class="space-y-2 text-sm">
                            <li>• Al actualizar, tu límite se reinicia inmediatamente</li>
                            <li>• Puedes cambiar de plan en cualquier momento</li>
                            <li>• Los cambios se aplican de forma instantánea</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-2">Procesamiento de emails</h4>
                        <ul class="space-y-2 text-sm">
                            <li>• Emails procesados solo con reglas son gratis</li>
                            <li>• Solo emails complejos usan IA (≈20% del total)</li>
                            <li>• Vera sigue funcionando aunque alcances el límite</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-white mb-6">Preguntas frecuentes</h3>
                <div class="space-y-6">
                    <div>
                        <h4 class="text-white font-semibold mb-2">¿Qué pasa si supero mi límite?</h4>
                        <p class="text-gray-400 text-sm">Vera seguirá procesando tus emails usando solo reglas (sin IA). No perderás funcionalidad básica, solo el análisis avanzado con inteligencia artificial.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-2">¿Cuándo se reinicia mi límite?</h4>
                        <p class="text-gray-400 text-sm">Tu límite se reinicia automáticamente el primer día de cada mes a las 00:00.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-2">¿Puedo cambiar de plan en cualquier momento?</h4>
                        <p class="text-gray-400 text-sm">Sí, puedes actualizar o cambiar tu plan cuando lo desees. Al actualizar, tu límite se reinicia inmediatamente.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-2">¿Qué son los tokens?</h4>
                        <p class="text-gray-400 text-sm">Los tokens son unidades de medida del procesamiento de IA. En promedio, un email procesado con IA consume alrededor de 1,100 tokens. Esta métrica técnica te permite entender el consumo detallado.</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <a href="{{ route('settings.usage') }}" class="text-violet-400 hover:text-violet-300 transition-colors duration-200 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Ver mi uso actual
                </a>
                <a href="{{ route('dashboard') }}" class="text-violet-400 hover:text-violet-300 transition-colors duration-200 inline-flex items-center">
                    Volver al Dashboard
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <script>
        async function updatePlan(plan) {
            const button = document.getElementById(`btn-${plan}`);
            const originalText = button.textContent;

            // Confirm
            const planNames = {
                'basic': 'Basic ($5/mes - 100 emails)',
                'starter': 'Starter ($15/mes - 250 emails)',
                'pro': 'Pro ($30/mes - 450 emails)'
            };

            if (!confirm(`¿Confirmas el cambio al plan ${planNames[plan]}?\n\nTu límite se reiniciará inmediatamente.`)) {
                return;
            }

            // Disable button
            button.disabled = true;
            button.textContent = 'Procesando...';

            try {
                const response = await fetch('{{ route("settings.plans.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ plan })
                });

                const data = await response.json();

                if (data.success) {
                    // Success - reload page to show updated plan
                    window.location.reload();
                } else {
                    alert('Error al actualizar el plan: ' + (data.message || 'Error desconocido'));
                    button.disabled = false;
                    button.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al actualizar el plan. Por favor, intenta de nuevo.');
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    </script>

</body>
</html>
