<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JURUX - Vera, tu secretaria virtual que nunca descansa</title>
    <meta name="description" content="Vera es tu secretaria virtual impulsada por IA de JURUX. Gestiona emails, organiza reuniones, atiende llamadas y trabaja 24/7. El empleado perfecto que nunca descansa.">
    <meta name="keywords" content="secretaria virtual, asistente virtual IA, automatización emails, gestión calendario, virtual employee, asistente IA, productividad, vera">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo.png') }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="JURUX - Vera, tu secretaria virtual que nunca descansa">
    <meta property="og:description" content="Ahorra 15+ horas semanales con Vera, tu secretaria virtual impulsada por IA. Gestiona emails, organiza reuniones y atiende llamadas 24/7">
    <meta property="og:image" content="{{ asset('images/og/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:title" content="JURUX - Vera, tu secretaria virtual">
    <meta name="twitter:description" content="Tu secretaria virtual que trabaja 24/7. Ahorra 15+ horas semanales automatizando emails, reuniones y llamadas">
    <meta name="twitter:image" content="{{ asset('images/og/twitter-card.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <!-- Google Analytics -->
    @if(config('services.google_analytics.id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.google_analytics.id') }}');
    </script>
    @endif
</head>
<body class="antialiased overflow-x-hidden bg-[#0d0d0d] text-white">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-1 group">
                    <img src="{{ asset('images/logo.png') }}" alt="JURUX Logo" class="w-12 h-12 group-hover:scale-110 transition-transform">
                    <span class="text-lg font-semibold tracking-wide">JURUX</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#producto" class="text-sm text-gray-400 hover:text-white transition-colors">Producto</a>
                    <a href="#caracteristicas" class="text-sm text-gray-400 hover:text-white transition-colors">Características</a>
                    <a href="#precios" class="text-sm text-gray-400 hover:text-white transition-colors">Precios</a>
                    <a href="#faq" class="text-sm text-gray-400 hover:text-white transition-colors">FAQ</a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @guest
                        <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-white transition-colors hidden sm:block">
                            Iniciar sesión
                        </a>
                        <a href="{{ route('register') }}" class="btn-primary px-5 py-2 rounded-lg text-sm font-medium text-white">
                            Comenzar gratis
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="btn-primary px-5 py-2 rounded-lg text-sm font-medium text-white">
                            Ir al Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center pt-20 overflow-hidden">
        <!-- Background gradient -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-[#667eea]/5 to-transparent pointer-events-none"></div>

        <div class="relative z-10 max-w-6xl mx-auto px-6 lg:px-8 py-24 text-center">
            <!-- Badge -->
            <div class="inline-flex items-center px-4 py-1.5 rounded-full border border-white/10 bg-white/5 text-sm text-gray-300 mb-8 hover:border-white/20 transition-colors">
                <span class="w-2 h-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] rounded-full mr-2 animate-pulse"></span>
                Potenciado por IA avanzada
            </div>

            <!-- Main Heading -->
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-6 leading-[1.1] tracking-tight">
                Ahorra 15+ horas semanales con<br>
                <span class="gradient-text">tu secretaria virtual que nunca duerme</span>
            </h1>

            <p class="text-xl text-gray-400 max-w-3xl mx-auto mb-12 leading-relaxed">
                Vera gestiona tu correo, agenda reuniones y atiende llamadas <span class="text-white font-medium">24/7</span>.<br class="hidden sm:block">
                Mientras tú duermes, ella trabaja. <span class="text-white font-medium">Prueba gratis por 14 días, sin tarjeta.</span>
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
                @guest
                    <a href="{{ route('register') }}" class="btn-primary px-8 py-4 rounded-xl font-medium text-base text-white shadow-lg group">
                        Prueba gratis 14 días
                        <svg class="inline-block w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ url('/dashboard') }}" class="btn-primary px-8 py-4 rounded-xl font-medium text-base text-white shadow-lg">
                        Ir al Dashboard
                    </a>
                @endguest

                <a href="#producto" class="btn-secondary px-8 py-4 rounded-xl font-medium text-base text-white">
                    Ver demo
                    <svg class="inline-block w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </a>
            </div>

            <!-- Social Proof Mini -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 text-sm text-gray-400 mb-20">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Sin tarjeta de crédito</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Cancela cuando quieras</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span><strong class="text-white">500+</strong> profesionales activos</span>
                </div>
            </div>

            <!-- Trust badges -->
            <div class="mt-16">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-6">Confiado por empresas innovadoras</p>
                <div class="flex items-center justify-center gap-8 md:gap-12 opacity-30 flex-wrap">
                    <div class="text-lg font-semibold">OpenAI</div>
                    <div class="text-lg font-semibold">Vercel</div>
                    <div class="text-lg font-semibold">Scale</div>
                    <div class="text-lg font-semibold">Coinbase</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Producto Section -->
    <section id="producto" class="relative py-32 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="section-fade-in">
                    <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                        Presentamos a Vera
                    </div>
                    <h2 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                        Tu nueva empleada estrella<br>que jamás falla
                    </h2>
                    <p class="text-lg text-gray-400 mb-8 leading-relaxed">
                        Imagina tener una secretaria que responde emails a las 3 AM, nunca olvida una reunión
                        y atiende llamadas cuando estás ocupado. <span class="text-white font-medium">Eso es Vera.</span><br>
                        No es una herramienta más: es tu empleada perfecta que trabaja sin descanso para que
                        tú puedas enfocarte en crecer tu negocio.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <div>
                                <span class="text-white font-medium">Disponible 24/7</span>
                                <p class="text-gray-400 text-sm mt-1">Vera nunca duerme, nunca se enferma, siempre está lista para trabajar</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <div>
                                <span class="text-white font-medium">100% transparente</span>
                                <p class="text-gray-400 text-sm mt-1">Ve cada decisión que toma y mantén el control total</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <div>
                                <span class="text-white font-medium">Aprende de ti</span>
                                <p class="text-gray-400 text-sm mt-1">Se adapta a tu estilo y mejora con cada interacción</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="section-fade-in">
                    <!-- Screenshot de Vera con badge flotante -->
                    <div class="relative">
                        <img
                            src="{{ asset('images/screenshots/vera-dashboard.png') }}"
                            alt="Dashboard de Vera - Interfaz de la secretaria virtual"
                            class="rounded-2xl shadow-2xl border border-white/10 w-full"
                            loading="lazy"
                        >
                        <!-- Badge flotante -->
                        <div class="absolute -top-4 -right-4 bg-gradient-to-r from-[#667eea] to-[#764ba2] px-6 py-3 rounded-full text-white text-sm font-semibold shadow-2xl animate-float">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                Potenciado por IA
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Características Section -->
    <section id="caracteristicas" class="relative py-32 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-20 section-fade-in">
                <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                    Características
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Recupera tu tiempo.<br>Multiplica tu productividad.
                </h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                    Vera domina las tareas que te roban horas cada día. Automatización inteligente que realmente funciona.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/5 section-fade-in">
                    <div class="feature-icon w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-[#667eea]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Gestión de Emails</h3>
                    <p class="text-gray-400 leading-relaxed">
                        <span class="text-white">Zero inbox, every day.</span> Vera lee, categoriza y responde emails mientras duermes. Tu bandeja siempre organizada, sin esfuerzo.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/5 section-fade-in">
                    <div class="feature-icon w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-[#667eea]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Calendario Inteligente</h3>
                    <p class="text-gray-400 leading-relaxed">
                        <span class="text-white">Reuniones perfectas, siempre.</span> Vera agenda en tus mejores horas, evita solapamientos y protege tu tiempo de descanso. Automatización que respeta tu energía.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/5 section-fade-in">
                    <div class="feature-icon w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-[#667eea]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Gestión de Llamadas</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Vera atiende llamadas, mantiene conversaciones naturales y graba todo para que no pierdas información importante.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/5 section-fade-in">
                    <div class="feature-icon w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-[#667eea]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Organización de Archivos</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Encuentra cualquier documento al instante. Vera organiza y categoriza automáticamente todos tus archivos.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/5 section-fade-in">
                    <div class="feature-icon w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-[#667eea]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Notificaciones Inteligentes</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Recibe alertas por email o WhatsApp de cada acción importante. Mantente informado sin sobrecarga de información.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/5 section-fade-in">
                    <div class="feature-icon w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-[#667eea]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Chat con Vera</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Conversa con Vera directamente. Pídele información, archivos o que realice tareas específicas en tiempo real.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cómo funciona Section -->
    <section id="demo" class="relative py-32 border-t border-white/5">
        <div class="max-w-5xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-20 section-fade-in">
                <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                    Proceso simple
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Empieza en menos de 5 minutos
                </h2>
                <p class="text-xl text-gray-400">
                    Configura Vera y deja que empiece a trabajar por ti
                </p>
            </div>

            <div class="space-y-8">
                <!-- Step 1 -->
                <div class="flex items-start gap-6 section-fade-in">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-[#667eea] to-[#764ba2] flex items-center justify-center text-xl font-bold">
                        1
                    </div>
                    <div class="flex-1 pt-2">
                        <h3 class="text-2xl font-semibold mb-3">Conecta tus cuentas</h3>
                        <p class="text-gray-400 text-lg leading-relaxed">
                            Inicia sesión con Gmail o Outlook. Vera se conecta de forma segura con tus servicios existentes.
                        </p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="flex items-start gap-6 section-fade-in">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-[#667eea] to-[#764ba2] flex items-center justify-center text-xl font-bold">
                        2
                    </div>
                    <div class="flex-1 pt-2">
                        <h3 class="text-2xl font-semibold mb-3">Configura tus preferencias</h3>
                        <p class="text-gray-400 text-lg leading-relaxed">
                            Define tus horarios, prioridades y reglas. Vera aprenderá tu estilo de trabajo.
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex items-start gap-6 section-fade-in">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-[#667eea] to-[#764ba2] flex items-center justify-center text-xl font-bold">
                        3
                    </div>
                    <div class="flex-1 pt-2">
                        <h3 class="text-2xl font-semibold mb-3">Vera empieza a trabajar</h3>
                        <p class="text-gray-400 text-lg leading-relaxed">
                            Relájate mientras Vera gestiona tu correo, organiza tu calendario y atiende tus llamadas 24/7.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="precios" class="relative py-32 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-20 section-fade-in">
                <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                    Precios
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Planes simples y transparentes
                </h2>
                <p class="text-xl text-gray-400">
                    Elige el plan que mejor se adapte a tus necesidades
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Free Plan -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-2">Gratis</h3>
                    <div class="mb-6">
                        <span class="text-5xl font-bold">$0</span>
                        <span class="text-gray-400 text-lg">/mes</span>
                    </div>
                    <p class="text-gray-400 mb-8">Para probar Vera</p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">1 empleado virtual</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">50 emails/día</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Calendario básico</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Soporte por email</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn-secondary w-full block text-center py-3 rounded-xl font-medium text-white">
                        Empezar gratis
                    </a>
                </div>

                <!-- Pro Plan - Highlighted -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-[#667eea]/10 to-transparent border-2 border-[#667eea]/50 relative section-fade-in transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] rounded-full text-white text-sm font-medium">
                        Más popular
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Profesional</h3>
                    <div class="mb-6">
                        <span class="text-5xl font-bold">$29</span>
                        <span class="text-gray-400 text-lg">/mes</span>
                    </div>
                    <p class="text-gray-400 mb-8">Para profesionales exigentes</p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">3 empleados virtuales</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Emails ilimitados</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Gestión de llamadas</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Integraciones avanzadas</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Soporte prioritario</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn-primary w-full block text-center py-3 rounded-xl font-medium text-white">
                        Comenzar prueba gratis
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-2">Empresa</h3>
                    <div class="mb-6">
                        <span class="text-5xl font-bold">Custom</span>
                    </div>
                    <p class="text-gray-400 mb-8">Para equipos y empresas</p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Empleados ilimitados</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Todo ilimitado</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Integraciones personalizadas</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">Gerente de cuenta dedicado</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-[#667eea] mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-300">SLA garantizado</span>
                        </li>
                    </ul>
                    <a href="mailto:sales@virtualemployee.com" class="btn-secondary w-full block text-center py-3 rounded-xl font-medium text-white">
                        Contactar ventas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="relative py-32 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-20 section-fade-in">
                <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                    Testimonios
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Lo que dicen nuestros usuarios
                </h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                    Profesionales de todo el mundo ya confían en Vera para optimizar su tiempo
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonio 1 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <div class="flex items-center mb-6">
                        <img src="{{ asset('images/testimonials/user1.png') }}" alt="María García" class="w-14 h-14 rounded-full mr-4 border-2 border-[#667eea]/50" loading="lazy">
                        <div>
                            <p class="font-semibold text-white">María García</p>
                            <p class="text-sm text-gray-400">CEO, TechStartup</p>
                        </div>
                    </div>
                    <div class="flex mb-4">
                        <span class="text-yellow-400">★★★★★</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        "Vera me ahorra <span class="text-white font-medium">3 horas diarias</span> gestionando mi inbox. Ahora puedo enfocarme en hacer crecer mi negocio en lugar de responder emails."
                    </p>
                </div>

                <!-- Testimonio 2 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <div class="flex items-center mb-6">
                        <img src="{{ asset('images/testimonials/user2.png') }}" alt="Carlos Rodríguez" class="w-14 h-14 rounded-full mr-4 border-2 border-[#667eea]/50" loading="lazy">
                        <div>
                            <p class="font-semibold text-white">Carlos Rodríguez</p>
                            <p class="text-sm text-gray-400">Abogado Senior</p>
                        </div>
                    </div>
                    <div class="flex mb-4">
                        <span class="text-yellow-400">★★★★★</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        "La gestión de agenda es <span class="text-white font-medium">impecable</span>. Vera coordina reuniones con mis clientes automáticamente y nunca se cruzan mis citas."
                    </p>
                </div>

                <!-- Testimonio 3 -->
                <div class="card-hover p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <div class="flex items-center mb-6">
                        <img src="{{ asset('images/testimonials/user3.png') }}" alt="Ana Martínez" class="w-14 h-14 rounded-full mr-4 border-2 border-[#667eea]/50" loading="lazy">
                        <div>
                            <p class="font-semibold text-white">Ana Martínez</p>
                            <p class="text-sm text-gray-400">Freelance Designer</p>
                        </div>
                    </div>
                    <div class="flex mb-4">
                        <span class="text-yellow-400">★★★★★</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        "Como freelancer, <span class="text-white font-medium">no puedo permitirme una secretaria</span>. Vera me da ese soporte profesional a una fracción del costo."
                    </p>
                </div>
            </div>

            <!-- Social Proof Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 section-fade-in">
                <div class="text-center">
                    <div class="text-4xl font-bold gradient-text mb-2">500+</div>
                    <div class="text-gray-400 text-sm">Profesionales activos</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold gradient-text mb-2">15hrs</div>
                    <div class="text-gray-400 text-sm">Ahorradas por semana</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold gradient-text mb-2">98%</div>
                    <div class="text-gray-400 text-sm">Satisfacción</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold gradient-text mb-2">24/7</div>
                    <div class="text-gray-400 text-sm">Disponibilidad</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="relative py-32 border-t border-white/5">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-20 section-fade-in">
                <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                    FAQ
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Preguntas frecuentes
                </h2>
            </div>

            <div class="space-y-6">
                <div class="p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-3">¿Qué es Vera exactamente?</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Vera es una secretaria virtual impulsada por inteligencia artificial que gestiona tu correo electrónico,
                        organiza tu calendario y atiende tus llamadas. Trabaja 24/7 aprendiendo de tus preferencias para hacerte
                        la vida más fácil mientras tú mantienes el control total.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-3">¿Cómo garantizan la seguridad de mis datos?</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Utilizamos encriptación de extremo a extremo para todos tus datos. Nos integramos mediante OAuth 2.0
                        (nunca guardamos contraseñas) y cumplimos con GDPR. Tus datos son tuyos y puedes exportarlos o
                        eliminarlos en cualquier momento.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-3">¿Puedo cancelar en cualquier momento?</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Sí, absolutamente. No hay contratos de permanencia ni penalizaciones. Cancela tu suscripción en cualquier
                        momento desde tu dashboard y mantendrás acceso hasta el final de tu período de facturación actual.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-3">¿Vera realmente aprende de mí?</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Sí. Vera utiliza machine learning para entender tus patrones de trabajo, preferencias de comunicación
                        y prioridades. Cuanto más la uses, mejor será respondiendo emails, organizando reuniones y gestionando
                        tu agenda de la forma exacta que tú lo harías.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-3">¿Qué servicios puedo integrar?</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Actualmente soportamos Gmail, Outlook, Google Calendar, Outlook Calendar, WhatsApp Business y Telegram.
                        Estamos constantemente añadiendo nuevas integraciones. Si necesitas algo específico, contáctanos.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-gradient-to-b from-white/5 to-transparent border border-white/10 section-fade-in">
                    <h3 class="text-xl font-semibold mb-3">¿Hay límite de uso en el plan Profesional?</h3>
                    <p class="text-gray-400 leading-relaxed">
                        En el plan Profesional tienes emails ilimitados y todas las funciones avanzadas. Los únicos límites son
                        el número de empleados virtuales (3 en Pro) y algunas integraciones específicas que están disponibles
                        solo en el plan Empresa.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="relative py-24 border-t border-white/5">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="gradient-border p-12 rounded-3xl text-center section-fade-in">
                <div class="inline-block px-3 py-1 rounded-full bg-[#667eea]/10 text-[#667eea] text-sm font-medium mb-6">
                    Newsletter
                </div>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Mantente al día con Vera
                </h2>
                <p class="text-lg text-gray-400 mb-8 max-w-xl mx-auto">
                    Recibe tips, actualizaciones y casos de éxito de usuarios que están recuperando su tiempo con IA
                </p>
                <form id="newsletter-form" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                    <input
                        type="email"
                        id="newsletter-email"
                        placeholder="tu@email.com"
                        required
                        class="flex-1 px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-[#667eea] focus:ring-2 focus:ring-[#667eea]/20 transition-all"
                    >
                    <button
                        type="submit"
                        class="btn-primary px-6 py-3 rounded-xl font-medium whitespace-nowrap"
                    >
                        Suscribirme
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-4">
                    Sin spam. Cancela cuando quieras.
                </p>
            </div>
        </div>
    </section>

    <!-- CTA Final Section -->
    <section class="relative py-32">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 text-center">
            <div class="section-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    ¿Listo para recuperar tu tiempo?
                </h2>
                <p class="text-xl text-gray-400 mb-12 max-w-2xl mx-auto">
                    Únete a cientos de profesionales que ya confían en Vera. <span class="text-white">Empieza gratis, sin tarjeta de crédito.</span>
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    @guest
                        <a href="{{ route('register') }}" class="btn-primary px-8 py-4 rounded-xl font-medium text-base text-white shadow-lg">
                            Comenzar gratis
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="btn-primary px-8 py-4 rounded-xl font-medium text-base text-white shadow-lg">
                            Ir al Dashboard
                        </a>
                    @endguest

                    <a href="mailto:sales@virtualemployee.com" class="btn-secondary px-8 py-4 rounded-xl font-medium text-base text-white">
                        Hablar con ventas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative py-16 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-12">
                <div class="col-span-2">
                    <a href="/" class="flex items-center gap-1 mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="JURUX Logo" class="w-11 h-11" loading="lazy">
                        <span class="text-lg font-semibold tracking-wide">JURUX</span>
                    </a>
                    <p class="text-gray-400 text-sm max-w-xs">
                        La plataforma de empleados virtuales que trabaja 24/7 para hacer tu vida más fácil.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold mb-4 text-sm">Producto</h4>
                    <ul class="space-y-2">
                        <li><a href="#caracteristicas" class="text-gray-400 text-sm hover:text-white transition-colors">Características</a></li>
                        <li><a href="#precios" class="text-gray-400 text-sm hover:text-white transition-colors">Precios</a></li>
                        <li><a href="#faq" class="text-gray-400 text-sm hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4 text-sm">Empresa</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 text-sm hover:text-white transition-colors">Sobre nosotros</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-white transition-colors">Carreras</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4 text-sm">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('privacy') }}" class="text-gray-400 text-sm hover:text-white transition-colors">Privacidad</a></li>
                        <li><a href="{{ route('terms') }}" class="text-gray-400 text-sm hover:text-white transition-colors">Términos</a></li>
                        <li><a href="mailto:support@jurux.com" class="text-gray-400 text-sm hover:text-white transition-colors">Contacto</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500 mb-4 md:mb-0">
                    © {{ date('Y') }} JURUX. Todos los derechos reservados.
                </p>
                <div class="flex items-center space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.220-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll animations & Newsletter script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll animations
            const fadeElements = document.querySelectorAll('.section-fade-in');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1
            });

            fadeElements.forEach(element => {
                observer.observe(element);
            });

            // Newsletter form handling
            const newsletterForm = document.getElementById('newsletter-form');
            const newsletterEmail = document.getElementById('newsletter-email');

            if (newsletterForm) {
                newsletterForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const email = newsletterEmail.value;
                    const submitBtn = newsletterForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;

                    // Disable button and show loading state
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Suscribiendo...';

                    try {
                        // Send subscription request to backend
                        const response = await fetch('{{ route('newsletter.subscribe') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: email })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Show success message
                            submitBtn.textContent = '¡Suscrito! ✓';
                            submitBtn.classList.remove('btn-primary');
                            submitBtn.classList.add('bg-green-600');

                            // Track event in Google Analytics
                            if (typeof gtag !== 'undefined') {
                                gtag('event', 'newsletter_signup', {
                                    'event_category': 'engagement',
                                    'event_label': 'Newsletter Subscription'
                                });
                            }

                            // Reset form
                            newsletterEmail.value = '';

                            // Reset button after 3 seconds
                            setTimeout(() => {
                                submitBtn.textContent = originalText;
                                submitBtn.classList.remove('bg-green-600');
                                submitBtn.classList.add('btn-primary');
                                submitBtn.disabled = false;
                            }, 3000);
                        } else {
                            // Show error message from server
                            submitBtn.textContent = data.message || 'Error. Intenta de nuevo';
                            submitBtn.disabled = false;

                            setTimeout(() => {
                                submitBtn.textContent = originalText;
                            }, 3000);
                        }

                    } catch (error) {
                        console.error('Newsletter subscription error:', error);
                        submitBtn.textContent = 'Error. Intenta de nuevo';
                        submitBtn.disabled = false;

                        setTimeout(() => {
                            submitBtn.textContent = originalText;
                        }, 3000);
                    }
                });
            }

            // Track CTA clicks
            document.querySelectorAll('a[href*="register"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'click', {
                            'event_category': 'CTA',
                            'event_label': 'Sign Up Button'
                        });
                    }
                });
            });

            // Track scroll depth
            let maxScroll = 0;
            window.addEventListener('scroll', function() {
                const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

                if (scrollPercent > maxScroll) {
                    maxScroll = Math.floor(scrollPercent / 25) * 25; // Track in 25% increments

                    if (typeof gtag !== 'undefined' && maxScroll > 0) {
                        gtag('event', 'scroll', {
                            'event_category': 'engagement',
                            'event_label': `${maxScroll}% Scroll Depth`
                        });
                    }
                }
            });
        });
    </script>

    <!-- Exit Intent Popup -->
    <div id="exit-intent-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[60] flex items-center justify-center p-4" style="display: none;">
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0d0d0d] border border-white/10 rounded-2xl max-w-lg w-full p-8 relative shadow-2xl">
            <!-- Close button -->
            <button onclick="closeExitModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Content -->
            <div class="text-center">
                <div class="mb-6">
                    <span class="text-6xl">👋</span>
                </div>
                <h2 class="text-3xl font-bold mb-4 gradient-text">¡Espera un momento!</h2>
                <p class="text-xl text-gray-300 mb-6">
                    Antes de irte, prueba <span class="text-white font-semibold">Vera gratis por 14 días</span>
                </p>
                <ul class="text-left space-y-3 mb-8 max-w-sm mx-auto">
                    <li class="flex items-center gap-3 text-gray-400">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Sin tarjeta de crédito requerida</span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-400">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Cancela cuando quieras</span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-400">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Ahorra 15+ horas semanales</span>
                    </li>
                </ul>

                <div class="flex flex-col sm:flex-row gap-3">
                    @guest
                        <a href="{{ route('register') }}" class="btn-primary px-6 py-3 rounded-xl font-medium text-white flex-1">
                            Comenzar ahora gratis
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="btn-primary px-6 py-3 rounded-xl font-medium text-white flex-1">
                            Ir al Dashboard
                        </a>
                    @endguest
                    <button onclick="closeExitModal()" class="btn-secondary px-6 py-3 rounded-xl font-medium text-white">
                        No, gracias
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Exit Intent Script -->
    <script>
        let exitIntentShown = false;

        // Detectar cuando el mouse sale del viewport hacia arriba
        document.addEventListener('mouseleave', function(e) {
            // Solo detectar si el cursor sale por arriba (intentando cerrar pestaña o ir a barra de direcciones)
            if (e.clientY < 0 && !exitIntentShown) {
                // No mostrar si el usuario ya está registrado
                @guest
                    exitIntentShown = true;
                    showExitModal();
                @endguest
            }
        });

        // Detectar en móviles cuando intentan ir hacia atrás
        let lastTouchY = 0;
        document.addEventListener('touchstart', function(e) {
            lastTouchY = e.touches[0].clientY;
        });

        document.addEventListener('touchmove', function(e) {
            let touchY = e.touches[0].clientY;
            let deltaY = touchY - lastTouchY;

            // Si scroll hacia abajo en la parte superior de la página (intent de cerrar)
            if (deltaY > 100 && window.pageYOffset === 0 && !exitIntentShown) {
                @guest
                    exitIntentShown = true;
                    showExitModal();
                @endguest
            }
        });

        function showExitModal() {
            document.getElementById('exit-intent-modal').style.display = 'flex';
            // Prevenir scroll del body
            document.body.style.overflow = 'hidden';

            // Google Analytics event (si está configurado)
            if (typeof gtag === 'function') {
                gtag('event', 'exit_intent_shown', {
                    'event_category': 'engagement',
                    'event_label': 'Exit Intent Modal Displayed'
                });
            }
        }

        function closeExitModal() {
            document.getElementById('exit-intent-modal').style.display = 'none';
            // Restaurar scroll
            document.body.style.overflow = 'auto';

            // Google Analytics event
            if (typeof gtag === 'function') {
                gtag('event', 'exit_intent_closed', {
                    'event_category': 'engagement',
                    'event_label': 'Exit Intent Modal Closed'
                });
            }
        }

        // Cerrar al hacer clic fuera del modal
        document.getElementById('exit-intent-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExitModal();
            }
        });

        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeExitModal();
            }
        });
    </script>

    <!-- Cookie Consent Banner (GDPR) -->
    <div id="cookie-consent" class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:max-w-md bg-gray-900 border border-white/10 rounded-xl p-6 shadow-2xl z-50" style="display: none;">
        <div class="flex items-start gap-3 mb-4">
            <svg class="w-6 h-6 text-[#667eea] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <div>
                <h3 class="text-white font-semibold mb-2">🍪 Cookies</h3>
                <p class="text-sm text-gray-300 leading-relaxed">
                    Usamos cookies para mejorar tu experiencia y analizar el uso del sitio. Al continuar navegando, aceptas nuestra
                    <a href="{{ route('privacy') }}" class="text-[#667eea] hover:underline">Política de Privacidad</a>.
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="acceptCookies()" class="btn-primary px-4 py-2 rounded-lg text-sm flex-1 font-medium">
                Aceptar todas
            </button>
            <button onclick="rejectCookies()" class="btn-secondary px-4 py-2 rounded-lg text-sm font-medium">
                Solo esenciales
            </button>
        </div>
    </div>

    <!-- Cookie Consent Script -->
    <script>
        function acceptCookies() {
            localStorage.setItem('cookies-accepted', 'true');
            document.getElementById('cookie-consent').style.display = 'none';

            // Habilitar Google Analytics si está configurado
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
            }
        }

        function rejectCookies() {
            localStorage.setItem('cookies-accepted', 'essential');
            document.getElementById('cookie-consent').style.display = 'none';

            // Deshabilitar Google Analytics
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': 'denied'
                });
            }
        }

        // Mostrar banner si no ha respondido
        document.addEventListener('DOMContentLoaded', function() {
            if (!localStorage.getItem('cookies-accepted')) {
                // Mostrar después de 1 segundo para mejor UX
                setTimeout(function() {
                    document.getElementById('cookie-consent').style.display = 'block';
                }, 1000);
            }
        });
    </script>

    <!-- Tawk.to Live Chat -->
    <!--
    📌 INSTRUCCIONES PARA ACTIVAR TAWK.TO:

    1. Ve a https://www.tawk.to y crea una cuenta gratis
    2. En tu dashboard, ve a Administration → Property Settings
    3. Copia tu Property ID (ejemplo: 6123abc456def789)
    4. Reemplaza 'TU_TAWK_ID_AQUI' con tu ID real en el código abajo
    5. Descomenta el bloque de código (quita los <!-- y -->)
    6. ¡Listo! El chat aparecerá en la esquina inferior derecha
    -->
    <!--
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/TU_TAWK_ID_AQUI/default';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
    -->
    <!-- Fin Tawk.to -->

</body>
</html>
