<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dev Panel · Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-[#fafafa] font-['Inter'] text-gray-900">
<div class="flex min-h-screen">

    @include('components.dashboard-sidebar')

    <main class="flex-1 md:ml-56">

        {{-- TOP BAR --}}
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6">
            <div class="flex items-center gap-3">
                <h1 class="text-sm font-semibold text-gray-900">Dev Panel</h1>
                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold">DEV MODE</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500">{{ auth()->user()->email }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                    {{ auth()->user()->plan_type === 'pro' ? 'bg-purple-100 text-purple-700' :
                       (auth()->user()->plan_type === 'team' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') }}">
                    {{ ucfirst(auth()->user()->plan_type ?? 'basic') }}
                </span>
            </div>
        </header>

        <div class="p-6 max-w-5xl mx-auto space-y-6">

            {{-- NOTIFICACIÓN --}}
            @if(session('dev_success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm font-medium">
                    ✓ {{ session('dev_success') }}
                </div>
            @endif

            {{-- PLAN SWITCHER --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-1">Cambiar plan</h2>
                <p class="text-xs text-gray-500 mb-4">Sin Stripe. Cambio inmediato para testear comportamiento según plan.</p>
                <form method="POST" action="{{ route('dev.switch-plan') }}" class="flex flex-wrap gap-3">
                    @csrf
                    @foreach([
                        ['value' => 'basic', 'label' => 'Vera Básica', 'sub' => '30 reuniones/mes', 'color' => 'blue'],
                        ['value' => 'pro',   'label' => 'Vera Pro',    'sub' => 'Reuniones ilimitadas', 'color' => 'purple'],
                        ['value' => 'team',  'label' => 'Vera Equipo', 'sub' => 'Multi-agenda', 'color' => 'emerald'],
                    ] as $plan)
                        <button type="submit" name="plan" value="{{ $plan['value'] }}"
                            class="flex-1 min-w-[140px] text-left px-4 py-3 rounded-xl border-2 transition-all
                                {{ auth()->user()->plan_type === $plan['value']
                                    ? 'border-' . $plan['color'] . '-500 bg-' . $plan['color'] . '-50'
                                    : 'border-gray-200 hover:border-gray-300 bg-white' }}">
                            <p class="text-sm font-semibold text-gray-900">{{ $plan['label'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $plan['sub'] }}</p>
                            @if(auth()->user()->plan_type === $plan['value'])
                                <span class="text-xs font-medium text-{{ $plan['color'] }}-600 mt-1 block">← activo</span>
                            @endif
                        </button>
                    @endforeach
                </form>
            </div>

            {{-- HERRAMIENTAS --}}
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Herramientas</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                    {{-- Token Analytics --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-7 h-7 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Token Analytics</span>
                            </div>
                            <p class="text-xs text-gray-500">Consumo de IA, costes reales y calculadora de pricing por plan.</p>
                        </div>
                        <a href="{{ route('dev.tokens') }}"
                           class="mt-auto flex items-center justify-center gap-1.5 px-3 py-2 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Abrir
                        </a>
                    </div>

                    {{-- Inspector Vera Config --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3" x-data="{ open: false }">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Inspector Vera Config</span>
                            </div>
                            <p class="text-xs text-gray-500">Ver el JSON completo del Cerebro de Vera del usuario actual.</p>
                        </div>
                        <button @click="open = !open"
                            class="mt-auto flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span x-text="open ? 'Cerrar' : 'Inspeccionar'"></span>
                        </button>
                        <div x-show="open" x-transition class="mt-1">
                            <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs text-gray-700 overflow-auto max-h-64 whitespace-pre-wrap">{{ $config ? json_encode($config->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Sin configuración' }}</pre>
                        </div>
                    </div>

                    {{-- Simular mensaje entrante --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3" x-data="{ open: false, loading: false, result: null }">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-7 h-7 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Simular mensaje</span>
                            </div>
                            <p class="text-xs text-gray-500">Lanza un Job de Vera con un mensaje custom sin necesitar WhatsApp real.</p>
                        </div>
                        <button @click="open = !open"
                            class="mt-auto flex items-center justify-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Simular
                        </button>
                        <div x-show="open" x-transition class="mt-1 space-y-2">
                            <form method="POST" action="{{ route('dev.simulate-message') }}">
                                @csrf
                                <div class="space-y-2">
                                    <select name="channel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs text-gray-900 bg-white focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none">
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="telegram">Telegram</option>
                                        <option value="email">Email</option>
                                    </select>
                                    <input type="text" name="contact" placeholder="Nombre del contacto" value="Cliente Test"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs text-gray-900 bg-white focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none">
                                    <textarea name="message" rows="2" placeholder="Hola, quiero una reunión el martes a las 10"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs text-gray-900 bg-white focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none resize-none">Hola, quiero una reunión el martes a las 10</textarea>
                                    <button type="submit" class="w-full py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700">
                                        Enviar al Job →
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Reset Onboarding --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Reset Onboarding</span>
                            </div>
                            <p class="text-xs text-gray-500">Borra la config de Vera para volver a ver el wizard desde cero.</p>
                        </div>
                        <form method="POST" action="{{ route('dev.reset-onboarding') }}" class="mt-auto"
                              onsubmit="return confirm('¿Borrar la config de Vera y resetear el onboarding?')">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-1.5 px-3 py-2 bg-amber-500 text-white text-xs font-medium rounded-lg hover:bg-amber-600 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Resetear
                            </button>
                        </form>
                    </div>

                    {{-- Horizon --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-7 h-7 bg-red-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Horizon (Colas)</span>
                            </div>
                            <p class="text-xs text-gray-500">Monitor de jobs Redis. Ver pendientes, fallidos y throughput.</p>
                        </div>
                        <a href="/horizon" target="_blank"
                           class="mt-auto flex items-center justify-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Abrir Horizon
                        </a>
                    </div>

                    {{-- Actividad Vera --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Actividad Vera</span>
                            </div>
                            <p class="text-xs text-gray-500">Log de acciones de Vera: mensajes procesados, escalados, reuniones.</p>
                        </div>
                        <a href="{{ route('vera.activity.index') }}"
                           class="mt-auto flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Ver actividad
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const s = document.getElementById('sidebar');
    if (s) s.classList.toggle('hidden');
}
</script>
</body>
</html>
