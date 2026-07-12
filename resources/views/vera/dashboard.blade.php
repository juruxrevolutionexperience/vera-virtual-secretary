<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Vera - Agenda IA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .stat-card { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="antialiased bg-[#fafafa] text-gray-900">

<div class="flex min-h-screen overflow-x-hidden">
    @include('components.dashboard-sidebar')

    <!-- Main content -->
    <main class="flex-1 min-w-0 lg:ml-56">

        <!-- Header móvil sticky (solo < lg) -->
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center px-4 lg:hidden">
            <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100 mr-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-sm font-semibold text-gray-900">Panel de Vera</span>
        </header>

        <div class="p-4 lg:p-8">

        <!-- Header escritorio -->
        <div class="hidden lg:flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bandeja</h1>
                <p class="text-sm text-gray-500 mt-0.5">Todo lo que requiere tu acción: conversaciones, incidencias y citas por confirmar</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if($config)
                    <a href="{{ route('vera.config') }}" title="Cambiar el modo de Vera"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-medium rounded-full border border-purple-200 hover:bg-purple-100 transition-colors">
                        <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                        Vera: {{ $config->mode === 'autonomous' ? 'Autónoma' : 'Supervisada' }}
                    </a>
                @endif
                @if($config && $config->hasCalendarConnected())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 text-xs font-medium rounded-full border border-green-200">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                        Google Calendar conectado
                    </span>
                @else
                    <a href="{{ route('google.calendar.connect') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-700 text-xs font-medium rounded-full border border-amber-200 hover:bg-amber-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Conectar Google Calendar
                    </a>
                @endif

                <a href="{{ route('vera.config') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-600 text-xs font-medium rounded-full border border-gray-200 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configurar Vera
                </a>
            </div>
        </div>

        {{-- Recorte Fase A: el bloque grande de "Modo de Vera" pasó a badge en la
             cabecera; las Stats Cards viven ahora en el Resumen. La Bandeja es solo
             lo que requiere acción del dueño. --}}

        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,4000)"
             class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800 font-medium">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,6000)"
             class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800 font-medium">
            {{ session('error') }}
        </div>
        @endif

        <!-- Incidencias / quejas escaladas (requieren al dueño) -->
        @if(isset($escalations) && $escalations->isNotEmpty())
        <div class="mb-6 bg-white border border-red-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-red-100 bg-red-50 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-red-900">⚠️ Incidencias / Quejas</h2>
                    <p class="text-xs text-red-600 mt-0.5">Vera las escaló — gestiónalas tú</p>
                </div>
                <span class="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full">{{ $escalations->count() }}</span>
            </div>
            <div class="divide-y divide-red-50">
                @foreach($escalations as $inc)
                @php
                    // Email del cliente para poder responderle (de la relación o del identificador).
                    // Los "@vera.internal" son emails sintéticos de contactos de Telegram — no mostrarlos.
                    $clientEmail = $inc->contact?->email;
                    if ($clientEmail && str_ends_with($clientEmail, '@vera.internal')) { $clientEmail = null; }
                    if (!$clientEmail && preg_match('/<(.+?)>/', $inc->contact_identifier, $mm)) { $clientEmail = $mm[1]; }
                    if (!$clientEmail && filter_var($inc->contact_identifier, FILTER_VALIDATE_EMAIL)) { $clientEmail = $inc->contact_identifier; }
                    $clientPhone = $inc->contact?->phone;
                    $fullMsg = $inc->context['last_message'] ?? $inc->escalation_reason ?? 'Queja escalada';
                    // Enlace a Gmail: si tenemos el ID del mensaje concreto, abrimos ESE
                    // email directamente (#all/{id}); si no, caemos al listado por remitente.
                    $gmailMsgId = $inc->context['gmail_message_id'] ?? null;
                    $gmailUrl = $gmailMsgId
                        ? 'https://mail.google.com/mail/u/0/#all/' . $gmailMsgId
                        : ($clientEmail ? 'https://mail.google.com/mail/u/0/#search/from:' . urlencode($clientEmail) : null);
                    // Hilo de la conversación (últimos mensajes): la incidencia es un
                    // chat dueño-cliente hasta que se cierre, no un aviso de un solo golpe.
                    $thread = $inc->conversation?->messages()->orderByDesc('created_at')->limit(8)->get()->reverse()->values() ?? collect();
                    $clientReplied = !empty($inc->context['last_client_reply_at']);
                @endphp
                <div class="px-5 py-4" x-data="{ replying: false }">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $inc->contact?->name ?? $clientEmail ?? $inc->contact_identifier }}</p>
                            <p class="text-xs text-gray-500">
                                @if($clientEmail)<span>✉️ {{ $clientEmail }}</span>@endif
                                @if($clientPhone) · <span>📞 {{ $clientPhone }}</span>@endif
                                · {{ $inc->updated_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 flex items-center gap-1.5">
                            @if($clientReplied)
                            <span class="text-[11px] uppercase tracking-wide font-semibold text-white bg-purple-600 px-2 py-0.5 rounded animate-pulse">💬 Ha respondido</span>
                            @endif
                            <span class="text-[11px] uppercase tracking-wide font-semibold text-red-500 bg-red-50 px-2 py-0.5 rounded">Queja</span>
                        </div>
                    </div>
                    <!-- Hilo de la conversación (o el mensaje suelto si aún no hay hilo) -->
                    @if($thread->isNotEmpty())
                    <div class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 max-h-56 overflow-y-auto space-y-2">
                        @foreach($thread as $msg)
                        <div class="flex {{ $msg->role === 'user' ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[85%] text-sm rounded-lg px-3 py-1.5 whitespace-pre-line {{ $msg->role === 'user' ? 'bg-white border border-gray-200 text-gray-800' : 'bg-purple-50 border border-purple-100 text-purple-900' }}">
                                <span class="block text-[10px] font-semibold {{ $msg->role === 'user' ? 'text-gray-400' : 'text-purple-400' }}">{{ $msg->role === 'user' ? 'Cliente' : 'Vera' }} · {{ $msg->created_at->format('d/m H:i') }}</span>
                                {{ $msg->content }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="bg-gray-50 border-l-2 border-red-300 rounded px-3 py-2 text-sm text-gray-700 whitespace-pre-line">{{ $fullMsg }}</div>
                    @endif
                    <p class="text-[11px] text-amber-600 mt-2">🔇 Vera está en silencio con este contacto. La conversación es tuya: responde lo que haga falta y, cuando esté resuelta, ciérrala para devolvérsela a Vera.</p>
                    <!-- Acciones: responder + marcar gestionada -->
                    <div class="flex items-center gap-2 mt-3">
                        @if(in_array($inc->channel, ['telegram', 'whatsapp']))
                        <button type="button" @click="replying = !replying"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                            💬 Responder vía Vera
                        </button>
                        @endif
                        @if($gmailUrl)
                        {{-- Si tenemos el ID del mensaje, abre ESE email directamente; si no,
                             cae al listado por remitente para responder en el hilo real. --}}
                        <a href="{{ $gmailUrl }}"
                           target="_blank" rel="noopener"
                           class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                            ✉️ Abrir en Gmail
                        </a>
                        @endif
                        @if($clientEmail)
                        {{-- Copiar email: funciona siempre, sin depender de cliente de correo. --}}
                        <button type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('{{ $clientEmail }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-medium rounded-lg transition-colors">
                            <span x-show="!copied">📋 Copiar email</span>
                            <span x-show="copied" x-cloak class="text-green-600">✓ Copiado</span>
                        </button>
                        @endif
                        <form action="{{ route('incidencias.gestionada', $inc) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-white border border-red-200 text-red-700 hover:bg-red-50 text-xs font-medium rounded-lg transition-colors"
                                onclick="return confirm('¿Cerrar la incidencia? El cliente recibirá un mensaje avisando de que Vera vuelve a atenderle.')">
                                ✓ Cerrar y devolver a Vera
                            </button>
                        </form>
                    </div>
                    @if(in_array($inc->channel, ['telegram', 'whatsapp']))
                    <!-- Responder vía Vera: el dueño escribe, Vera lo presenta al cliente -->
                    <form x-show="replying" x-cloak action="{{ route('incidencias.responder', $inc) }}" method="POST" class="mt-3">
                        @csrf
                        <textarea name="reply" rows="3" required maxlength="2000"
                            placeholder="Escribe tu respuesta para el cliente..."
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-y"></textarea>
                        <div class="flex items-center justify-between gap-3 mt-2">
                            <p class="text-[11px] text-gray-400">Vera se la enviará presentándola de tu parte, y la incidencia quedará gestionada.</p>
                            <button type="submit" class="flex-shrink-0 px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                                Enviar vía Vera
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Consultas sin respuesta (Fase C): Vera no tenía el dato — respóndele tú
             y, si quieres, guárdalo como FAQ para que la próxima vez responda ella. -->
        @if(isset($inquiries) && $inquiries->isNotEmpty())
        <div class="mb-6 bg-white border border-amber-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-amber-100 bg-amber-50 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-amber-900">❓ Consultas sin respuesta</h2>
                    <p class="text-xs text-amber-700 mt-0.5">Vera no tenía este dato. Respóndele tú y, si lo guardas, la próxima vez contesta ella sola</p>
                </div>
                <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-500 text-white text-xs font-bold rounded-full">{{ $inquiries->count() }}</span>
            </div>
            <div class="divide-y divide-amber-50">
                @foreach($inquiries as $inq)
                <div class="px-5 py-4" x-data="{ answering: false }">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $inq->contact?->name ?? $inq->contact_name ?? $inq->contact_identifier }}</p>
                            <p class="text-xs text-gray-500">via {{ ucfirst($inq->channel) }} · {{ $inq->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="flex-shrink-0 text-[11px] uppercase tracking-wide font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Consulta</span>
                    </div>
                    <div class="bg-gray-50 border-l-2 border-amber-300 rounded px-3 py-2 text-sm text-gray-700 whitespace-pre-line">{{ $inq->question }}</div>
                    <div class="flex items-center gap-2 mt-3">
                        <button type="button" @click="answering = !answering"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                            {{ in_array($inq->channel, ['telegram', 'whatsapp']) ? '💬 Responder vía Vera' : '✍️ Registrar respuesta' }}
                        </button>
                        <form action="{{ route('consultas.descartar', $inq) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 text-xs font-medium rounded-lg transition-colors"
                                onclick="return confirm('¿Descartar esta consulta sin responder?')">
                                Descartar
                            </button>
                        </form>
                    </div>
                    <form x-show="answering" x-cloak action="{{ route('consultas.responder', $inq) }}" method="POST" class="mt-3">
                        @csrf
                        <textarea name="answer" rows="3" required maxlength="2000"
                            placeholder="Escribe la respuesta para el cliente..."
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-y"></textarea>
                        <div class="flex items-center justify-between gap-3 mt-2 flex-wrap">
                            <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox" name="save_faq" value="1" checked
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                Guardar en la información de Vera (la próxima vez responde ella sola)
                            </label>
                            <button type="submit" class="flex-shrink-0 px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                                {{ in_array($inq->channel, ['telegram', 'whatsapp']) ? 'Enviar vía Vera' : 'Guardar respuesta' }}
                            </button>
                        </div>
                        @if(!in_array($inq->channel, ['telegram', 'whatsapp']))
                        <p class="text-[11px] text-gray-400 mt-1">Consulta llegada por email: recuerda contestar al cliente desde Gmail. Aquí queda registrada y Vera la aprende.</p>
                        @endif
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Próximas reuniones -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Próximas reuniones</h2>
                    <span class="text-xs text-gray-400">Confirmadas</span>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($upcomingMeetings as $meeting)
                    <div class="px-5 py-4 flex items-start gap-3">
                        <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $meeting->contact_name }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($meeting->scheduled_at)->format('d M, H:i') }} · {{ $meeting->duration_minutes }} min</p>
                            @if($meeting->google_meet_link)
                            <a href="{{ $meeting->google_meet_link }}" target="_blank" class="text-xs text-purple-600 hover:underline">Unirse a Meet</a>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            {{ ucfirst($meeting->source_channel) }}
                        </span>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-400">Sin reuniones próximas</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Solicitudes pendientes -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Solicitudes pendientes</h2>
                    @if($stats['pending'] > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        {{ $stats['pending'] }} pendiente{{ $stats['pending'] > 1 ? 's' : '' }}
                    </span>
                    @endif
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($pendingMeetings as $meeting)
                    <div class="px-5 py-4 flex items-start gap-3">
                        <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $meeting->contact_name }}</p>
                            <p class="text-xs text-gray-500">{{ $meeting->contact_email ?? $meeting->contact_phone }}</p>
                            <p class="text-xs text-gray-400">via {{ ucfirst($meeting->source_channel) }} · {{ \Carbon\Carbon::parse($meeting->created_at)->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <form action="{{ route('meetings.confirm', $meeting) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                                    Confirmar
                                </button>
                            </form>
                            <form action="{{ route('meetings.reject', $meeting) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-xs px-2 py-1 bg-white text-gray-500 border border-gray-200 rounded hover:bg-gray-50 transition-colors"
                                    onclick="return confirm('¿Rechazar esta reserva? El cliente será notificado.')">
                                    Rechazar
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400">Sin solicitudes pendientes</p>
                        <p class="text-xs text-gray-300 mt-1">Vera gestionará las nuevas solicitudes automáticamente</p>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Recorte Fase A: "Actividad reciente" vive en su página (Actividad) y
             "Canales de entrada" en su sección (Canales). Aquí solo lo accionable. --}}

        </div>{{-- /p-4 lg:p-8 --}}
    </main>
</div>

{{-- Bandeja en vivo: sondeo ligero cada 12s. Actualiza el contador de la sidebar
     y, si hay novedades (mensaje nuevo del cliente, cita nueva...), recarga la
     página — salvo que el dueño esté escribiendo una respuesta, para no pisarle. --}}
<script>
(function () {
    let fingerprint = @json($bandejaFingerprint ?? '');
    const url = "{{ route('vera.bandeja.ping') }}";

    async function tick() {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();

            document.querySelectorAll('[data-bandeja-count]').forEach(function (el) {
                el.textContent = data.pending;
                el.classList.toggle('hidden', data.pending === 0);
            });

            const typing = Array.from(document.querySelectorAll('textarea')).some(function (t) {
                return t.value.trim() !== '' || t === document.activeElement;
            });

            if (fingerprint && data.fingerprint !== fingerprint && !typing) {
                location.reload();
            }
        } catch (e) { /* sin red: se reintenta en el siguiente tick */ }
    }

    setInterval(tick, 12000);
})();
</script>

@stack('scripts')
</body>
</html>
