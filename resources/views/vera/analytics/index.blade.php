<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Analytics - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
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

        .sidebar {
            transition: transform 0.2s ease;
        }

        @media (max-width: 768px) {
            .sidebar.hidden {
                transform: translateX(-100%);
            }
        }

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

        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                    <h1 class="text-sm font-semibold text-gray-900">Analytics</h1>
                    <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                    <span class="hidden sm:inline-block text-xs text-gray-500">Métricas y tendencias de Vera</span>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-6 max-w-[1400px] mx-auto">

                {{-- Comparativa Mes Actual vs Mes Anterior --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 fade-in">
                    {{-- Total Actividades --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Actividades</div>
                            @php
                                $change = $summary['comparison']['change']['activities'];
                            @endphp
                            @if ($change['direction'] === 'up')
                                <div class="flex items-center text-xs text-green-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @elseif($change['direction'] === 'down')
                                <div class="flex items-center text-xs text-red-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @endif
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ number_format($summary['comparison']['current_month']['activities']) }}</div>
                        <p class="text-xs text-gray-500 mt-1">Este mes</p>
                    </div>

                    {{-- Tokens --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</div>
                            @php
                                $change = $summary['comparison']['change']['tokens'];
                            @endphp
                            @if ($change['direction'] === 'up')
                                <div class="flex items-center text-xs text-green-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @elseif($change['direction'] === 'down')
                                <div class="flex items-center text-xs text-red-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @endif
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ number_format($summary['comparison']['current_month']['tokens']) }}</div>
                        <p class="text-xs text-gray-500 mt-1">Consumidos</p>
                    </div>

                    {{-- Costo --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Costo</div>
                            @php
                                $change = $summary['comparison']['change']['cost'];
                            @endphp
                            @if ($change['direction'] === 'up')
                                <div class="flex items-center text-xs text-red-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @elseif($change['direction'] === 'down')
                                <div class="flex items-center text-xs text-green-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @endif
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            ${{ number_format($summary['comparison']['current_month']['cost'], 2) }}</div>
                        <p class="text-xs text-gray-500 mt-1">Total</p>
                    </div>

                    {{-- Emails Respondidos --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Respuestas</div>
                            @php
                                $change = $summary['comparison']['change']['emails_replied'];
                            @endphp
                            @if ($change['direction'] === 'up')
                                <div class="flex items-center text-xs text-green-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @elseif($change['direction'] === 'down')
                                <div class="flex items-center text-xs text-red-600 font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ abs($change['percentage']) }}%
                                </div>
                            @endif
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ number_format($summary['comparison']['current_month']['emails_replied']) }}</div>
                        <p class="text-xs text-gray-500 mt-1">Generadas por IA</p>
                    </div>
                </div>

                {{-- Predicción de Consumo Mensual --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 fade-in">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Predicción de Consumo del Mes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Progreso del Mes</p>
                            <div class="text-2xl font-semibold text-gray-900 mb-2">
                                {{ $summary['prediction']['completion_percentage'] }}%</div>
                            <div class="relative h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-purple-500 rounded-full transition-all duration-500"
                                    style="width: {{ $summary['prediction']['completion_percentage'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">{{ $summary['prediction']['remaining_days'] }} días
                                restantes</p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 mb-1">Promedio Diario</p>
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ $summary['prediction']['daily_average']['emails'] }}</div>
                            <p class="text-xs text-gray-500 mt-1">emails / día</p>
                            <p class="text-xs text-gray-400 mt-2">
                                {{ number_format($summary['prediction']['daily_average']['tokens']) }} tokens / día
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 mb-1">Proyección Fin de Mes</p>
                            <div class="text-2xl font-semibold
                                @if ($summary['prediction']['will_exceed_limit']) text-red-600
                                @else text-green-600 @endif">
                                {{ $summary['prediction']['projected_end_of_month']['emails'] }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">emails proyectados</p>
                            @if ($summary['prediction']['will_exceed_limit'])
                                <p class="text-xs text-red-600 font-medium mt-2">
                                    ⚠️ Excederás tu límite de {{ $summary['prediction']['limit'] }} emails
                                </p>
                            @else
                                <p class="text-xs text-green-600 font-medium mt-2">
                                    ✓ Dentro del límite de {{ $summary['prediction']['limit'] }} emails
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Gráficos --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {{-- Tendencia de Actividad (últimos 30 días) --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 fade-in">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Tendencia de Actividad (30 días)</h3>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    {{-- Distribución por Tipo --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 fade-in">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Distribución por Tipo de Actividad</h3>
                        <div class="chart-container">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>

                    {{-- Costos por Día de la Semana --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 fade-in">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Actividad por Día de la Semana</h3>
                        <div class="chart-container">
                            <canvas id="dayOfWeekChart"></canvas>
                        </div>
                    </div>

                    {{-- Análisis de Feedback --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 fade-in">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Análisis de Feedback del Usuario</h3>
                        <div class="chart-container">
                            <canvas id="feedbackChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Top Categorías de Emails --}}
                @if (count($summary['top_categories']) > 0)
                    <div class="bg-white rounded-lg border border-gray-200 p-5 fade-in">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Top Categorías de Emails Procesados</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Categoría</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Total</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Tokens</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Confianza Promedio</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($summary['top_categories'] as $cat)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ $cat['category'] ?? 'Sin categoría' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $cat['total'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                {{ number_format($cat['tokens']) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                @if ($cat['avg_confidence'])
                                                    {{ round($cat['avg_confidence'] * 100) }}%
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </main>
    </div>

    <!-- Settings Modal -->
    @include('components.settings-modal')

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const target = event.target;

            if (window.innerWidth < 768 &&
                !sidebar.contains(target) &&
                !target.closest('[onclick="toggleSidebar()"]')) {
                sidebar.classList.add('hidden');
            }
        });

        // Configuración de Chart.js
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#6B7280';

        // GRÁFICO 1: Tendencia de Actividad
        const trendData = @json($summary['trends']);
        const trendChart = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendData.map(d => d.date_formatted),
                datasets: [{
                    label: 'Actividades',
                    data: trendData.map(d => d.total_activities),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // GRÁFICO 2: Distribución por Tipo
        const distributionData = @json($summary['distribution']);
        const distributionChart = new Chart(document.getElementById('distributionChart'), {
            type: 'doughnut',
            data: {
                labels: distributionData.map(d => d.type_label),
                datasets: [{
                    data: distributionData.map(d => d.count),
                    backgroundColor: [
                        '#8B5CF6',
                        '#10B981',
                        '#3B82F6',
                        '#F59E0B',
                        '#EC4899',
                        '#6366F1',
                        '#14B8A6',
                        '#F97316'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    }
                }
            }
        });

        // GRÁFICO 3: Costos por Día de la Semana
        const dayOfWeekData = @json($summary['cost_by_day']);
        const dayOfWeekChart = new Chart(document.getElementById('dayOfWeekChart'), {
            type: 'bar',
            data: {
                labels: dayOfWeekData.map(d => d.day),
                datasets: [{
                    label: 'Actividades',
                    data: dayOfWeekData.map(d => d.total),
                    backgroundColor: '#8B5CF6',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // GRÁFICO 4: Feedback Analysis
        const feedbackData = @json($summary['feedback']);
        const feedbackChart = new Chart(document.getElementById('feedbackChart'), {
            type: 'bar',
            data: {
                labels: feedbackData.map(d => d.label),
                datasets: [{
                    label: 'Cantidad',
                    data: feedbackData.map(d => d.count),
                    backgroundColor: [
                        '#10B981', // Aprobado - verde
                        '#EF4444', // Rechazado - rojo
                        '#F59E0B', // Modificado - amarillo
                        '#6B7280' // Pendiente - gris
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>
