{{-- Reporte de Análisis de Vera --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl p-6 text-white">
        <h2 class="text-2xl font-bold flex items-center gap-2">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            Análisis Inteligente de tus Emails
        </h2>
        <p class="mt-2 text-purple-100">Vera ha analizado tus patrones de comunicación</p>
    </div>

    {{-- Estadísticas Generales --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">📊</span>
            Estadísticas Generales
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Total de Emails</div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($analysis['email_statistics']['total_emails']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Recibidos</div>
                <div class="text-2xl font-bold text-blue-600">{{ number_format($analysis['email_statistics']['received_emails']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Enviados</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($analysis['email_statistics']['sent_emails']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Promedio/día</div>
                <div class="text-2xl font-bold text-purple-600">{{ $analysis['email_statistics']['avg_emails_per_day'] }}</div>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <span class="font-medium">Período:</span> {{ $analysis['email_statistics']['first_email_date'] ?? 'N/A' }} → {{ $analysis['email_statistics']['last_email_date'] ?? 'N/A' }}
            ({{ $analysis['email_statistics']['days_of_data'] }} días)
        </div>
    </div>

    {{-- Estilo de Escritura --}}
    @if($analysis['writing_style']['has_sent_emails'])
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">✍️</span>
            Tu Estilo de Escritura
        </h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tono:</span>
                        <span class="font-semibold text-gray-900 capitalize">{{ $analysis['writing_style']['tone'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Longitud promedio:</span>
                        <span class="font-semibold text-gray-900">{{ $analysis['writing_style']['avg_email_length'] }} caracteres</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Usa emojis:</span>
                        <span class="font-semibold {{ $analysis['writing_style']['uses_emojis'] ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $analysis['writing_style']['uses_emojis'] ? 'Sí' : 'No' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Formalidad:</span>
                        <div class="mt-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ ($analysis['writing_style']['formality_score'] * 100) }}%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ round($analysis['writing_style']['formality_score'] * 100) }}%</div>
                    </div>
                </div>
            </div>
            <div>
                @if(!empty($analysis['writing_style']['top_greetings']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">Saludos típicos:</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($analysis['writing_style']['top_greetings'] as $greeting)
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">{{ $greeting }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($analysis['writing_style']['top_closings']))
                <div>
                    <div class="text-sm font-medium text-gray-700 mb-2">Despedidas típicas:</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($analysis['writing_style']['top_closings'] as $closing)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">{{ $closing }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Patrones de Comunicación --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">📅</span>
            Patrones de Comunicación
        </h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="text-gray-600">Día más activo:</span>
                    <span class="font-semibold text-gray-900">{{ $analysis['communication_patterns']['peak_day'] }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-gray-600">Hora más activa:</span>
                    <span class="font-semibold text-gray-900">{{ $analysis['communication_patterns']['peak_hour'] }}</span>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="{{ $analysis['communication_patterns']['is_mostly_weekday'] ? 'text-blue-600' : 'text-orange-600' }}">
                        {{ $analysis['communication_patterns']['is_mostly_weekday'] ? '💼' : '🏖️' }}
                    </span>
                    <span class="text-gray-700">{{ $analysis['communication_patterns']['is_mostly_weekday'] ? 'Mayoría entre semana' : 'Mayoría fines de semana' }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="{{ $analysis['communication_patterns']['is_mostly_business_hours'] ? 'text-blue-600' : 'text-purple-600' }}">
                        {{ $analysis['communication_patterns']['is_mostly_business_hours'] ? '⏰' : '🌙' }}
                    </span>
                    <span class="text-gray-700">{{ $analysis['communication_patterns']['is_mostly_business_hours'] ? 'Horario laboral' : 'Fuera de horario' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Comportamiento de Respuesta --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">⚡</span>
            Comportamiento de Respuesta
        </h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Tasa de Respuesta</div>
                <div class="text-3xl font-bold text-purple-600">{{ $analysis['response_behavior']['response_rate'] }}%</div>
                <div class="mt-2 bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $analysis['response_behavior']['response_rate'] }}%"></div>
                </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Emails Respondidos</div>
                <div class="text-3xl font-bold text-green-600">{{ number_format($analysis['response_behavior']['responded_to']) }}</div>
                <div class="text-xs text-gray-500 mt-1">de {{ number_format($analysis['response_behavior']['total_received']) }} recibidos</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Tiempo Promedio</div>
                <div class="text-3xl font-bold text-blue-600">
                    {{ $analysis['response_behavior']['avg_response_time_hours'] ?? 'N/A' }}
                    @if($analysis['response_behavior']['avg_response_time_hours'])
                    <span class="text-sm">h</span>
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ $analysis['response_behavior']['response_speed'] }}</div>
            </div>
        </div>
    </div>

    {{-- Conclusiones Inteligentes --}}
    @if(!empty($analysis['conclusions']))
    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl border border-purple-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">🧠</span>
            Conclusiones Inteligentes
        </h3>
        <div class="space-y-4">
            @foreach($analysis['conclusions'] as $index => $conclusion)
            <div class="bg-white rounded-lg p-4 border border-purple-100">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">
                            @switch($conclusion['type'])
                                @case('communication_volume') 📊 @break
                                @case('writing_style') ✍️ @break
                                @case('response_behavior') ⚡ @break
                                @case('email_types') 📂 @break
                                @default 💡
                            @endswitch
                        </span>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-900 font-medium">{{ $conclusion['message'] }}</p>
                        <p class="mt-2 text-sm text-purple-700 flex items-start gap-2">
                            <span>💡</span>
                            <span>{{ $conclusion['suggestion'] }}</span>
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Configuración Recomendada --}}
    <div class="bg-green-50 rounded-xl border border-green-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">⚙️</span>
            Configuración Recomendada
        </h3>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="flex items-center justify-between bg-white rounded-lg p-3">
                <span class="text-gray-700">Auto-archivar newsletters</span>
                <span class="text-2xl">{{ $analysis['recommended_settings']['auto_archive_newsletters'] ? '✅' : '❌' }}</span>
            </div>
            <div class="flex items-center justify-between bg-white rounded-lg p-3">
                <span class="text-gray-700">Respuestas automáticas</span>
                <span class="text-2xl">{{ $analysis['recommended_settings']['auto_response_enabled'] ? '✅' : '❌' }}</span>
            </div>
            <div class="flex items-center justify-between bg-white rounded-lg p-3">
                <span class="text-gray-700">Umbral de confianza</span>
                <span class="font-semibold text-purple-600">{{ round($analysis['recommended_settings']['auto_response_confidence_threshold'] * 100) }}%</span>
            </div>
            <div class="flex items-center justify-between bg-white rounded-lg p-3">
                <span class="text-gray-700">Estilo de respuesta</span>
                <span class="font-semibold text-gray-900 capitalize">{{ $analysis['recommended_settings']['response_style'] }}</span>
            </div>
        </div>
    </div>
</div>
