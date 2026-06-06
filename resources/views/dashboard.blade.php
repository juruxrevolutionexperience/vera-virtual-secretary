<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <!-- Alpine.js CDN (solo para este template) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Custom scrollbar - Linear style */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }

        /* Sidebar animation */
        .sidebar {
            transition: transform 0.2s ease;
        }

        @media (max-width: 768px) {
            .sidebar.hidden {
                transform: translateX(-100%);
            }
        }

        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-duration: 150ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hover lift effect */
        .hover-lift {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Skeleton loading animation */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }

            100% {
                background-position: 1000px 0;
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }

        /* Tooltip styles */
        .tooltip {
            position: relative;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            z-index: 100;
            background-color: #1f2937;
            color: white;
            text-align: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) translateY(4px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }

        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1f2937 transparent transparent transparent;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Badge pulse animation */
        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                opacity: 1;
            }

            50% {
                transform: scale(1.05);
                opacity: 0.7;
            }

            100% {
                transform: scale(0.95);
                opacity: 1;
            }
        }

        .badge-pulse {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Search modal */
        .search-modal {
            backdrop-filter: blur(8px);
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Fade in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        /* Stat number animation */
        .stat-number {
            font-variant-numeric: tabular-nums;
        }

        /* Activity item hover */
        .activity-item {
            position: relative;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #8b5cf6, #6366f1);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .activity-item:hover::before {
            opacity: 1;
        }

        /* Quick action buttons */
        .quick-action {
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.2s;
        }

        .activity-item:hover .quick-action {
            opacity: 1;
            transform: scale(1);
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid #8b5cf6;
            outline-offset: 2px;
        }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

    <div class="flex min-h-screen overflow-x-hidden">
        @include('components.dashboard-sidebar')
        <main class="flex-1 min-w-0 lg:ml-56">
            <!-- Top Bar - Linear Style Enhanced -->
            <header
                class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 backdrop-blur-sm bg-white/80">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()"
                        class="lg:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-sm font-semibold text-gray-900">Inicio</h1>
                    <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                    <span class="hidden sm:inline-block text-xs text-gray-500">Última actualización: hace 2 min</span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Keyboard Shortcut Hint -->
                    <button onclick="openSearch()"
                        class="hidden lg:flex items-center gap-2 px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Buscar</span>
                        <kbd class="bg-gray-200 px-1.5 py-0.5 rounded text-[10px] font-semibold">⌘K</kbd>
                    </button>

                    <!-- Notification Bell -->
                    <button
                        class="relative p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md tooltip">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full ring-2 ring-white"></span>
                        <span class="tooltip-text">3 notificaciones</span>
                    </button>
                </div>
            </header>

            <!-- Dashboard Content - Enhanced -->
            <div class="p-6 max-w-[1400px] mx-auto">
                <!-- Welcome Section -->
                <div class="mb-8 fade-in">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="text-2xl font-semibold text-gray-900">Hola, {{ auth()->user()->name }}</h2>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 text-green-700 text-xs font-medium rounded-full">
                                <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                                Todas las tareas al día
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Vera ha estado trabajando para ti. Aquí está el resumen de hoy.</p>
                </div>

                <!-- Stats Grid - Enhanced -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    <!-- Stat Card 1 - Enhanced -->
                    <div class="bg-white border border-gray-200 rounded-lg p-5 hover-lift cursor-pointer group">
                        <div class="flex items-center justify-between mb-3">
                            <div
                                class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                                <svg class="w-4 h-4 text-gray-600 group-hover:text-purple-600 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full font-medium">Este mes</span>
                        </div>
                        <p class="text-3xl font-semibold text-gray-900 mb-1 stat-number">{{ $meetingsThisMonth }}</p>
                        <p class="text-sm text-gray-500 mb-2">Reuniones este mes</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-purple-600 font-medium bg-purple-50 px-2 py-0.5 rounded-full">Confirmadas</span>
                        </div>
                    </div>

                    <!-- Stat Card 2 - Enhanced -->
                    <div class="bg-white border border-gray-200 rounded-lg p-5 hover-lift cursor-pointer group">
                        <div class="flex items-center justify-between mb-3">
                            <div
                                class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                                <svg class="w-4 h-4 text-gray-600 group-hover:text-blue-600 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full font-medium">Esta
                                semana</span>
                        </div>
                        <p class="text-3xl font-semibold text-gray-900 mb-1 stat-number">{{ $meetingsThisWeek }}</p>
                        <p class="text-sm text-gray-500 mb-2">Reuniones esta semana</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-blue-600 font-medium bg-blue-50 px-2 py-0.5 rounded-full">Confirmadas</span>
                        </div>
                    </div>

                    <!-- Stat Card 3 - Tokens IA (datos reales) -->
                    <x-tokens-widget />
                </div>

                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column - Activity Feed (2/3) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Aviso: cosas que requieren tu atención → se gestionan en la Bandeja -->
                        @php
                            $numInc = isset($escalations) ? $escalations->count() : 0;
                            $numPend = $pendingMeetings->count();
                            $numTotal = $numInc + $numPend;
                        @endphp
                        @if($numTotal > 0)
                        <a href="{{ route('vera.dashboard') }}" class="block bg-white border {{ $numInc > 0 ? 'border-red-200' : 'border-amber-200' }} rounded-lg px-5 py-4 hover:shadow-sm transition-shadow">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg {{ $numInc > 0 ? 'bg-red-100' : 'bg-amber-100' }}">
                                        <svg class="w-5 h-5 {{ $numInc > 0 ? 'text-red-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Requiere tu atención</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            @if($numInc > 0)<span class="text-red-600 font-medium">{{ $numInc }} incidencia{{ $numInc > 1 ? 's' : '' }}</span>@endif
                                            @if($numInc > 0 && $numPend > 0) · @endif
                                            @if($numPend > 0)<span class="text-amber-600 font-medium">{{ $numPend }} cita{{ $numPend > 1 ? 's' : '' }} por confirmar</span>@endif
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-medium text-purple-600">Ir a la Bandeja →</span>
                            </div>
                        </a>
                        @endif

                        <!-- Próximas reuniones -->
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Próximas reuniones</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Confirmadas por Vera</p>
                                </div>
                                <a href="{{ route('vera.dashboard') }}" class="text-xs text-purple-600 hover:text-purple-700 font-medium">Ver todas →</a>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @forelse($upcomingMeetings as $meeting)
                                <div class="px-5 py-3.5 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                                    <div class="w-10 text-center flex-shrink-0">
                                        <p class="text-xs font-bold text-purple-600 uppercase">{{ $meeting->scheduled_at->format('M') }}</p>
                                        <p class="text-xl font-bold text-gray-900 leading-tight">{{ $meeting->scheduled_at->format('d') }}</p>
                                    </div>
                                    <div class="w-px h-10 bg-gray-200 flex-shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $meeting->contact_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $meeting->scheduled_at->format('H:i') }} · {{ $meeting->duration_minutes }} min
                                            @if($meeting->contact_email) · {{ $meeting->contact_email }}@endif
                                        </p>
                                    </div>
                                    @if($meeting->google_meet_link)
                                    <a href="{{ $meeting->google_meet_link }}" target="_blank"
                                       class="flex-shrink-0 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                                        Meet
                                    </a>
                                    @else
                                    <span class="flex-shrink-0 px-3 py-1.5 bg-green-50 text-green-700 text-xs font-medium rounded-lg">
                                        Confirmada
                                    </span>
                                    @endif
                                </div>
                                @empty
                                <div class="px-5 py-8 text-center">
                                    <svg class="w-9 h-9 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm text-gray-400">Sin reuniones próximas</p>
                                    <p class="text-xs text-gray-300 mt-0.5">Vera las añadirá cuando las confirme</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Activity Feed - Enhanced -->
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Actividad Reciente</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Últimas acciones de Vera</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        class="text-xs text-gray-500 hover:text-gray-700 font-medium px-2 py-1 hover:bg-gray-100 rounded-md">
                                        Filtrar
                                    </button>
                                    <a href="#"
                                        class="text-xs text-gray-500 hover:text-gray-700 font-medium px-2 py-1 hover:bg-gray-100 rounded-md">
                                        Ver todo →
                                    </a>
                                </div>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @forelse($recentActivity as $activity)
                                @php $esQueja = ($activity->activity_type ?? '') === 'escalation'; @endphp
                                <div class="activity-item px-5 py-4 transition-colors cursor-pointer {{ $esQueja ? 'bg-red-50 hover:bg-red-100 border-l-4 border-red-400' : 'hover:bg-gray-50' }}">
                                    <div class="flex gap-3">
                                        <div class="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0 {{ $esQueja ? 'bg-red-100' : 'bg-purple-100' }}">
                                            @if($esQueja)
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/>
                                            </svg>
                                            @else
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium {{ $esQueja ? 'text-red-900' : 'text-gray-900' }}">{{ $activity->title ?? 'Acción de Vera' }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($activity->description ?? '', 80) }}</p>
                                            <p class="text-xs text-gray-400 mt-1.5">{{ $activity->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="px-5 py-10 text-center">
                                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-sm text-gray-400 font-medium">Sin actividad aún</p>
                                    <p class="text-xs text-gray-300 mt-1">Vera registrará sus acciones aquí</p>
                                </div>
                                @endforelse

                                {{-- Items hardcodeados eliminados --}}
                                @if(false)
                                <!-- Activity Item 1 - Enhanced -->
                                <div class="activity-item px-5 py-4 hover:bg-gray-50 transition-colors cursor-pointer">
                                    <div class="flex gap-3">
                                        <div
                                            class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">Email organizado en
                                                        "Proyectos Urgentes"</p>
                                                    <p class="text-xs text-gray-500 mt-1">De: cliente@example.com -
                                                        "Propuesta Q4 2025"</p>
                                                </div>
                                                <div class="flex items-center gap-1 ml-2 quick-action">
                                                    <button class="p-1 hover:bg-gray-200 rounded tooltip">
                                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <span class="tooltip-text">Marcar como leído</span>
                                                    </button>
                                                    <button class="p-1 hover:bg-gray-200 rounded tooltip">
                                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                        </svg>
                                                        <span class="tooltip-text">Archivar</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 mt-2">
                                                <p class="text-xs text-gray-400">Hace 5 minutos</p>
                                                <span class="text-xs text-gray-300">•</span>
                                                <span
                                                    class="inline-flex items-center text-xs text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full font-medium">
                                                    Alta prioridad
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Item 2 - Enhanced -->
                                <div class="activity-item px-5 py-4 hover:bg-gray-50 transition-colors cursor-pointer">
                                    <div class="flex gap-3">
                                        <div
                                            class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">Reunión agendada
                                                        exitosamente</p>
                                                    <p class="text-xs text-gray-500 mt-1">Mañana 10:00 AM - "Revisión de
                                                        diseño"</p>
                                                </div>
                                                <div class="flex items-center gap-1 ml-2 quick-action">
                                                    <button class="p-1 hover:bg-gray-200 rounded tooltip">
                                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                        <span class="tooltip-text">Editar</span>
                                                    </button>
                                                    <button class="p-1 hover:bg-gray-200 rounded tooltip">
                                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                                        </svg>
                                                        <span class="tooltip-text">Compartir</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 mt-2">
                                                <p class="text-xs text-gray-400">Hace 23 minutos</p>
                                                <span class="text-xs text-gray-300">•</span>
                                                <span
                                                    class="inline-flex items-center text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-medium">
                                                    ✓ Confirmado
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Item 3 - Enhanced -->
                                <div class="activity-item px-5 py-4 hover:bg-gray-50 transition-colors cursor-pointer">
                                    <div class="flex gap-3">
                                        <div
                                            class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">Llamada contestada y
                                                        grabada</p>
                                                    <p class="text-xs text-gray-500 mt-1">Duración: 3:45 min - Proveedor
                                                        XYZ</p>
                                                </div>
                                                <div class="flex items-center gap-1 ml-2 quick-action">
                                                    <button class="p-1 hover:bg-gray-200 rounded tooltip">
                                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="tooltip-text">Reproducir grabación</span>
                                                    </button>
                                                    <button class="p-1 hover:bg-gray-200 rounded tooltip">
                                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        <span class="tooltip-text">Descargar</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 mt-2">
                                                <p class="text-xs text-gray-400">Hace 1 hora</p>
                                                <span class="text-xs text-gray-300">•</span>
                                                <span
                                                    class="inline-flex items-center text-xs text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full font-medium">
                                                    Transcrita
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Item 4 - Enhanced with Action -->
                                <div class="activity-item px-5 py-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex gap-3 items-start">
                                        <div
                                            class="w-8 h-8 bg-amber-100 rounded-md flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900">Solicitó tu aprobación</p>
                                            <p class="text-xs text-gray-500 mt-1">Reprogramar "Junta Directiva" al
                                                jueves 15:00</p>
                                            <div class="flex items-center gap-2 mt-3">
                                                <button
                                                    class="px-3 py-1.5 bg-gray-900 hover:bg-gray-800 text-white text-xs font-medium rounded-md transition-colors">
                                                    Aprobar
                                                </button>
                                                <button
                                                    class="px-3 py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-medium rounded-md transition-colors">
                                                    Rechazar
                                                </button>
                                                <p class="text-xs text-gray-400 ml-auto">Hace 2 horas</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Sidebar Widgets (1/3) -->
                    <div class="lg:col-span-1 space-y-6" x-data="usageWidget()">
                        <!-- Vera Weekly Insights -->
                        <x-vera-weekly-insights />

                        <!-- Usage Widget - NEW -->
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 border-2 border-purple-200 rounded-lg p-5 hover-lift cursor-pointer"
                             @click="openUsageModal()">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-900">Uso de Vera</h3>
                                </div>
                                <span x-text="statusBadge"
                                      :class="`text-xs px-2 py-0.5 rounded-full font-semibold ${statusBadgeClass}`"></span>
                            </div>

                            <!-- Loading State -->
                            <div x-show="loading" class="flex justify-center py-4">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
                            </div>

                            <!-- Content -->
                            <div x-show="!loading" class="space-y-3">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-xs text-gray-600">Este mes</span>
                                    <span class="text-2xl font-bold text-gray-900" x-text="`${emailsProcessed}/${emailLimit}`"></span>
                                </div>

                                <!-- Progress Bar -->
                                <div>
                                    <div class="w-full bg-white rounded-full h-2.5 overflow-hidden shadow-inner">
                                        <div class="h-full transition-all duration-500"
                                             :class="progressBarColor"
                                             :style="`width: ${Math.min(usagePercentage, 100)}%`"></div>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs text-gray-600" x-text="`${usagePercentage}% utilizado`"></span>
                                        <span class="text-xs font-semibold"
                                              :class="emailsRemaining < 10 ? 'text-red-600' : 'text-gray-700'"
                                              x-text="`${emailsRemaining} restantes`"></span>
                                    </div>
                                </div>

                                <!-- CTA -->
                                <button class="w-full mt-3 text-xs text-purple-700 hover:text-purple-800 font-medium flex items-center justify-center gap-1 py-2 hover:bg-purple-100 rounded-md transition-colors">
                                    <span>Ver detalles</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Chat Widget - Enhanced -->
                        <div class="bg-white border border-gray-200 rounded-lg p-5 hover-lift">
                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <span class="text-white font-semibold text-sm">V</span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Chat con Vera</h3>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                                        <p class="text-xs text-gray-500">Online · Responde en segundos</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 mb-4 leading-relaxed">Pregúntame lo que necesites. Puedo
                                ayudarte a buscar emails, agendar reuniones o consultar tu agenda.</p>
                            <button
                                class="w-full bg-gray-900 hover:bg-gray-800 text-white text-xs font-medium py-2.5 px-3 rounded-md transition-all hover:shadow-md flex items-center justify-center gap-2 group">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Abrir chat
                            </button>
                        </div>

                        <!-- Próximos Eventos eliminado — datos fake reemplazados por reuniones reales en columna izquierda -->
                        @if(false)
                        <div class="bg-white border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-900">Próximos Eventos</h3>
                                <button class="text-xs text-gray-500 hover:text-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="space-y-3">
                                <!-- Event 1 - Enhanced -->
                                <div
                                    class="border-l-2 border-purple-500 pl-3 py-2 hover:bg-purple-50 rounded-r transition-colors cursor-pointer group">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 group-hover:text-purple-700">
                                                Reunión con equipo</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Hoy 14:00 - 15:00</p>
                                            <span
                                                class="inline-flex items-center text-xs text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full font-medium mt-2">
                                                En 2 horas
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Event 2 - Enhanced -->
                                <div
                                    class="border-l-2 border-blue-500 pl-3 py-2 hover:bg-blue-50 rounded-r transition-colors cursor-pointer group">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">
                                                Revisión de diseño</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Mañana 10:00 - 11:30</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Event 3 - Enhanced -->
                                <div
                                    class="border-l-2 border-green-500 pl-3 py-2 hover:bg-green-50 rounded-r transition-colors cursor-pointer group">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 group-hover:text-green-700">
                                                Llamada con cliente</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Vie 09:00 - 09:45</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="w-full mt-4 text-center text-xs text-gray-500 hover:text-gray-700 font-medium py-2 hover:bg-gray-50 rounded-md transition-colors">
                                Ver calendario completo →
                            </button>
                        </div>

                        @endif
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" class="fixed inset-0 z-50 hidden search-modal" onclick="closeSearch(event)">
        <div class="flex items-start justify-center min-h-screen pt-20 px-4">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl" onclick="event.stopPropagation()">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchInput" placeholder="Buscar en Vera..."
                            class="flex-1 text-sm outline-none" autofocus>
                        <kbd class="text-xs bg-gray-100 px-2 py-1 rounded">ESC</kbd>
                    </div>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div class="text-xs text-gray-500 mb-3">Sugerencias</div>
                    <div class="space-y-1">
                        <a href="#"
                            class="block px-3 py-2 hover:bg-gray-50 rounded-md text-sm text-gray-700 flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Buscar en emails
                        </a>
                        <a href="#"
                            class="block px-3 py-2 hover:bg-gray-50 rounded-md text-sm text-gray-700 flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Ver calendario
                        </a>
                        <a href="#"
                            class="block px-3 py-2 hover:bg-gray-50 rounded-md text-sm text-gray-700 flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Abrir chat con Vera
                        </a>
                    </div>
                </div>
                <div
                    class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-xs text-gray-500 flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <kbd class="bg-white px-1.5 py-0.5 rounded border border-gray-200">↑</kbd>
                        <kbd class="bg-white px-1.5 py-0.5 rounded border border-gray-200">↓</kbd>
                        navegar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="bg-white px-1.5 py-0.5 rounded border border-gray-200">↵</kbd>
                        seleccionar
                    </span>
                </div>
            </div>
        </div>
    </div>


    <!-- Settings Modal (Estilo OpenAI) -->
    @include('components.settings-modal')

    <script>
        // Search modal
        function openSearch() {
            const modal = document.getElementById('searchModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('searchInput').focus();
            }, 100);
        }

        function closeSearch(event) {
            if (event && event.target.id !== 'searchModal') return;
            const modal = document.getElementById('searchModal');
            modal.classList.add('hidden');
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // CMD+K or CTRL+K to open search
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                openSearch();
            }
            // ESC to close search
            if (e.key === 'Escape') {
                closeSearch({ target: { id: 'searchModal' } });
            }
        });

        // Add fade-in animation to elements on load
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.3s ease-out';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 50);
            });
        });

        // La lógica del modal de Vera ahora está en /emails

        // Usage Widget Alpine Component
        function usageWidget() {
            return {
                loading: true,
                emailsProcessed: 0,
                emailLimit: 100,
                emailsRemaining: 100,
                usagePercentage: 0,
                alertLevel: 'normal',

                init() {
                    this.loadUsageData();
                },

                get statusBadge() {
                    if (this.alertLevel === 'blocked') return 'Bloqueado';
                    if (this.alertLevel === 'critical') return 'Crítico';
                    if (this.alertLevel === 'warning') return 'Alerta';
                    if (this.alertLevel === 'info') return 'Info';
                    return 'Normal';
                },

                get statusBadgeClass() {
                    if (this.alertLevel === 'blocked') return 'bg-red-100 text-red-800';
                    if (this.alertLevel === 'critical') return 'bg-orange-100 text-orange-800';
                    if (this.alertLevel === 'warning') return 'bg-yellow-100 text-yellow-800';
                    if (this.alertLevel === 'info') return 'bg-blue-100 text-blue-800';
                    return 'bg-green-100 text-green-800';
                },

                get progressBarColor() {
                    if (this.usagePercentage >= 90) return 'bg-red-500';
                    if (this.usagePercentage >= 75) return 'bg-orange-500';
                    if (this.usagePercentage >= 50) return 'bg-yellow-500';
                    return 'bg-green-500';
                },

                async loadUsageData() {
                    try {
                        const response = await fetch('/settings/usage/current', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) throw new Error('Error loading usage');

                        const data = await response.json();

                        this.emailsProcessed = data.usage?.emails_processed || 0;
                        this.emailLimit = data.usage?.email_limit || 100;
                        this.emailsRemaining = data.usage?.emails_remaining || 100;
                        this.usagePercentage = data.usage?.usage_percentage || 0;
                        this.alertLevel = data.alert_level || 'normal';
                        this.loading = false;
                    } catch (error) {
                        console.error('Error loading usage:', error);
                        this.loading = false;
                    }
                },

                openUsageModal() {
                    // Abrir modal de settings en la pestaña de Uso
                    window.dispatchEvent(new CustomEvent('open-settings-modal'));
                    // Esperar un poco y cambiar a la pestaña de uso
                    setTimeout(() => {
                        // Intentar cambiar la categoría activa del modal
                        const event = new CustomEvent('switch-settings-tab', {
                            detail: { category: 'usage' }
                        });
                        window.dispatchEvent(event);
                    }, 100);
                }
            };
        }
    </script>
</body>

</html>