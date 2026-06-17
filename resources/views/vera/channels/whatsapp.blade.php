<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conectar WhatsApp — Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .code-block { font-family: 'Courier New', monospace; }
    </style>
</head>
<body class="antialiased bg-[#fafafa] text-gray-900">

<div class="flex min-h-screen overflow-x-hidden">
    @include('components.dashboard-sidebar')

    <main class="flex-1 min-w-0 lg:ml-56">

        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center px-4 lg:hidden">
            <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100 mr-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-sm font-semibold text-gray-900">Conectar WhatsApp</span>
        </header>

        <div class="p-4 lg:p-8 max-w-3xl">

        <div class="hidden lg:flex items-center gap-2 text-sm text-gray-400 mb-6">
            <a href="{{ route('vera.dashboard') }}" class="hover:text-gray-600">Panel Vera</a>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-600 font-medium">Conectar WhatsApp</span>
        </div>

        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.515 5.26l-.999 3.648 3.973-1.607z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Conectar WhatsApp</h1>
                <p class="text-sm text-gray-500">Vera responderá los mensajes de WhatsApp automáticamente</p>
            </div>
        </div>

        {{-- ESTADO: CONECTADO --}}
        @if($config->hasWhatsAppConnected())
        <div x-data="{
                disconnecting: false,
                async disconnect() {
                    if (!confirm('¿Desconectar WhatsApp? Vera dejará de responder por este número.')) return;
                    this.disconnecting = true;
                    const res = await fetch('{{ route('vera.channels.whatsapp.disconnect') }}', {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    if ((await res.json()).success) window.location.reload();
                    this.disconnecting = false;
                }
            }">
            <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-800">WhatsApp conectado</p>
                        <p class="text-xs text-green-600">Phone Number ID: <span class="code-block">{{ $config->whatsapp_phone_number_id }}</span></p>
                    </div>
                </div>
                <button @click="disconnect()" :disabled="disconnecting"
                    class="text-xs text-red-600 hover:text-red-800 font-medium border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50">
                    <span x-show="!disconnecting">Desconectar</span>
                    <span x-show="disconnecting">Desconectando...</span>
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Datos del webhook (para configurar en Meta)</h3>
                <p class="text-xs text-gray-500 mb-1">URL de devolución de llamada</p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 mb-3"><p class="text-xs code-block text-gray-800 break-all">{{ $webhookUrl }}</p></div>
                <p class="text-xs text-gray-500 mb-1">Token de verificación</p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2"><p class="text-xs code-block text-gray-800 break-all">{{ $verifyToken ?: '(define WHATSAPP_VERIFY_TOKEN en el servidor)' }}</p></div>
            </div>
        </div>

        {{-- ESTADO: NO CONECTADO --}}
        @else
        <div x-data="{
                pnid: '', token: '', loading: false, error: '', done: false, number: '', name: '',
                async save() {
                    if (!this.pnid.trim() || !this.token.trim()) return;
                    this.loading = true; this.error = '';
                    try {
                        const res = await fetch('{{ route('vera.channels.whatsapp.save') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ whatsapp_phone_number_id: this.pnid.trim(), whatsapp_access_token: this.token.trim() })
                        });
                        const data = await res.json();
                        if (data.success) { this.number = data.number; this.name = data.name; this.done = true; setTimeout(() => window.location.reload(), 2000); }
                        else { this.error = data.message; }
                    } catch(e) { this.error = 'Error de conexión. Inténtalo de nuevo.'; }
                    this.loading = false;
                }
            }">

            {{-- Instrucciones --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
                <p class="text-sm font-semibold text-blue-900 mb-2">Dónde encontrar estos datos</p>
                <p class="text-sm text-blue-800">En <strong>Meta for Developers</strong> → tu app → WhatsApp → <strong>Configuración de la API</strong>: copia el <strong>Phone Number ID</strong> y genera/copia el <strong>token de acceso</strong>. Y en <strong>WhatsApp → Configuración → Webhook</strong> pon la URL y el token de verificación de abajo, y suscríbete a <code class="text-xs bg-blue-100 px-1 rounded">messages</code>.</p>
            </div>

            {{-- Datos del webhook (referencia) --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Datos para el webhook en Meta</h3>
                <p class="text-xs text-gray-500 mb-1">URL de devolución de llamada</p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 mb-3"><p class="text-xs code-block text-gray-800 break-all">{{ $webhookUrl }}</p></div>
                <p class="text-xs text-gray-500 mb-1">Token de verificación</p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2"><p class="text-xs code-block text-gray-800 break-all">{{ $verifyToken ?: '(define WHATSAPP_VERIFY_TOKEN en el servidor)' }}</p></div>
            </div>

            {{-- Formulario --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-green-50">
                    <h2 class="text-sm font-semibold text-gray-900">Conectar tu número de WhatsApp</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Vera verificará los datos contra Meta antes de guardarlos.</p>
                </div>
                <div class="px-6 py-6 space-y-4" x-show="!done">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number ID</label>
                        <input x-model="pnid" type="text" placeholder="1024397470767632"
                            class="w-full code-block text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Token de acceso</label>
                        <input x-model="token" type="password" placeholder="EAAxxxxxxxx..."
                            class="w-full code-block text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-1">El token de prueba caduca a las 24h. Para producción usa un token permanente.</p>
                    </div>
                    <div x-show="error" class="p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-600" x-text="error"></p></div>
                </div>
                <div x-show="done" class="px-6 py-8 text-center">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-base font-bold text-gray-900">¡WhatsApp conectado!</p>
                    <p class="text-sm text-gray-500 mt-1" x-text="(name || '') + (number ? ' · ' + number : '')"></p>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end" x-show="!done">
                    <button @click="save()" :disabled="loading || !pnid.trim() || !token.trim()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-show="!loading">Verificar y conectar</span>
                        <span x-show="loading">Verificando...</span>
                    </button>
                </div>
            </div>
        </div>
        @endif

        </div>
    </main>
</div>

</body>
</html>
