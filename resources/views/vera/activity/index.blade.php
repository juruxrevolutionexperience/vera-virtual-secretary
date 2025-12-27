<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Actividad de Vera - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Custom scrollbar */
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

        /* Tooltip */
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
            padding: 6px 10px;
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

        /* Badge pulse */
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
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('components.dashboard-sidebar')

        <main class="flex-1 md:ml-56">
            <!-- Top Bar -->
            <header
                class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 backdrop-blur-sm bg-white/80">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()"
                        class="md:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-sm font-semibold text-gray-900">Actividad de Vera</h1>
                    <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                    <span class="hidden sm:inline-block text-xs text-gray-500">Registro completo de acciones</span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Export Button -->
                    <a href="{{ route('vera.activity.export', request()->query()) }}"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md border border-gray-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Exportar CSV</span>
                    </a>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-6 max-w-[1400px] mx-auto">

                {{-- Estadísticas Principales --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 fade-in">
                    {{-- Total Actividades --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Actividades
                            </div>
                            <div class="w-9 h-9 bg-purple-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_activities'] }}</div>
                        <p class="text-xs text-gray-500 mt-1">Este mes</p>
                    </div>

                    {{-- Tokens Consumidos --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</div>
                            <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_tokens']) }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Consumidos</p>
                    </div>

                    {{-- Emails Respondidos --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Respuestas</div>
                            <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['emails_replied'] }}</div>
                        <p class="text-xs text-gray-500 mt-1">Generadas por IA</p>
                    </div>

                    {{-- Confianza Promedio --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Confianza</div>
                            <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">{{ round($stats['avg_confidence'] * 100) }}%
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Promedio</p>
                    </div>
                </div>

                {{-- Widget de Uso del Plan --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Uso del Plan</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Plan {{ ucfirst($currentUsage['plan_type']) }}</p>
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full
                            @if($alertLevel === 'red') bg-red-100 text-red-700
                            @elseif($alertLevel === 'yellow') bg-yellow-100 text-yellow-700
                            @elseif($alertLevel === 'blue') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ $currentUsage['emails_processed'] }} / {{ $currentUsage['email_limit'] }} emails
                        </span>
                    </div>
                    <div class="relative h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500
                            @if($alertLevel === 'red') bg-red-500
                            @elseif($alertLevel === 'yellow') bg-yellow-500
                            @elseif($alertLevel === 'blue') bg-blue-500
                            @else bg-gray-300 @endif"
                            style="width: {{ $currentUsage['usage_percentage'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <p class="text-xs text-gray-500">
                            {{ $currentUsage['emails_remaining'] }} emails restantes
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ round($currentUsage['usage_percentage']) }}% usado
                        </p>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 fade-in">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Filtros</h3>
                    <form method="GET" action="{{ route('vera.activity.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        {{-- Tipo de Actividad --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Tipo</label>
                            <select name="type"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Todos</option>
                                <option value="email_processed" {{ request('type') == 'email_processed' ? 'selected' : '' }}>
                                    Procesados</option>
                                <option value="email_replied" {{ request('type') == 'email_replied' ? 'selected' : '' }}>
                                    Respondidos</option>
                                <option value="email_archived" {{ request('type') == 'email_archived' ? 'selected' : '' }}>
                                    Archivados</option>
                                <option value="email_categorized" {{ request('type') == 'email_categorized' ? 'selected' : '' }}>Categorizados</option>
                                <option value="chat_interaction" {{ request('type') == 'chat_interaction' ? 'selected' : '' }}>Chat</option>
                                <option value="limit_reached" {{ request('type') == 'limit_reached' ? 'selected' : '' }}>
                                    Límites</option>
                            </select>
                        </div>

                        {{-- Feedback --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Feedback</label>
                            <select name="feedback"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Todos</option>
                                <option value="approved" {{ request('feedback') == 'approved' ? 'selected' : '' }}>Aprobado
                                </option>
                                <option value="rejected" {{ request('feedback') == 'rejected' ? 'selected' : '' }}>Rechazado
                                </option>
                                <option value="modified" {{ request('feedback') == 'modified' ? 'selected' : '' }}>Modificado
                                </option>
                                <option value="pending" {{ request('feedback') == 'pending' ? 'selected' : '' }}>Pendiente
                                </option>
                            </select>
                        </div>

                        {{-- Confianza Mínima --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Confianza Mínima</label>
                            <select name="confidence"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Todas</option>
                                <option value="0.9" {{ request('confidence') == '0.9' ? 'selected' : '' }}>≥ 90%</option>
                                <option value="0.75" {{ request('confidence') == '0.75' ? 'selected' : '' }}>≥ 75%
                                </option>
                                <option value="0.5" {{ request('confidence') == '0.5' ? 'selected' : '' }}>≥ 50%</option>
                            </select>
                        </div>

                        {{-- Fecha Desde --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Desde</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>

                        {{-- Fecha Hasta --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Hasta</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>

                        {{-- Botones --}}
                        <div class="md:col-span-5 flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                                Aplicar Filtros
                            </button>
                            <a href="{{ route('vera.activity.index') }}"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                {{-- Timeline de Actividades --}}
                <div class="bg-white rounded-lg border border-gray-200 fade-in">
                    <div class="p-5 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Registro de Actividades</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $activities->total() }} actividades encontradas</p>
                    </div>

                    @if ($activities->count() > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach ($activities as $activity)
                                <div class="p-5 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start gap-4">
                                        {{-- Icon --}}
                                        <div class="w-10 h-10 flex-shrink-0 rounded-lg flex items-center justify-center text-lg
                                            @if ($activity->activity_type === 'email_processed') bg-purple-50
                                            @elseif($activity->activity_type === 'email_replied') bg-green-50
                                            @elseif($activity->activity_type === 'email_archived') bg-blue-50
                                            @elseif($activity->activity_type === 'email_categorized') bg-amber-50
                                            @elseif($activity->activity_type === 'chat_interaction') bg-pink-50
                                            @else bg-gray-50 @endif">
                                            {{ $activity->activity_icon }}
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-4 mb-1">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">{{ $activity->title }}</h4>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $activity->description }}</p>
                                                </div>
                                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </span>
                                            </div>

                                            {{-- Metadata --}}
                                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                                {{-- Tokens --}}
                                                @if ($activity->tokens_consumed > 0)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                        </svg>
                                                        {{ number_format($activity->tokens_consumed) }} tokens
                                                    </span>
                                                @endif

                                                {{-- Confianza --}}
                                                @if ($activity->confidence_score)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        {{ $activity->confidence_badge_color }}">
                                                        {{ round($activity->confidence_score * 100) }}% confianza
                                                    </span>
                                                @endif

                                                {{-- Feedback --}}
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    @if ($activity->user_feedback === 'approved') bg-green-50 text-green-700
                                                    @elseif($activity->user_feedback === 'rejected') bg-red-50 text-red-700
                                                    @elseif($activity->user_feedback === 'modified') bg-amber-50 text-amber-700
                                                    @else bg-gray-50 text-gray-600 @endif">
                                                    @if ($activity->user_feedback === 'approved')
                                                        ✓ Aprobado
                                                    @elseif($activity->user_feedback === 'rejected')
                                                        ✗ Rechazado
                                                    @elseif($activity->user_feedback === 'modified')
                                                        ✎ Modificado
                                                    @else
                                                        ⋯ Pendiente
                                                    @endif
                                                </span>

                                                {{-- Cuenta en límite --}}
                                                @if ($activity->counts_towards_limit)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                                                        Cuenta en límite
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="p-5 border-t border-gray-200">
                            {{ $activities->links() }}
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-1">No hay actividades</h3>
                            <p class="text-xs text-gray-500 mb-4">No se encontraron actividades con los filtros aplicados
                            </p>
                            @if (request()->hasAny(['type', 'feedback', 'confidence', 'date_from', 'date_to']))
                                <a href="{{ route('vera.activity.index') }}"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 hover:text-purple-700">
                                    Limpiar filtros
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const target = event.target;

            // Check if click is outside sidebar and not on toggle button
            if (window.innerWidth < 768 &&
                !sidebar.contains(target) &&
                !target.closest('[onclick="toggleSidebar()"]')) {
                sidebar.classList.add('hidden');
            }
        });
    </script>

</body>

</html>
