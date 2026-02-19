<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tokens IA · Análisis de costes - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
        .sidebar { transition: transform 0.2s ease; }
        @media (max-width: 768px) { .sidebar.hidden { transform: translateX(-100%); } }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">
<div class="flex min-h-screen">
    @include('components.dashboard-sidebar')

    <main class="flex-1 md:ml-56">

        {{-- ── TOP BAR ──────────────────────────────────────────────────────── --}}
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 bg-white/90 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-sm font-semibold text-gray-900">Tokens IA</h1>
                <span class="hidden sm:block text-xs text-gray-400">·</span>
                <span class="hidden sm:block text-xs text-gray-500">Análisis de costes y pricing de planes</span>
                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Dev tools</span>
            </div>

            <form method="GET" action="{{ route('vera.tokens') }}" class="flex items-center gap-2">
                <select name="month" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 rounded-md px-2 py-1.5 text-gray-700 bg-white focus:border-purple-500 focus:ring-purple-500 focus:outline-none">
                    @forelse($availableMonths as $m)
                        @php $label = \Carbon\Carbon::create($m->year, $m->month)->translatedFormat('F Y'); @endphp
                        <option value="{{ $m->month }}" {{ ($m->month == $month && $m->year == $year) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @empty
                        <option selected>{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</option>
                    @endforelse
                </select>
                <input type="hidden" name="year" value="{{ $year }}">
            </form>
        </header>

        <div class="p-6 max-w-[1400px] mx-auto space-y-6 fade-in">

            {{-- ── BLOQUE 1: RESUMEN DE COSTES REALES ──────────────────────── --}}
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Coste real de API este mes</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                    {{-- Tokens totales --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <p class="text-xs text-gray-500 mb-1">Tokens consumidos</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($realCost['total_tokens']) }}</p>
                        <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                            <span>↑ {{ number_format($realCost['input_tokens']) }} input</span>
                            <span>·</span>
                            <span>↓ {{ number_format($realCost['output_tokens']) }} output</span>
                        </div>
                    </div>

                    {{-- Coste USD --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <p class="text-xs text-gray-500 mb-1">Coste API (USD)</p>
                        <p class="text-2xl font-semibold text-gray-900">${{ number_format($realCost['cost_usd'], 4) }}</p>
                        <p class="text-xs text-gray-400 mt-2">
                            @if($realCost['total_tokens'] > 0)
                                ~${{ number_format(($realCost['cost_usd'] / $realCost['total_tokens']) * 1000, 6) }} por 1k tokens
                            @else
                                Sin consumo este mes
                            @endif
                        </p>
                    </div>

                    {{-- Coste EUR --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5 border-l-4 border-l-purple-500">
                        <p class="text-xs text-gray-500 mb-1">Coste API (EUR)</p>
                        <p class="text-2xl font-semibold text-purple-700">€{{ number_format($realCost['cost_eur'], 4) }}</p>
                        <p class="text-xs text-gray-400 mt-2">Tasa: 1 USD ≈ 0.92 EUR</p>
                    </div>

                    {{-- Coste por llamada --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <p class="text-xs text-gray-500 mb-1">Coste medio por llamada</p>
                        <p class="text-2xl font-semibold text-gray-900">€{{ number_format($realCost['avg_cost_per_call_eur'], 5) }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ number_format($realCost['calls']) }} llamadas totales</p>
                    </div>
                </div>
            </div>

            {{-- ── BLOQUE 2: DESGLOSE POR OPERACIÓN ────────────────────────── --}}
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Coste por tipo de operación</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @php
                        $opColors = [
                            'email_analysis'      => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-700', 'icon' => '#9333ea'],
                            'response_generation' => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'text' => 'text-blue-700',   'icon' => '#2563eb'],
                            'chat_interaction'    => ['bg' => 'bg-green-50',  'border' => 'border-green-200',  'text' => 'text-green-700',  'icon' => '#16a34a'],
                        ];
                    @endphp

                    @forelse($stats['by_action_type'] as $actionType => $data)
                        @php
                            $c = $opColors[$actionType] ?? ['bg'=>'bg-gray-50','border'=>'border-gray-200','text'=>'text-gray-700','icon'=>'#6b7280'];
                            $costEur = \App\Models\VeraTokenUsage::calculateCost(0, 0, $data['total_tokens']) * 0.92;
                            $avgCostEur = $data['count'] > 0 ? $costEur / $data['count'] : 0;
                        @endphp
                        <div class="bg-white border {{ $c['border'] }} rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-900">{{ $actionLabels[$actionType] ?? $actionType }}</span>
                                <span class="text-xs {{ $c['text'] }} {{ $c['bg'] }} px-2 py-0.5 rounded-full">{{ $data['count'] }} llamadas</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400">Tokens totales</p>
                                    <p class="font-semibold text-gray-900">{{ number_format($data['total_tokens']) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Tokens / llamada</p>
                                    <p class="font-semibold text-gray-900">{{ number_format($data['avg_tokens']) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Coste total</p>
                                    <p class="font-semibold {{ $c['text'] }}">€{{ number_format($costEur, 4) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Coste / llamada</p>
                                    <p class="font-semibold {{ $c['text'] }}">€{{ number_format($avgCostEur, 5) }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="md:col-span-3 bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                            <p class="text-sm">Sin actividad este mes</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ── BLOQUE 3: CALCULADORA DE PRICING POR PLAN ───────────────── --}}
            <div
                x-data="{
                    prices: {
                        basic:   {{ $planPricing['plans']['basic']['suggested_price'] }},
                        starter: {{ $planPricing['plans']['starter']['suggested_price'] }},
                        pro:     {{ $planPricing['plans']['pro']['suggested_price'] }}
                    },
                    costs: {
                        basic:   {{ $planPricing['plans']['basic']['cost_api_eur'] }},
                        starter: {{ $planPricing['plans']['starter']['cost_api_eur'] }},
                        pro:     {{ $planPricing['plans']['pro']['cost_api_eur'] }}
                    },
                    marginEur(plan) {
                        return (this.prices[plan] - this.costs[plan]).toFixed(2);
                    },
                    marginPct(plan) {
                        if (this.prices[plan] <= 0) return '0.0';
                        return ((this.prices[plan] - this.costs[plan]) / this.prices[plan] * 100).toFixed(1);
                    },
                    marginColor(plan) {
                        const pct = parseFloat(this.marginPct(plan));
                        if (pct >= 80) return 'text-green-600';
                        if (pct >= 60) return 'text-amber-600';
                        return 'text-red-500';
                    }
                }"
            >
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                    Calculadora de pricing por plan
                    <span class="normal-case font-normal text-gray-400 ml-1">
                        — basada en {{ number_format($planPricing['avg_tokens_per_email']) }} tokens/email
                        @if($planPricing['data_points'] > 0)
                            ({{ $planPricing['data_points'] }} análisis reales tuyos)
                        @else
                            (referencia medida 18/02/2026)
                        @endif
                    </span>
                </h2>

                {{-- Info + aviso Gemini gratuito --}}
                <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
                        <p class="text-xs text-blue-700">
                            <strong>Cómo funciona:</strong> Vera procesa 1 email = 1 llamada a Gemini.
                            El coste de API es casi irrelevante (&lt;€1/usuario/mes con flujo alto).
                            El precio del plan lo fijas tú por <strong>valor percibido</strong>, no por coste de API.
                            Edita los precios abajo y ve el margen en tiempo real.
                        </p>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-lg px-4 py-3">
                        <p class="text-xs text-amber-700">
                            <strong>⚠ Límite Gemini gratuito:</strong> {{ number_format($planPricing['free_tier']['daily_limit']) }} req/día.
                            Un usuario con {{ number_format($planPricing['free_tier']['emails_per_day']) }} emails/día consume 100 req.
                            El tier gratuito aguanta hasta <strong>~{{ $planPricing['free_tier']['max_free_users'] }} usuarios activos</strong>.
                            A partir de ahí, necesitas Gemini de pago (~€1/usuario/mes con ese flujo).
                        </p>
                    </div>
                </div>

                {{-- Tabla interactiva --}}
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Plan</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Emails/mes</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Tokens/mes</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 text-red-500">Coste API</th>
                                    <th class="text-center text-xs font-medium text-purple-600 uppercase tracking-wider px-5 py-3">Precio venta/mes ← edita</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Margen €</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Margen %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">

                                {{-- Plan Basic --}}
                                @php $p = $planPricing['plans']['basic']; @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                            <span class="font-semibold text-gray-900">{{ $p['name'] }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right text-gray-600">{{ number_format($p['emails_per_month']) }}</td>
                                    <td class="px-5 py-4 text-right text-gray-500 text-xs">{{ number_format($p['tokens_per_month']) }}</td>
                                    <td class="px-5 py-4 text-right font-medium text-red-500">€{{ number_format($p['cost_api_eur'], 4) }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex items-center border border-purple-200 rounded-lg overflow-hidden bg-white shadow-sm">
                                            <span class="px-2 text-gray-400 text-xs bg-gray-50 border-r border-purple-200 py-2">€</span>
                                            <input type="number" x-model.number="prices.basic" min="0" step="1"
                                                class="w-20 text-center text-sm font-semibold text-gray-900 px-2 py-1.5 focus:outline-none focus:bg-purple-50">
                                            <span class="px-2 text-gray-400 text-xs bg-gray-50 border-l border-purple-200 py-2">/mes</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold" :class="marginColor('basic')" x-text="'€' + marginEur('basic')"></td>
                                    <td class="px-5 py-4 text-right font-bold" :class="marginColor('basic')" x-text="marginPct('basic') + '%'"></td>
                                </tr>

                                {{-- Plan Starter --}}
                                @php $p = $planPricing['plans']['starter']; @endphp
                                <tr class="hover:bg-gray-50 transition-colors bg-purple-50/30">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                            <span class="font-semibold text-gray-900">{{ $p['name'] }}</span>
                                            <span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">recomendado</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right text-gray-600">{{ number_format($p['emails_per_month']) }}</td>
                                    <td class="px-5 py-4 text-right text-gray-500 text-xs">{{ number_format($p['tokens_per_month']) }}</td>
                                    <td class="px-5 py-4 text-right font-medium text-red-500">€{{ number_format($p['cost_api_eur'], 4) }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex items-center border border-purple-300 rounded-lg overflow-hidden bg-white shadow-sm">
                                            <span class="px-2 text-gray-400 text-xs bg-gray-50 border-r border-purple-200 py-2">€</span>
                                            <input type="number" x-model.number="prices.starter" min="0" step="1"
                                                class="w-20 text-center text-sm font-semibold text-gray-900 px-2 py-1.5 focus:outline-none focus:bg-purple-50">
                                            <span class="px-2 text-gray-400 text-xs bg-gray-50 border-l border-purple-200 py-2">/mes</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold" :class="marginColor('starter')" x-text="'€' + marginEur('starter')"></td>
                                    <td class="px-5 py-4 text-right font-bold" :class="marginColor('starter')" x-text="marginPct('starter') + '%'"></td>
                                </tr>

                                {{-- Plan Pro --}}
                                @php $p = $planPricing['plans']['pro']; @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            <span class="font-semibold text-gray-900">{{ $p['name'] }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right text-gray-600">{{ number_format($p['emails_per_month']) }}</td>
                                    <td class="px-5 py-4 text-right text-gray-500 text-xs">{{ number_format($p['tokens_per_month']) }}</td>
                                    <td class="px-5 py-4 text-right font-medium text-red-500">€{{ number_format($p['cost_api_eur'], 4) }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex items-center border border-purple-200 rounded-lg overflow-hidden bg-white shadow-sm">
                                            <span class="px-2 text-gray-400 text-xs bg-gray-50 border-r border-purple-200 py-2">€</span>
                                            <input type="number" x-model.number="prices.pro" min="0" step="1"
                                                class="w-20 text-center text-sm font-semibold text-gray-900 px-2 py-1.5 focus:outline-none focus:bg-purple-50">
                                            <span class="px-2 text-gray-400 text-xs bg-gray-50 border-l border-purple-200 py-2">/mes</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold" :class="marginColor('pro')" x-text="'€' + marginEur('pro')"></td>
                                    <td class="px-5 py-4 text-right font-bold" :class="marginColor('pro')" x-text="marginPct('pro') + '%'"></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
                        <p class="text-xs text-gray-400">
                            Coste API calculado con Gemini Flash de pago: $0.075/1M input · $0.300/1M output.
                            El margen en verde ≥ 80% es el objetivo SaaS. Estos números NO incluyen servidor, Stripe fees (2.9%), soporte ni tu tiempo.
                            <strong class="text-gray-500">El precio del plan debe fijarse por valor percibido, no por coste de API</strong> (que es mínimo).
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── BLOQUE 4: GRÁFICO + DESGLOSE ────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Consumo diario de tokens</h2>
                    @if($dailyUsage->isEmpty())
                        <div class="flex items-center justify-center h-40 text-gray-400 text-sm">Sin actividad este mes</div>
                    @else
                        <div style="position:relative;height:200px;">
                            <canvas id="tokensChart"></canvas>
                        </div>
                    @endif
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Por tipo de operación</h2>
                    @if(empty($stats['by_action_type']))
                        <p class="text-sm text-gray-400 text-center mt-8">Sin datos</p>
                    @else
                        <div class="space-y-4">
                            @foreach($stats['by_action_type'] as $actionType => $data)
                                @php
                                    $pct = $stats['total_tokens'] > 0
                                        ? round(($data['total_tokens'] / $stats['total_tokens']) * 100, 1) : 0;
                                    $barClasses = ['email_analysis'=>'bg-purple-500','response_generation'=>'bg-blue-500','chat_interaction'=>'bg-green-500'];
                                    $bar = $barClasses[$actionType] ?? 'bg-gray-400';
                                @endphp
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-xs font-medium text-gray-700">{{ $actionLabels[$actionType] ?? $actionType }}</span>
                                        <span class="text-xs text-gray-500">{{ $pct }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="{{ $bar }} h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ number_format($data['total_tokens']) }} tokens · {{ $data['count'] }} llamadas</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── BLOQUE 5: HISTORIAL DETALLADO ───────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Historial detallado</h2>
                    <span class="text-xs text-gray-400">{{ $history->total() }} registros</span>
                </div>

                @if($history->isEmpty())
                    <div class="py-16 text-center text-gray-400">
                        <p class="text-sm">Sin registros este mes</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Fecha</th>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Operación</th>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Email</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Input</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Output</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Total</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 text-purple-600">Coste €</th>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">Modelo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($history as $record)
                                    @php
                                        $costEur = \App\Models\VeraTokenUsage::calculateCost(
                                            $record->prompt_tokens,
                                            $record->completion_tokens,
                                            $record->total_tokens
                                        ) * 0.92;
                                        $badge = [
                                            'email_analysis'      => 'bg-purple-100 text-purple-700',
                                            'response_generation' => 'bg-blue-100 text-blue-700',
                                            'chat_interaction'    => 'bg-green-100 text-green-700',
                                        ][$record->action_type] ?? 'bg-gray-100 text-gray-600';
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">
                                            {{ $record->created_at->format('d/m H:i') }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $badge }}">
                                                {{ $actionLabels[$record->action_type] ?? $record->action_type }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-gray-600 hidden md:table-cell max-w-[200px] truncate">
                                            @if($record->email)
                                                <span title="{{ $record->email->subject }}">{{ Str::limit($record->email->subject, 40) }}</span>
                                                <span class="text-gray-400 block text-xs">{{ $record->email->from_email }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-xs text-gray-500 text-right">{{ number_format($record->prompt_tokens) }}</td>
                                        <td class="px-5 py-3 text-xs text-gray-500 text-right">{{ number_format($record->completion_tokens) }}</td>
                                        <td class="px-5 py-3 text-sm font-semibold text-gray-900 text-right">{{ number_format($record->total_tokens) }}</td>
                                        <td class="px-5 py-3 text-xs font-medium text-purple-700 text-right whitespace-nowrap">
                                            €{{ number_format($costEur, 5) }}
                                        </td>
                                        <td class="px-5 py-3 text-xs text-gray-400 hidden lg:table-cell">{{ $record->model }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($history->hasPages())
                        <div class="px-5 py-3 border-t border-gray-100">{{ $history->links() }}</div>
                    @endif
                @endif
            </div>

        </div>{{-- /content --}}
    </main>
</div>

@if(!$dailyUsage->isEmpty())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const data = @json($dailyUsage->values()->map(fn($d) => ['day' => $d->day, 'tokens' => (int)$d->tokens]));
    const ctx  = document.getElementById('tokensChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => { const dt = new Date(d.day); return dt.getDate()+'/'+(dt.getMonth()+1); }),
            datasets: [{
                label: 'Tokens',
                data: data.map(d => d.tokens),
                backgroundColor: 'rgba(147,51,234,0.12)',
                borderColor: 'rgba(147,51,234,0.7)',
                borderWidth: 1.5,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' '+c.parsed.y.toLocaleString()+' tokens' } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, color: '#9ca3af', callback: v => v>=1000?(v/1000).toFixed(1)+'k':v } }
            }
        }
    });
});
</script>
@endif

<script>
function toggleSidebar() {
    const s = document.getElementById('sidebar');
    if (s) s.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
    const s = document.getElementById('sidebar');
    if (s && !s.contains(e.target) && !e.target.closest('[onclick="toggleSidebar()"]')) s.classList.add('hidden');
});
</script>
</body>
</html>
