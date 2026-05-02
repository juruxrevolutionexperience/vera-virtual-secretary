<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Elige tu plan — Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-[#fafafa] text-gray-900" style="font-family: 'Inter', sans-serif;">

<div class="min-h-screen flex flex-col">

    <!-- Header simple -->
    <header class="h-14 bg-white border-b border-gray-200 flex items-center px-6">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-md flex items-center justify-center">
                <span class="text-white font-bold text-sm">V</span>
            </div>
            <span class="text-gray-900 font-semibold text-sm">Vera</span>
        </div>
        <div class="ml-auto flex items-center gap-4 text-sm text-gray-500">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-gray-600 transition-colors">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <!-- Content -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-12">

        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Elige tu plan</h1>
            <p class="text-gray-500">Empieza hoy. Cancela cuando quieras.</p>
        </div>

        <!-- Plans grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-4xl">

            <!-- Essentials -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col">
                <div class="mb-6">
                    <p class="text-xs font-semibold text-purple-600 uppercase tracking-wide mb-2">Essentials</p>
                    <div class="flex items-end gap-1 mb-3">
                        <span class="text-4xl font-bold text-gray-900">29€</span>
                        <span class="text-gray-400 text-sm mb-1">/mes</span>
                    </div>
                    <p class="text-sm text-gray-500">Para profesionales independientes que empiezan a automatizar.</p>
                </div>
                <ul class="space-y-2 mb-8 flex-1 text-sm text-gray-600">
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> 500 mensajes/mes</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> 1 canal conectado</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Modo supervisado</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> 1 usuario</li>
                </ul>
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="essentials">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-900 font-semibold text-sm transition-colors">
                        Empezar con Essentials
                    </button>
                </form>
            </div>

            <!-- Professional (destacado) -->
            <div class="bg-white rounded-2xl border-2 border-purple-500 p-6 flex flex-col relative shadow-lg">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="bg-purple-600 text-white text-xs font-semibold px-3 py-1 rounded-full">Más popular</span>
                </div>
                <div class="mb-6">
                    <p class="text-xs font-semibold text-purple-600 uppercase tracking-wide mb-2">Professional</p>
                    <div class="flex items-end gap-1 mb-3">
                        <span class="text-4xl font-bold text-gray-900">59€</span>
                        <span class="text-gray-400 text-sm mb-1">/mes</span>
                    </div>
                    <p class="text-sm text-gray-500">Para negocios que quieren Vera funcionando de forma autónoma.</p>
                </div>
                <ul class="space-y-2 mb-8 flex-1 text-sm text-gray-600">
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> 2.000 mensajes/mes</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> 3 canales conectados</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Modo autónomo</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Hasta 3 usuarios</li>
                </ul>
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="professional">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm transition-colors">
                        Empezar con Professional
                    </button>
                </form>
            </div>

            <!-- Business -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col">
                <div class="mb-6">
                    <p class="text-xs font-semibold text-purple-600 uppercase tracking-wide mb-2">Business</p>
                    <div class="flex items-end gap-1 mb-3">
                        <span class="text-4xl font-bold text-gray-900">149€</span>
                        <span class="text-gray-400 text-sm mb-1">/mes</span>
                    </div>
                    <p class="text-sm text-gray-500">Para equipos que necesitan escala y sin límites.</p>
                </div>
                <ul class="space-y-2 mb-8 flex-1 text-sm text-gray-600">
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> 10.000 mensajes/mes</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Canales ilimitados</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Modo autónomo</li>
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Usuarios ilimitados</li>
                </ul>
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="business">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-900 font-semibold text-sm transition-colors">
                        Empezar con Business
                    </button>
                </form>
            </div>

        </div>

        <p class="text-xs text-gray-400 mt-8">Pago seguro con Stripe · Cancela cuando quieras · Sin permanencia</p>

    </main>
</div>

</body>
</html>
