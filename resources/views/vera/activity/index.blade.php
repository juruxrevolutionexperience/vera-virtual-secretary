<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Actividad de Vera - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        .sidebar { transition: transform 0.2s ease; }
        @media (max-width: 768px) { .sidebar.hidden { transform: translateX(-100%); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

<div class="flex min-h-screen">
    @include('components.dashboard-sidebar')

    <main class="flex-1 md:ml-56">
        <!-- Top Bar -->
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 backdrop-blur-sm bg-white/80">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-sm font-semibold text-gray-900">Lo que ha hecho Vera</h1>
                <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                <span class="hidden sm:inline-block text-xs text-gray-500">Este mes</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('vera.activity.export', request()->query()) }}"
                   class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md border border-gray-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Exportar</span>
                </a>
            </div>
        </header>

        <div class="p-6 max-w-[1400px] mx-auto">

            {{-- Tarjetas de impacto --}}
            @php
                $meetingsConfirmed = $stats['meetings_confirmed'] ?? 0;
                $hoursSaved        = round($meetingsConfirmed * 0.5, 1);
                $messagesHandled   = $stats['responses_sent'] ?? 0;
                $meetingsRemaining = $currentUsage['meetings_remaining'] ?? 0;
                $meetingLimit      = $currentUsage['meeting_limit'] ?? null;
                $usagePercent      = $currentUsage['usage_percentage'] ?? 0;
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-in">

                {{-- Reuniones confirmadas --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reuniones confirmadas</p>
                        <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">{{ $meetingsConfirmed }}</div>
                    <p class="text-xs text-gray-500 mt-1">Este mes</p>
                </div>

                {{-- Horas ahorradas --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Horas ahorradas</p>
                        <div class="w-9 h-9 bg-purple-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">{{ $hoursSaved }}h</div>
                    <p class="text-xs text-gray-500 mt-1">~30 min por coordinación</p>
                </div>

                {{-- Mensajes gestionados --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Mensajes gestionados</p>
                        <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">{{ $messagesHandled }}</div>
                    <p class="text-xs text-gray-500 mt-1">Respondidos por Vera</p>
                </div>

                {{-- Reuniones disponibles --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reuniones disponibles</p>
                        <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">
                        {{ $meetingLimit ? $meetingsRemaining : '∞' }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Plan {{ ucfirst($currentUsage['plan_type']) }}
                        @if($meetingLimit) · {{ $meetingLimit }} al mes @endif
                    </p>
                </div>

            </div>

            {{-- Barra de uso del plan --}}
            @if($meetingLimit)
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6 fade-in">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Uso del plan este mes</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $meetingsConfirmed }} de {{ $meetingLimit }} reuniones usadas</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        @if($alertLevel === 'critical' || $alertLevel === 'blocked') bg-red-100 text-red-700
                        @elseif($alertLevel === 'warning') bg-yellow-100 text-yellow-700
                        @else bg-green-100 text-green-700 @endif">
                        {{ round($usagePercent) }}% usado
                    </span>
                </div>
                <div class="relative h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500
                        @if($alertLevel === 'critical' || $alertLevel === 'blocked') bg-red-500
                        @elseif($alertLevel === 'warning') bg-yellow-500
                        @else bg-green-500 @endif"
                        style="width: {{ $usagePercent }}%"></div>
                </div>
                @if($alertLevel === 'warning' || $alertLevel === 'critical' || $alertLevel === 'blocked')
                <p class="text-xs text-amber-600 mt-2 font-medium">
                    ⚠ Te quedan {{ $meetingsRemaining }} reuniones este mes.
                    <a href="{{ route('billing') }}" class="underline hover:text-amber-700">Ampliar plan</a>
                </p>
                @endif
            </div>
            @endif

            {{-- Filtros --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6 fade-in">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Filtrar actividad</h3>
                <form method="GET" action="{{ route('vera.activity.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Tipo</label>
                        <select name="type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 bg-white">
                            <option value="">Todos</option>
                            <option value="meeting_confirmed"   {{ request('type') == 'meeting_confirmed'   ? 'selected' : '' }}>Reunión confirmada</option>
                            <option value="meeting_requested"   {{ request('type') == 'meeting_requested'   ? 'selected' : '' }}>Solicitud recibida</option>
                            <option value="meeting_rescheduled" {{ request('type') == 'meeting_rescheduled' ? 'selected' : '' }}>Reprogramada</option>
                            <option value="meeting_cancelled"   {{ request('type') == 'meeting_cancelled'   ? 'selected' : '' }}>Cancelada</option>
                            <option value="response_sent"       {{ request('type') == 'response_sent'       ? 'selected' : '' }}>Respuesta enviada</option>
                            <option value="escalation"          {{ request('type') == 'escalation'          ? 'selected' : '' }}>Escalado a ti</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Valoración</label>
                        <select name="feedback" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 bg-white">
                            <option value="">Todas</option>
                            <option value="approved" {{ request('feedback') == 'approved' ? 'selected' : '' }}>Aprobadas</option>
                            <option value="rejected" {{ request('feedback') == 'rejected' ? 'selected' : '' }}>Rechazadas</option>
                            <option value="modified" {{ request('feedback') == 'modified' ? 'selected' : '' }}>Corregidas</option>
                            <option value="pending"  {{ request('feedback') == 'pending'  ? 'selected' : '' }}>Sin valorar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Desde</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Hasta</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 bg-white">
                    </div>

                    <div class="md:col-span-4 flex gap-2">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-md">
                            Aplicar
                        </button>
                        <a href="{{ route('vera.activity.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            {{-- Timeline de actividad --}}
            <div class="bg-white rounded-xl border border-gray-200 fade-in">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Historial de actividad</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $activities->total() }} acciones registradas</p>
                    </div>
                </div>

                @if($activities->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($activities as $activity)
                            @php
                                $typeConfig = match($activity->activity_type) {
                                    'meeting_confirmed'   => ['bg' => 'bg-green-50',  'icon_color' => 'text-green-600',  'emoji' => '✅', 'label' => 'Reunión confirmada'],
                                    'meeting_requested'   => ['bg' => 'bg-purple-50', 'icon_color' => 'text-purple-600', 'emoji' => '📅', 'label' => 'Solicitud recibida'],
                                    'meeting_rescheduled' => ['bg' => 'bg-amber-50',  'icon_color' => 'text-amber-600',  'emoji' => '🔄', 'label' => 'Reunión reprogramada'],
                                    'meeting_cancelled'   => ['bg' => 'bg-red-50',    'icon_color' => 'text-red-600',    'emoji' => '❌', 'label' => 'Reunión cancelada'],
                                    'response_sent'       => ['bg' => 'bg-blue-50',   'icon_color' => 'text-blue-600',   'emoji' => '💬', 'label' => 'Respuesta enviada'],
                                    'escalation'          => ['bg' => 'bg-orange-50', 'icon_color' => 'text-orange-600', 'emoji' => '🔔', 'label' => 'Escalado a ti'],
                                    'chat_interaction'    => ['bg' => 'bg-pink-50',   'icon_color' => 'text-pink-600',   'emoji' => '🤖', 'label' => 'Chat con Vera'],
                                    default               => ['bg' => 'bg-gray-50',   'icon_color' => 'text-gray-500',   'emoji' => '⚡', 'label' => ucfirst(str_replace('_', ' ', $activity->activity_type))],
                                };

                                $channel = $activity->metadata['channel'] ?? null;
                                $channelLabel = match($channel) {
                                    'whatsapp' => '· WhatsApp',
                                    'telegram' => '· Telegram',
                                    'email'    => '· Email',
                                    default    => '',
                                };
                            @endphp

                            <div class="p-5 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 flex-shrink-0 rounded-xl {{ $typeConfig['bg'] }} flex items-center justify-center text-lg">
                                        {{ $typeConfig['emoji'] }}
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4 mb-1">
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h4 class="text-sm font-medium text-gray-900">{{ $activity->title }}</h4>
                                                    <span class="text-xs text-gray-400">{{ $typeConfig['label'] }} {{ $channelLabel }}</span>
                                                </div>
                                                @if($activity->description)
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $activity->description }}</p>
                                                @endif
                                            </div>
                                            <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        {{-- Feedback del usuario --}}
                                        <div class="flex items-center gap-2 mt-2">
                                            @if($activity->user_feedback === 'approved')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Aprobado
                                                </span>
                                            @elseif($activity->user_feedback === 'rejected')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Rechazado
                                                </span>
                                            @elseif($activity->user_feedback === 'modified')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">
                                                    ✎ Corregido
                                                </span>
                                            @else
                                                {{-- Botones de feedback rápido --}}
                                                <form method="POST" action="{{ route('vera.activity.feedback', $activity->id) }}" class="inline-flex gap-1">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="feedback" value="approved">
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 hover:bg-green-50 hover:text-green-700 transition-colors">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        Correcto
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('vera.activity.feedback', $activity->id) }}" class="inline-flex gap-1">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="feedback" value="rejected">
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-700 transition-colors">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        Mejorable
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-5 border-t border-gray-200">
                        {{ $activities->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Vera aún no ha hecho nada</h3>
                        <p class="text-xs text-gray-500 mb-4">
                            @if(request()->hasAny(['type', 'feedback', 'date_from', 'date_to']))
                                No hay actividad con esos filtros.
                            @else
                                En cuanto reciba su primer mensaje, aparecerá aquí.
                            @endif
                        </p>
                        @if(request()->hasAny(['type', 'feedback', 'date_from', 'date_to']))
                            <a href="{{ route('vera.activity.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 hover:text-purple-700">
                                Ver todo
                            </a>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </main>
</div>

@include('components.settings-modal')

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('hidden');
    }
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 768 && !sidebar.contains(event.target) && !event.target.closest('[onclick="toggleSidebar()"]')) {
            sidebar.classList.add('hidden');
        }
    });
</script>

</body>
</html>
