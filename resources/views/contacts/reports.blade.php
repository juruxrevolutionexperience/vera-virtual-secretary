<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Informes CRM — Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

<div class="flex min-h-screen overflow-x-hidden">
    @include('components.dashboard-sidebar')

    <main class="flex-1 min-w-0 lg:ml-56 p-4 lg:p-8">
        <div class="max-w-5xl">
            <h1 class="text-xl font-bold text-gray-900 mb-1">Informes del CRM</h1>
            <p class="text-sm text-gray-500 mb-8">Visión de tu cartera de clientes y conversión.</p>

            {{-- Embudo de conversión --}}
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Embudo de conversión</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="text-3xl font-bold text-gray-900">{{ $totalContactos }}</p>
                    <p class="text-sm text-gray-500 mt-1">Contactos</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="text-3xl font-bold text-gray-900">{{ $agendaron }}</p>
                    <p class="text-sm text-gray-500 mt-1">Agendaron cita</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="text-3xl font-bold text-gray-900">{{ $repiten }}</p>
                    <p class="text-sm text-gray-500 mt-1">Repiten (≥2 citas)</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="text-3xl font-bold text-purple-600">{{ $conversion }}%</p>
                    <p class="text-sm text-gray-500 mt-1">Conversión</p>
                </div>
            </div>

            {{-- Segmentación de la cartera --}}
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Segmentación de la cartera</h2>
            @php
                $segMeta = [
                    'habitual'  => ['Cliente habitual', '#166534'],
                    'cliente'   => ['Cliente',          '#1e40af'],
                    'potencial' => ['Potencial',        '#92400e'],
                    'dudoso'    => ['Dudoso',           '#9a3412'],
                    'perdido'   => ['Perdido',          '#6b7280'],
                ];
                $segTotal = max(array_sum($segments), 1);
            @endphp
            <div class="bg-white border border-gray-200 rounded-xl p-5 mb-10 space-y-4">
                @foreach($segMeta as $key => $meta)
                    @php $n = $segments[$key] ?? 0; $pct = (int) round($n / $segTotal * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium" style="color:{{ $meta[1] }};">{{ $meta[0] }}</span>
                            <span class="text-sm text-gray-500">{{ $n }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full" style="background:#f3f4f6;">
                            <div class="h-2 rounded-full" style="width:{{ $pct }}%;background:{{ $meta[1] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Informe mensual de citas --}}
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Citas por mes (últimos 6 meses)</h2>
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="text-left font-medium px-5 py-3">Mes</th>
                            <th class="text-right font-medium px-5 py-3">Total</th>
                            <th class="text-right font-medium px-5 py-3">Confirmadas</th>
                            <th class="text-right font-medium px-5 py-3">Canceladas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthly as $row)
                        <tr class="border-t border-gray-100">
                            <td class="px-5 py-3 text-gray-900">{{ $row->mes }}</td>
                            <td class="px-5 py-3 text-right text-gray-900">{{ $row->total }}</td>
                            <td class="px-5 py-3 text-right text-green-600">{{ $row->confirmadas }}</td>
                            <td class="px-5 py-3 text-right text-red-600">{{ $row->canceladas }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Aún no hay citas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>
