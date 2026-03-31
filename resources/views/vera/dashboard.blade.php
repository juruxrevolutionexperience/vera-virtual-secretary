<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

<div class="flex min-h-screen">
    @include('components.dashboard-sidebar')

    <!-- Main content -->
    <main class="flex-1 ml-56 p-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Panel de Vera</h1>
                <p class="text-sm text-gray-500 mt-0.5">Tu secretaria de agenda inteligente</p>
            </div>
            <div class="flex items-center gap-3">
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

        <!-- Modo actual de Vera -->
        @if($config)
        <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-purple-900">
                        Vera está en modo <strong>{{ $config->mode === 'autonomous' ? 'Autónomo' : 'Supervisado' }}</strong>
                    </p>
                    <p class="text-xs text-purple-600">
                        {{ $config->mode === 'autonomous' ? 'Agenda reuniones automáticamente sin tu aprobación' : 'Propone horarios y espera tu confirmación antes de confirmar' }}
                    </p>
                </div>
            </div>
            <a href="{{ route('vera.config') }}" class="text-xs text-purple-700 font-medium hover:text-purple-900">Cambiar →</a>
        </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-5 gap-4 mb-8">
            <div class="stat-card bg-white rounded-xl border border-gray-200 p-5 text-center">
                <p class="text-xs text-gray-500 mb-2">Hoy</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['today'] }}</p>
                <p class="text-xs text-gray-400 mt-1">reuniones</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-200 p-5 text-center">
                <p class="text-xs text-gray-500 mb-2">Esta semana</p>
                <p class="text-3xl font-bold text-purple-600">{{ $stats['this_week'] }}</p>
                <p class="text-xs text-gray-400 mt-1">confirmadas</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-200 p-5 text-center">
                <p class="text-xs text-gray-500 mb-2">Este mes</p>
                <p class="text-3xl font-bold text-blue-600">{{ $stats['this_month'] }}</p>
                <p class="text-xs text-gray-400 mt-1">confirmadas</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-200 p-5 text-center">
                <p class="text-xs text-gray-500 mb-2">Pendientes</p>
                <p class="text-3xl font-bold text-amber-500">{{ $stats['pending'] }}</p>
                <p class="text-xs text-gray-400 mt-1">por confirmar</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-200 p-5 text-center">
                <p class="text-xs text-gray-500 mb-2">Total</p>
                <p class="text-3xl font-bold text-green-600">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-400 mt-1">historial</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">

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
                            <button class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                                Confirmar
                            </button>
                            <button class="text-xs px-2 py-1 bg-white text-gray-500 border border-gray-200 rounded hover:bg-gray-50 transition-colors">
                                Rechazar
                            </button>
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

        <!-- Actividad reciente de Vera -->
        <div class="mt-6 bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Actividad reciente</h2>
                <a href="{{ route('vera.activity.index') }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Ver todo →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentActivity as $log)
                @php
                    $icon = match($log->activity_type) {
                        'message_received' => $log->metadata['ignored'] ?? false
                            ? ['bg' => 'bg-gray-100', 'text' => 'text-gray-400', 'emoji' => '🚫']
                            : ['bg' => 'bg-blue-100',  'text' => 'text-blue-600',  'emoji' => '📧'],
                        'slot_proposed'    => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'emoji' => '📅'],
                        'meeting_confirmed'=> ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'emoji' => '✅'],
                        'escalation'       => ['bg' => 'bg-red-100',    'text' => 'text-red-600',    'emoji' => '⚠️'],
                        default            => ['bg' => 'bg-gray-100',   'text' => 'text-gray-500',   'emoji' => '•'],
                    };
                @endphp
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 {{ $icon['bg'] }} rounded-lg flex items-center justify-center flex-shrink-0 text-sm">
                        {{ $icon['emoji'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 truncate">{{ $log->title }}</p>
                        @if($log->description)
                        <p class="text-xs text-gray-400 truncate">{{ $log->description }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <p class="text-sm text-gray-400">Vera aún no ha registrado actividad</p>
                    <p class="text-xs text-gray-300 mt-1">Los emails procesados aparecerán aquí</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Canales activos -->
        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Canales de entrada</h2>
            <div class="grid grid-cols-3 gap-4">
                <!-- WhatsApp -->
                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.108.549 4.086 1.512 5.8L0 24l6.362-1.493A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.908 0-3.704-.516-5.25-1.418l-.376-.224-3.9.916.945-3.808-.244-.393A9.951 9.951 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-900">WhatsApp</p>
                        <p class="text-xs text-gray-500">{{ env('WHATSAPP_VERIFY_TOKEN') ? 'Configurado' : 'Pendiente' }}</p>
                    </div>
                </div>
                <!-- Telegram -->
                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-900">Telegram</p>
                        <p class="text-xs text-gray-500">{{ env('TELEGRAM_BOT_TOKEN') ? 'Configurado' : 'Pendiente' }}</p>
                    </div>
                </div>
                <!-- Google Calendar -->
                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50">
                    <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-900">Google Calendar</p>
                        <p class="text-xs {{ $config && $config->hasCalendarConnected() ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $config && $config->hasCalendarConnected() ? 'Conectado' : 'Sin conectar' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

@stack('scripts')
</body>
</html>
