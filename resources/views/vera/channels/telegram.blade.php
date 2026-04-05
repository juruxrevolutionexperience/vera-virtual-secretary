<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conectar Telegram — Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .step-connector { width: 2px; background: #e5e7eb; margin-left: 19px; }
        .step-connector.done { background: #7c3aed; }
        .code-block { font-family: 'Courier New', monospace; }
    </style>
</head>
<body class="antialiased bg-[#fafafa] text-gray-900">

<div class="flex min-h-screen">
    @include('components.dashboard-sidebar')

    <main class="flex-1 ml-56 p-8 max-w-3xl">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
            <a href="{{ route('vera.dashboard') }}" class="hover:text-gray-600">Panel Vera</a>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-600 font-medium">Conectar Telegram</span>
        </div>

        {{-- Header --}}
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Conectar Telegram</h1>
                <p class="text-sm text-gray-500">Vera responderá mensajes de Telegram automáticamente</p>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- ESTADO: YA CONECTADO                                             --}}
        {{-- ================================================================ --}}
        @if($config->telegram_bot_token)
        <div
            x-data="{
                disconnecting: false,
                async disconnect() {
                    if (!confirm('¿Desconectar Telegram? Vera dejará de responder mensajes de este bot.')) return;
                    this.disconnecting = true;
                    const res = await fetch('{{ route('vera.channels.telegram.disconnect') }}', {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    if ((await res.json()).success) window.location.reload();
                    this.disconnecting = false;
                }
            }"
        >
            {{-- Banner conectado --}}
            <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-800">Telegram conectado</p>
                        <p class="text-xs text-green-600">Vera está lista para responder mensajes de Telegram</p>
                    </div>
                </div>
                <button @click="disconnect()" :disabled="disconnecting"
                    class="text-xs text-red-600 hover:text-red-800 font-medium border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50">
                    <span x-show="!disconnecting">Desconectar</span>
                    <span x-show="disconnecting">Desconectando...</span>
                </button>
            </div>

            {{-- Info del webhook --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Estado del webhook</h3>

                @if($webhookInfo && !empty($webhookInfo['url']))
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="text-sm text-green-700 font-medium">Webhook activo</span>
                        @if(isset($webhookInfo['pending_update_count']) && $webhookInfo['pending_update_count'] > 0)
                        <span class="ml-2 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                            {{ $webhookInfo['pending_update_count'] }} mensaje(s) pendiente(s)
                        </span>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 mb-1">URL del webhook registrada</p>
                        <p class="text-xs code-block text-gray-800 break-all">{{ $webhookInfo['url'] }}</p>
                    </div>
                    @if(!empty($webhookInfo['last_error_message']))
                    <div class="mt-3 p-3 bg-red-50 rounded-lg">
                        <p class="text-xs text-red-600"><strong>Último error:</strong> {{ $webhookInfo['last_error_message'] }}</p>
                    </div>
                    @endif
                @else
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                        <span class="text-sm text-amber-700 font-medium">Webhook no registrado</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">El token está guardado pero el webhook no está activo. Regístralo para que Vera empiece a recibir mensajes.</p>

                    <div
                        x-data="{
                            loading: false, done: false, error: '',
                            webhookUrl: '{{ $webhookUrl }}',
                            async register() {
                                this.loading = true; this.error = '';
                                const res = await fetch('{{ route('vera.channels.telegram.register-webhook') }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                });
                                const data = await res.json();
                                if (data.success) { this.done = true; setTimeout(() => window.location.reload(), 1500); }
                                else { this.error = data.message; }
                                this.loading = false;
                            }
                        }"
                    >
                        <button @click="register()" :disabled="loading || done"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                            <svg x-show="!loading && !done" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-show="!loading && !done">Registrar webhook</span>
                            <span x-show="loading">Registrando...</span>
                            <span x-show="done">Registrado!</span>
                        </button>
                        <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></p>
                    </div>
                @endif
            </div>

            {{-- URL del webhook (para referencia) --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Tu URL de webhook</h3>
                <p class="text-xs text-gray-500 mb-3">Esta URL es única para tu bot. Si cambias el token, la URL cambia también.</p>
                <div x-data="{ copied: false }" class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5">
                        <p class="text-xs code-block text-gray-800 break-all">{{ $webhookUrl }}</p>
                    </div>
                    <button @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition-colors">
                        <span x-show="!copied">Copiar</span>
                        <span x-show="copied" class="text-green-600">Copiado!</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- ESTADO: NO CONECTADO — WIZARD 3 PASOS                           --}}
        {{-- ================================================================ --}}
        @else
        <div
            x-data="{
                step: 1,
                token: '',
                loading: false,
                error: '',
                botName: '',
                botUsername: '',
                webhookUrl: '',
                webhookRegistered: false,

                async saveToken() {
                    if (!this.token.trim()) return;
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('{{ route('vera.channels.telegram.save-token') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({ telegram_bot_token: this.token.trim() })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.botName = data.bot_name;
                            this.botUsername = data.bot_username;
                            this.webhookUrl = data.webhook_url;
                            this.step = 3;
                        } else {
                            this.error = data.message;
                        }
                    } catch(e) {
                        this.error = 'Error de conexión. Inténtalo de nuevo.';
                    }
                    this.loading = false;
                },

                async registerWebhook() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('{{ route('vera.channels.telegram.register-webhook') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.webhookRegistered = true;
                        } else {
                            this.error = data.message;
                        }
                    } catch(e) {
                        this.error = 'Error de conexión. Inténtalo de nuevo.';
                    }
                    this.loading = false;
                },

                copyToClipboard(text) {
                    navigator.clipboard.writeText(text);
                }
            }"
        >

            {{-- ── Indicador de pasos ────────────────────────────────────── --}}
            <div class="flex items-start gap-0 mb-8">
                {{-- Paso 1 --}}
                <div class="flex flex-col items-center">
                    <div :class="step >= 1 ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-400'"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0 transition-colors">
                        <span x-show="step <= 1">1</span>
                        <svg x-show="step > 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div :class="step > 1 ? 'bg-purple-600' : 'bg-gray-200'" class="w-0.5 h-16 mt-1 transition-colors"></div>
                </div>
                <div class="ml-4 pt-1 flex-1 pb-16">
                    <button @click="step = 1" class="text-left w-full">
                        <p :class="step === 1 ? 'text-gray-900 font-semibold' : (step > 1 ? 'text-purple-600 font-medium' : 'text-gray-400')"
                            class="text-sm transition-colors">Crear tu bot en Telegram</p>
                        <p class="text-xs text-gray-400 mt-0.5">Solo tarda 2 minutos con @BotFather</p>
                    </button>
                </div>

                {{-- Paso 2 --}}
                <div class="flex flex-col items-center">
                    <div :class="step >= 2 ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-400'"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0 transition-colors">
                        <span x-show="step <= 2">2</span>
                        <svg x-show="step > 2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div :class="step > 2 ? 'bg-purple-600' : 'bg-gray-200'" class="w-0.5 h-16 mt-1 transition-colors"></div>
                </div>
                <div class="ml-4 pt-1 flex-1 pb-16">
                    <p :class="step === 2 ? 'text-gray-900 font-semibold' : (step > 2 ? 'text-purple-600 font-medium' : 'text-gray-400')"
                        class="text-sm transition-colors">Conectar con Vera</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pega el token que te dio BotFather</p>
                </div>

                {{-- Paso 3 --}}
                <div class="flex flex-col items-center">
                    <div :class="step >= 3 ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-400'"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0 transition-colors">
                        <span x-show="!webhookRegistered">3</span>
                        <svg x-show="webhookRegistered" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 pt-1 flex-1">
                    <p :class="step === 3 ? 'text-gray-900 font-semibold' : 'text-gray-400'"
                        class="text-sm transition-colors">Activar Vera en el bot</p>
                    <p class="text-xs text-gray-400 mt-0.5">Un clic y Vera empieza a responder</p>
                </div>
            </div>

            {{-- ── PASO 1: Crear bot en BotFather ───────────────────────── --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                        <h2 class="text-sm font-semibold text-gray-900">Paso 1 — Crear tu bot en Telegram</h2>
                        <p class="text-xs text-gray-500 mt-0.5">@BotFather es el bot oficial de Telegram para crear bots. Es gratis y solo tarda 2 minutos.</p>
                    </div>

                    <div class="px-6 py-5 space-y-5">

                        {{-- Instrucción 1 --}}
                        <div class="flex gap-4">
                            <div class="w-7 h-7 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-xs font-bold text-blue-600">1</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Abre Telegram y busca <span class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-mono text-xs">@BotFather</span></p>
                                <p class="text-xs text-gray-500 mt-1">Funciona en Telegram para móvil, escritorio y web. El bot tiene el logo oficial de Telegram y una marca de verificación azul.</p>
                                <div class="mt-2 flex gap-2">
                                    <a href="https://t.me/botfather" target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                        </svg>
                                        Abrir @BotFather
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="border-l-2 border-gray-100 ml-3.5 pl-7 space-y-4">

                            {{-- Instrucción 2 --}}
                            <div class="flex gap-4">
                                <div class="w-7 h-7 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-xs font-bold text-purple-600">2</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Envía el comando <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">/newbot</code></p>
                                    <p class="text-xs text-gray-500 mt-1">BotFather te pedirá que le des un nombre a tu bot.</p>
                                </div>
                            </div>

                            {{-- Instrucción 3 --}}
                            <div class="flex gap-4">
                                <div class="w-7 h-7 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-xs font-bold text-purple-600">3</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Elige un nombre para el bot</p>
                                    <p class="text-xs text-gray-500 mt-1">Es el nombre que verán tus clientes cuando escriban al bot. Por ejemplo:</p>
                                    <div class="mt-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                        <p class="text-xs code-block text-gray-700">Secretaria de Clínica Roque</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Instrucción 4 --}}
                            <div class="flex gap-4">
                                <div class="w-7 h-7 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-xs font-bold text-purple-600">4</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Elige un username para el bot</p>
                                    <p class="text-xs text-gray-500 mt-1">Debe acabar en <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">_bot</code>. Este es el alias que tus clientes usarán para encontrar el bot. Por ejemplo:</p>
                                    <div class="mt-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                        <p class="text-xs code-block text-gray-700">clinica_roque_bot</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1.5">Tiene que ser único en Telegram. Si ya existe, prueba con otro.</p>
                                </div>
                            </div>

                            {{-- Instrucción 5 --}}
                            <div class="flex gap-4">
                                <div class="w-7 h-7 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-xs font-bold text-green-600">5</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Copia el <strong>token</strong> que te da BotFather</p>
                                    <p class="text-xs text-gray-500 mt-1">BotFather te enviará un mensaje con el token. Tiene este formato:</p>
                                    <div class="mt-2 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2.5">
                                        <p class="text-xs code-block text-amber-800">7123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</p>
                                    </div>
                                    <div class="mt-2 flex items-start gap-1.5 bg-blue-50 rounded-lg px-3 py-2">
                                        <svg class="w-3.5 h-3.5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-xs text-blue-700">El token es como una contraseña. No lo compartas con nadie.</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button @click="step = 2"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                            Ya tengo el token
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── PASO 2: Pegar el token ────────────────────────────────── --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-purple-50">
                        <h2 class="text-sm font-semibold text-gray-900">Paso 2 — Conectar con Vera</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pega el token que te dio @BotFather. Vera verificará que es válido.</p>
                    </div>

                    <div class="px-6 py-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Token del bot</label>
                        <div class="flex gap-2">
                            <input
                                x-model="token"
                                type="text"
                                placeholder="7123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                class="flex-1 code-block text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 focus:outline-none"
                                @keydown.enter="saveToken()"
                            >
                        </div>
                        <p class="text-xs text-gray-400 mt-2">El token lo encuentras en el mensaje de @BotFather justo después de crear el bot.</p>

                        <div x-show="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-600" x-text="error"></p>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <button @click="step = 1; error = ''"
                            class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                            ← Volver
                        </button>
                        <button @click="saveToken()" :disabled="loading || !token.trim()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-show="!loading">Verificar y conectar</span>
                            <span x-show="loading">Verificando...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── PASO 3: Activar webhook ───────────────────────────────── --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Éxito verificación --}}
                <div x-show="!webhookRegistered">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-5 flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-green-800">
                                Bot verificado: <span x-text="botName"></span> (<span x-text="botUsername"></span>)
                            </p>
                            <p class="text-xs text-green-600">Token guardado correctamente. Ahora hay que activar el webhook.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-purple-50">
                            <h2 class="text-sm font-semibold text-gray-900">Paso 3 — Activar Vera en el bot</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Registra el webhook para que Telegram sepa que tiene que enviar los mensajes a Vera.</p>
                        </div>

                        <div class="px-6 py-5">
                            <p class="text-sm text-gray-600 mb-4">Al pulsar el botón, Vera se registra automáticamente con Telegram. Desde ese momento, cualquier mensaje que llegue al bot será gestionado por Vera.</p>

                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                                <p class="text-xs text-gray-500 mb-1.5 font-medium">URL del webhook que se registrará</p>
                                <div class="flex items-center gap-2" x-data="{ copied: false }">
                                    <p class="flex-1 text-xs code-block text-gray-800 break-all" x-text="webhookUrl"></p>
                                    <button @click="navigator.clipboard.writeText(webhookUrl); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="flex-shrink-0 text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-gray-100 transition-colors">
                                        <span x-show="!copied">Copiar</span>
                                        <span x-show="copied" class="text-green-600">Copiado!</span>
                                    </button>
                                </div>
                            </div>

                            <div x-show="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm text-red-600" x-text="error"></p>
                            </div>

                            <button @click="registerWebhook()" :disabled="loading"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors w-full justify-center">
                                <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span x-show="!loading">Activar webhook automáticamente</span>
                                <span x-show="loading">Activando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── ÉXITO TOTAL ─────────────────────────────────────── --}}
                <div x-show="webhookRegistered" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="bg-white rounded-xl border border-green-200 overflow-hidden">
                        <div class="px-6 py-8 text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 mb-2">Telegram conectado!</h2>
                            <p class="text-sm text-gray-500 mb-1">El bot <strong x-text="botUsername"></strong> ya está activo.</p>
                            <p class="text-sm text-gray-500 mb-6">Vera responderá automáticamente a todos los mensajes que reciba.</p>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-left">
                                <p class="text-xs font-medium text-blue-800 mb-2">Pruébalo ahora mismo</p>
                                <p class="text-xs text-blue-700">Busca tu bot en Telegram por su username y envíale un mensaje como:</p>
                                <div class="mt-2 bg-white border border-blue-200 rounded px-3 py-2">
                                    <p class="text-xs code-block text-gray-700">"Hola, me gustaría pedir una cita para el martes"</p>
                                </div>
                                <p class="text-xs text-blue-600 mt-2">Vera debería responder con slots disponibles de tu calendario.</p>
                            </div>

                            <a href="{{ route('vera.dashboard') }}"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                Ir al panel de Vera
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @endif

    </main>
</div>

</body>
</html>
