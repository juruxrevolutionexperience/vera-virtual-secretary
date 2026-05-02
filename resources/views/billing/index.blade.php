<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Facturación - Virtual Employee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased overflow-x-hidden">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-effect border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('landing-new') }}" class="flex items-center space-x-3">
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
                        <a href="{{ route('profile.edit') }}" class="hidden sm:block text-gray-300 hover:text-white transition-colors duration-200">Perfil</a>
                        <a href="{{ url('/dashboard') }}" class="hidden sm:block text-gray-300 hover:text-white transition-colors duration-200">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-300 hover:text-white transition-colors duration-200">
                                Cerrar sesión
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:block text-gray-300 hover:text-white transition-colors duration-200">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="button-primary px-6 py-2.5 rounded-xl font-semibold text-white">
                            Comenzar gratis
                        </a>
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

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-32">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    <span class="gradient-text-violet">Gestiona tu</span>
                    <span class="text-white"> Suscripción</span>
                </h1>
                <p class="text-xl text-gray-400">
                    Administra tu plan y métodos de pago
                </p>
            </div>

            <!-- Status Messages -->
            @if (request('success'))
                <div class="card-gradient rounded-2xl p-6 mb-8 border border-green-500/20">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-green-300 font-semibold">Pago completado. ¡Gracias por tu suscripción!</span>
                    </div>
                </div>
            @elseif (request('canceled'))
                <div class="card-gradient rounded-2xl p-6 mb-8 border border-yellow-500/20">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-yellow-300 font-semibold">Pago cancelado. Puedes intentarlo nuevamente.</span>
                    </div>
                </div>
            @endif

            <!-- Subscription Status -->
            <div class="card-dark rounded-2xl p-4 sm:p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <svg class="w-8 h-8 text-violet-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Estado de tu Suscripción
                </h2>

                @auth
                    @if(auth()->user()->subscribed('default'))
                        <div class="space-y-4">
                            <div class="flex items-center justify-between pb-4 border-b border-white/10">
                                <span class="text-gray-400">Plan actual:</span>
                                <span class="text-white font-semibold text-lg">
                                    {{ ucfirst(auth()->user()->subscription('default')->stripe_price ?? 'Pro') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pb-4 border-b border-white/10">
                                <span class="text-gray-400">Estado:</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-500/20 text-green-400 text-sm font-semibold">
                                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2 pulse-violet"></span>
                                    Activo
                                </span>
                            </div>
                            @if(auth()->user()->subscription('default')->ends_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400">Finaliza el:</span>
                                    <span class="text-white font-semibold">
                                        {{ auth()->user()->subscription('default')->ends_at->format('d/m/Y') }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Manage Subscription -->
                        <div class="mt-8 pt-8 border-t border-white/10">
                            <h3 class="text-lg font-semibold text-white mb-4">Administrar Suscripción</h3>
                            <div class="flex flex-col sm:flex-row gap-4">
                                @if(auth()->user()->subscription('default')->onGracePeriod())
                                    <form method="POST" action="{{ route('billing.resume') }}" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full button-primary px-6 py-3 rounded-xl font-semibold">
                                            Reanudar Suscripción
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('billing.cancel') }}" class="flex-1" onsubmit="return confirm('¿Estás seguro de que deseas cancelar tu suscripción?')">
                                        @csrf
                                        <button type="submit" class="w-full button-secondary px-6 py-3 rounded-xl font-semibold">
                                            Cancelar Suscripción
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-20 h-20 bg-violet-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <p class="text-gray-400 text-lg mb-6">No tienes una suscripción activa</p>
                            <p class="text-gray-500 mb-8">Elige un plan para comenzar a usar Virtual Employee</p>
                            <a href="{{ route('landing-new') }}#pricing" class="button-primary px-8 py-4 rounded-xl font-semibold text-white inline-block">
                                Ver Planes Disponibles
                                <svg class="inline-block w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    @endif
                @endauth
            </div>

            <!-- Payment Methods (if subscribed) -->
            @auth
                @if(auth()->user()->subscribed('default'))
                    <div class="card-dark rounded-2xl p-4 sm:p-8">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                            <svg class="w-8 h-8 text-violet-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Métodos de Pago
                        </h2>
                        <p class="text-gray-400 mb-6">Administra tus métodos de pago desde el portal de Stripe</p>
                        <a href="{{ route('billing.portal') }}" class="button-secondary px-6 py-3 rounded-xl font-semibold inline-block">
                            Ir al Portal de Facturación
                            <svg class="inline-block w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                @endif
            @endauth

            <!-- Back to home -->
            <div class="text-center mt-8">
                <a href="{{ route('landing-new') }}" class="text-violet-400 hover:text-violet-300 transition-colors duration-200 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al inicio
                </a>
            </div>
        </div>
    </section>

</body>
</html>
