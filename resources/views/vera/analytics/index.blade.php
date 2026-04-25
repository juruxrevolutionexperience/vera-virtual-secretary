<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analítica - Vera AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
        .sidebar { transition: transform 0.2s ease; }
        @media (max-width: 768px) { .sidebar.hidden { transform: translateX(-100%); } }
    </style>
</head>
<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

<div class="flex min-h-screen">
    @include('components.dashboard-sidebar')

    <main class="flex-1 lg:ml-56">
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center px-6">
            <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100 mr-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-sm font-semibold text-gray-900">Analítica</h1>
        </header>

        <div class="p-6 max-w-3xl mx-auto">

            {{-- Resumen del mes --}}
            @php
                $comparison  = $summary['comparison'] ?? [];
                $current     = $comparison['current_month'] ?? [];
                $prediction  = $summary['prediction'] ?? [];
                $feedback    = $summary['feedback'] ?? [];

                $meetings    = $current['meetings_confirmed'] ?? 0;
                $activities  = $current['activities'] ?? 0;
                $hoursSaved  = round($meetings * 0.5, 1);

                $approvedItem = collect($feedback)->firstWhere('feedback', 'approved');
                $approvalRate = $approvedItem['percentage'] ?? 0;
            @endphp

            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Resumen de este mes</h2>
                <p class="text-sm text-gray-500">{{ now()->translatedFormat('F Y') }}</p>
            </div>

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $meetings }}</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reuniones confirmadas</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-1">{{ $hoursSaved }}h</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempo ahorrado</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-1">{{ $approvalRate }}%</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Respuestas aprobadas</div>
                </div>
            </div>

            {{-- Actividad por tipo --}}
            @if(!empty($summary['distribution']))
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Actividad por tipo</h3>
                <div class="space-y-3">
                    @foreach($summary['distribution'] as $item)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-700">{{ $item['type_label'] }}</span>
                            <span class="text-xs font-medium text-gray-900">{{ $item['count'] }} <span class="text-gray-400 font-normal">({{ $item['percentage'] }}%)</span></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ $item['percentage'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Feedback --}}
            @if(!empty($feedback))
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Valoraciones de las respuestas</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($feedback as $item)
                    @php
                        $color = match($item['feedback']) {
                            'approved' => 'bg-green-50 text-green-700',
                            'rejected' => 'bg-red-50 text-red-700',
                            'modified' => 'bg-amber-50 text-amber-700',
                            default    => 'bg-gray-50 text-gray-600',
                        };
                    @endphp
                    <div class="rounded-lg p-4 text-center {{ $color }}">
                        <div class="text-2xl font-bold mb-1">{{ $item['count'] }}</div>
                        <div class="text-xs font-medium">{{ $item['label'] }}</div>
                        <div class="text-xs opacity-70 mt-0.5">{{ $item['percentage'] }}%</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Proyección del mes --}}
            @if(!empty($prediction))
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Proyección a fin de mes</h3>
                <p class="text-xs text-gray-500 mb-4">Mes completado al {{ $prediction['completion_percentage'] ?? 0 }}% · {{ $prediction['remaining_days'] ?? 0 }} días restantes</p>
                <div class="flex items-center gap-6">
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $prediction['projected_end_of_month']['meetings'] ?? 0 }}</div>
                        <div class="text-xs text-gray-500">reuniones estimadas</div>
                    </div>
                    @if(($prediction['will_exceed_limit'] ?? false) && ($prediction['limit'] ?? 0) < 999999)
                    <div class="flex-1 p-3 bg-amber-50 rounded-lg">
                        <p class="text-xs text-amber-700 font-medium">⚠ A este ritmo superarás el límite de tu plan este mes.</p>
                        <a href="{{ route('billing') }}" class="text-xs text-amber-700 underline mt-1 inline-block">Ver opciones de plan</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Estado vacío --}}
            @if($activities === 0)
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Sin datos todavía</h3>
                <p class="text-xs text-gray-500">Las estadísticas aparecerán aquí en cuanto Vera empiece a gestionar reuniones.</p>
            </div>
            @endif

        </div>
    </main>
</div>

@include('components.settings-modal')

<script>
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('hidden'); }
    document.addEventListener('click', function(e) {
        const s = document.getElementById('sidebar');
        if (window.innerWidth < 768 && !s.contains(e.target) && !e.target.closest('[onclick="toggleSidebar()"]')) s.classList.add('hidden');
    });
</script>
</body>
</html>
