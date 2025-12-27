<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vera Analytics - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="antialiased overflow-x-hidden">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-effect border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center glow-violet">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text-violet">Vera Analytics</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-gray-300 hover:text-white transition-colors">Dashboard</a>
                    <a href="{{ route('admin.vera.analytics.export') }}" class="button-secondary px-4 py-2 rounded-xl text-sm">
                        Exportar CSV
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="relative min-h-screen overflow-hidden grid-pattern pt-20">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-1/4 w-96 h-96 bg-violet-500/20 rounded-full blur-[128px] gradient-animate"></div>
            <div class="absolute bottom-1/4 -right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-[128px] gradient-animate" style="animation-delay: -5s;"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold mb-6">
                    <span class="gradient-text-violet">Analytics</span>
                    <span class="text-white"> de Vera</span>
                </h1>
                <p class="text-xl text-gray-400">
                    Métricas detalladas de consumo de tokens
                </p>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card-dark rounded-2xl p-6">
                    <div class="text-gray-400 text-sm mb-2">Total Tokens</div>
                    <div class="text-3xl font-bold text-white">{{ number_format($totalTokens) }}</div>
                </div>
                <div class="card-dark rounded-2xl p-6">
                    <div class="text-gray-400 text-sm mb-2">Total Requests</div>
                    <div class="text-3xl font-bold text-white">{{ number_format($totalRequests) }}</div>
                </div>
                <div class="card-dark rounded-2xl p-6">
                    <div class="text-gray-400 text-sm mb-2">Usuarios Activos</div>
                    <div class="text-3xl font-bold text-white">{{ $totalUsers }}</div>
                </div>
                <div class="card-dark rounded-2xl p-6">
                    <div class="text-gray-400 text-sm mb-2">Costo Total</div>
                    <div class="text-3xl font-bold text-white">${{ number_format($totalCost, 4) }}</div>
                </div>
            </div>

            <!-- Averages -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card-dark rounded-2xl p-6">
                    <div class="text-gray-400 text-sm mb-2">Promedio Tokens/Request</div>
                    <div class="text-2xl font-bold text-white">{{ number_format($avgTokensPerRequest, 2) }}</div>
                </div>
                <div class="card-dark rounded-2xl p-6">
                    <div class="text-gray-400 text-sm mb-2">Promedio Costo/Request</div>
                    <div class="text-2xl font-bold text-white">${{ number_format($avgCostPerRequest, 6) }}</div>
                </div>
            </div>

            <!-- By Action Type -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Consumo por Tipo de Acción</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left text-gray-400 py-3 px-4">Tipo</th>
                                <th class="text-right text-gray-400 py-3 px-4">Requests</th>
                                <th class="text-right text-gray-400 py-3 px-4">Tokens</th>
                                <th class="text-right text-gray-400 py-3 px-4">Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byActionType as $action)
                            <tr class="border-b border-white/5">
                                <td class="text-white py-3 px-4">
                                    <span class="px-3 py-1 bg-violet-500/20 text-violet-400 rounded-full text-sm">
                                        {{ $action->action_type }}
                                    </span>
                                </td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($action->count) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($action->tokens) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($action->tokens / $action->count, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Users -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Top 10 Usuarios por Consumo</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left text-gray-400 py-3 px-4">Usuario</th>
                                <th class="text-left text-gray-400 py-3 px-4">Plan</th>
                                <th class="text-right text-gray-400 py-3 px-4">Tokens</th>
                                <th class="text-right text-gray-400 py-3 px-4">Requests</th>
                                <th class="text-right text-gray-400 py-3 px-4">Avg/Request</th>
                                <th class="text-right text-gray-400 py-3 px-4">Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topUsers as $user)
                            <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                <td class="text-white py-3 px-4">
                                    <div>{{ $user['user_name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $user['user_email'] }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded text-sm">
                                        {{ ucfirst($user['plan_type']) }}
                                    </span>
                                </td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($user['total_tokens']) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($user['requests']) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($user['avg_tokens_per_request'], 2) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">${{ number_format($user['total_cost'], 4) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Last 30 Days Chart Data -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Consumo Últimos 30 Días</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left text-gray-400 py-3 px-4">Fecha</th>
                                <th class="text-right text-gray-400 py-3 px-4">Tokens</th>
                                <th class="text-right text-gray-400 py-3 px-4">Requests</th>
                                <th class="text-right text-gray-400 py-3 px-4">Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($last30Days as $day)
                            <tr class="border-b border-white/5">
                                <td class="text-white py-3 px-4">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($day->tokens) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($day->requests) }}</td>
                                <td class="text-gray-300 text-right py-3 px-4">{{ number_format($day->tokens / $day->requests, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- By Model -->
            <div class="card-dark rounded-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Uso por Modelo de IA</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($byModel as $model)
                    <div class="bg-gray-800/50 rounded-xl p-4">
                        <div class="text-gray-400 text-sm mb-1">{{ $model->model }}</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($model->tokens) }}</div>
                        <div class="text-sm text-gray-500">{{ number_format($model->count) }} requests</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Back Link -->
            <div class="text-center">
                <a href="{{ route('dashboard') }}" class="text-violet-400 hover:text-violet-300 transition-colors inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </section>

</body>
</html>
