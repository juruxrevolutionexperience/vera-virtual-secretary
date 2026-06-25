<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css'])
    <!-- Alpine.js para Command Palette -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>
    </div>
    <!-- Dev Toolbar (solo visible para usuarios is_dev) -->
    @include('components.dev-toolbar')

    <!-- Modal de aceptación de Términos (bloquea la app hasta aceptar) -->
    @auth
        @php
            $termsAccepted = \App\Models\UserConsent::where('user_id', auth()->id())
                ->where('consent_type', 'terms_of_service')
                ->where('consent_version', \App\Http\Middleware\EnsureTermsAccepted::CURRENT_VERSION)
                ->where('is_granted', true)
                ->exists();
        @endphp
        @if(!$termsAccepted)
        <div
            id="terms-modal"
            style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;padding:1rem"
        >
            <div style="background:#fff;border-radius:1rem;width:100%;max-width:560px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3)">

                <div style="background:#7c3aed;padding:1.5rem 2rem">
                    <h2 style="color:#fff;font-size:1.125rem;font-weight:600;margin:0">Antes de empezar</h2>
                    <p style="color:#ddd6fe;font-size:0.875rem;margin:0.25rem 0 0">Lee y acepta los Términos y Condiciones para usar Vera</p>
                </div>

                <div style="padding:1.5rem 2rem;max-height:300px;overflow-y:auto;border-bottom:1px solid #e5e7eb;font-size:0.875rem;color:#4b5563;line-height:1.6">
                    <p><strong style="color:#111827">1. Qué es Vera</strong><br>Vera es una herramienta de gestión administrativa que automatiza la comunicación con tus clientes. No es un servicio sanitario ni médico.</p>
                    <p style="margin-top:1rem"><strong style="color:#111827">2. Lo que Vera no hace</strong><br>Vera no da consejos médicos ni clínicos. Si un cliente pregunta algo de ese tipo, Vera le redirige al profesional. La responsabilidad clínica es tuya.</p>
                    <p style="margin-top:1rem"><strong style="color:#111827">3. Tu responsabilidad sobre el contenido</strong><br>Eres responsable de la exactitud de la información que cargas en Vera: precios, horarios, servicios. Si Vera comunica algo incorrecto porque tú lo introdujiste mal, la responsabilidad es tuya.</p>
                    <p style="margin-top:1rem"><strong style="color:#111827">4. Disponibilidad</strong><br>Vera procura mantenerse disponible de forma continua, pero no garantiza disponibilidad ininterrumpida. No somos responsables de caídas de terceros (WhatsApp, Telegram, Google).</p>
                    <p style="margin-top:1rem"><strong style="color:#111827">5. Limitación de responsabilidad</strong><br>Nuestra responsabilidad máxima se limita a lo que hayas pagado en los últimos 3 meses. No respondemos por daños indirectos ni pérdida de negocio.</p>
                    <p style="margin-top:1rem"><strong style="color:#111827">6. Protección de datos</strong><br>Tratamos los datos de tus clientes como encargados del tratamiento, conforme al RGPD y la LOPDGDD. Tú eres el responsable del tratamiento.</p>
                    <p style="margin-top:1rem"><strong style="color:#111827">7. Modificaciones</strong><br>Podemos actualizar estos Términos con 15 días de preaviso. Si los cambios son significativos, te pediremos que los aceptes de nuevo.</p>
                    <p style="margin-top:1rem;font-size:0.75rem;color:#9ca3af">Versión {{ \App\Http\Middleware\EnsureTermsAccepted::CURRENT_VERSION }} · Jurux</p>
                </div>

                <form method="POST" action="{{ route('terms.accept.store') }}" style="padding:1.5rem 2rem">
                    @csrf
                    <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer">
                        <input
                            type="checkbox"
                            name="accept_terms"
                            value="1"
                            id="terms-check"
                            style="margin-top:0.125rem;width:1rem;height:1rem;accent-color:#7c3aed;flex-shrink:0"
                        >
                        <span style="font-size:0.875rem;color:#374151">
                            He leído y acepto los <strong>Términos y Condiciones de uso de Vera</strong>
                        </span>
                    </label>
                    <button
                        type="submit"
                        style="margin-top:1.25rem;width:100%;background:#7c3aed;color:#fff;font-weight:600;padding:0.75rem 1.5rem;border-radius:0.75rem;border:none;cursor:pointer;font-size:0.9375rem"
                        onmouseover="this.style.background='#6d28d9'"
                        onmouseout="this.style.background='#7c3aed'"
                    >
                        Aceptar y continuar
                    </button>
                    <p style="text-align:center;font-size:0.75rem;color:#9ca3af;margin-top:0.75rem">
                        Se registrará tu aceptación con fecha, hora e IP conforme al RGPD.
                    </p>
                </form>

            </div>
        </div>
        @endif
    @endauth

    <!-- Scripts adicionales -->
    @stack('scripts')
</body>

</html>