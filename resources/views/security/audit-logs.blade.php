<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Historial de Actividad</h2>
                    <p class="mt-1 text-sm text-gray-600">Registro completo de todas tus acciones en la plataforma</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('security.audit.export', request()->query()) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Exportar CSV
                    </a>
                    <a href="{{ route('security.dashboard') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                @foreach([
                    ['label' => 'Total', 'value' => $stats['total'], 'color' => 'text-gray-900', 'bg' => 'bg-gray-50'],
                    ['label' => 'Hoy', 'value' => $stats['today'], 'color' => 'text-blue-700', 'bg' => 'bg-blue-50'],
                    ['label' => 'Esta semana', 'value' => $stats['this_week'], 'color' => 'text-purple-700', 'bg' => 'bg-purple-50'],
                    ['label' => 'Críticos', 'value' => $stats['critical'], 'color' => 'text-red-700', 'bg' => 'bg-red-50'],
                    ['label' => 'Fallidos', 'value' => $stats['failed'], 'color' => 'text-orange-700', 'bg' => 'bg-orange-50'],
                ] as $stat)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
                        <div class="text-2xl font-bold {{ $stat['color'] }}">{{ number_format($stat['value']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
                <form method="GET" action="{{ route('security.audit.index') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-40">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none"
                               placeholder="Buscar en descripción...">
                    </div>
                    <div class="min-w-36">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="category"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none">
                            <option value="">Todas</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->event_category }}" {{ request('category') === $cat->event_category ? 'selected' : '' }}>
                                    {{ ucfirst($cat->event_category) }} ({{ $cat->total }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-32">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Severidad</label>
                        <select name="severity"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none">
                            <option value="">Todas</option>
                            <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Aviso</option>
                            <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Crítico</option>
                        </select>
                    </div>
                    <div class="min-w-36">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none">
                    </div>
                    <div class="min-w-36">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Filtrar
                        </button>
                        @if(request()->hasAny(['search', 'category', 'severity', 'date_from', 'date_to']))
                            <a href="{{ route('security.audit.index') }}"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tabla de logs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @if($logs->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resultado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($logs as $log)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $log->created_at->format('d/m/Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $catColors = [
                                                    'auth'     => 'bg-blue-100 text-blue-800',
                                                    'email'    => 'bg-purple-100 text-purple-800',
                                                    'security' => 'bg-red-100 text-red-800',
                                                    'settings' => 'bg-yellow-100 text-yellow-800',
                                                    'data'     => 'bg-green-100 text-green-800',
                                                ];
                                                $catColor = $catColors[$log->event_category] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $catColor }}">
                                                {{ ucfirst($log->event_category ?? '-') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $log->description }}">
                                                {{ $log->description ?? $log->event_type ?? '-' }}
                                            </div>
                                            @if($log->event_type)
                                                <div class="text-xs text-gray-500">{{ $log->event_type }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($log->action_result === 'success')
                                                <span class="inline-flex items-center text-xs text-green-700">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    Exitoso
                                                </span>
                                            @elseif($log->action_result === 'failed')
                                                <span class="inline-flex items-center text-xs text-red-700">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                    Fallido
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">{{ $log->action_result ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $severityBadge = [
                                                    'info'     => 'bg-blue-50 text-blue-700',
                                                    'warning'  => 'bg-yellow-50 text-yellow-700',
                                                    'critical' => 'bg-red-50 text-red-700',
                                                ];
                                                $badgeClass = $severityBadge[$log->severity] ?? 'bg-gray-50 text-gray-600';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                                {{ ucfirst($log->severity ?? 'info') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-xs text-gray-500 font-mono">{{ $log->ip_address ?? '-' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            Mostrando {{ $logs->firstItem() }} - {{ $logs->lastItem() }} de {{ $logs->total() }} registros
                        </p>
                        {{ $logs->links() }}
                    </div>
                @else
                    <div class="py-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">Sin registros</h3>
                        <p class="text-sm text-gray-500">
                            @if(request()->hasAny(['search', 'category', 'severity', 'date_from', 'date_to']))
                                No hay registros que coincidan con los filtros aplicados.
                            @else
                                Tu historial de actividad aparecerá aquí conforme uses la plataforma.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
