<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración de Notificaciones - Vera AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#fafafa] font-['Inter']">
    <div class="flex min-h-screen">
        @include('components.dashboard-sidebar')
        <main class="flex-1 md:ml-56">
            <header class="h-14 bg-white border-b border-gray-200 flex items-center px-6">
                <h1 class="text-sm font-semibold text-gray-900">Configuración de Notificaciones</h1>
            </header>
            <div class="p-6 max-w-5xl mx-auto">
                <!-- Header con descripción -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Preferencias de Notificaciones</h2>
                    <p class="text-sm text-gray-500">Configura cómo y cuándo quieres recibir actualizaciones de Vera</p>
                </div>

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.notifications.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <!-- Canales de Notificación -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                Canales de Notificación
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Elige cómo quieres recibir notificaciones de Vera</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Email Notifications -->
                            <div class="flex items-start gap-4 p-4 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           name="email_enabled"
                                           value="1"
                                           {{ $preferences->email_enabled ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <label class="text-sm font-medium text-gray-900">Notificaciones por Email</label>
                                    </div>
                                    <p class="text-xs text-gray-500">Recibe notificaciones importantes directamente en tu correo</p>
                                </div>
                            </div>

                            <!-- Push Notifications -->
                            <div class="flex items-start gap-4 p-4 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           name="push_enabled"
                                           value="1"
                                           {{ $preferences->push_enabled ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <label class="text-sm font-medium text-gray-900">Notificaciones Push</label>
                                    </div>
                                    <p class="text-xs text-gray-500">Recibe notificaciones instantáneas en tu navegador o dispositivo móvil</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resúmenes Automáticos -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Resúmenes Automáticos
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Recibe resúmenes periódicos de la actividad de Vera</p>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Resumen Diario -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox"
                                               name="daily_summary_enabled"
                                               value="1"
                                               {{ $preferences->daily_summary_enabled ? 'checked' : '' }}
                                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-2xl">📅</span>
                                            <label class="text-sm font-medium text-gray-900">Resumen Diario</label>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-3">Recibe un resumen de la actividad del día: emails procesados, respuestas enviadas y tareas completadas</p>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600 font-medium">Hora:</span>
                                            <input type="time"
                                                   name="daily_summary_time"
                                                   value="{{ $preferences->daily_summary_time }}"
                                                   class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            <span class="text-xs text-gray-400">(Recomendado: 18:00 - Al final del día)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen Semanal -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox"
                                               name="weekly_summary_enabled"
                                               value="1"
                                               {{ $preferences->weekly_summary_enabled ? 'checked' : '' }}
                                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-2xl">📊</span>
                                            <label class="text-sm font-medium text-gray-900">Resumen Semanal</label>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-3">Estadísticas semanales: emails totales, tiempo ahorrado, tendencias y análisis de productividad</p>

                                        <div class="flex items-center gap-3 flex-wrap">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-600 font-medium">Día:</span>
                                                <select name="weekly_summary_day"
                                                        class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                    <option value="monday" {{ $preferences->weekly_summary_day == 'monday' ? 'selected' : '' }}>Lunes</option>
                                                    <option value="tuesday" {{ $preferences->weekly_summary_day == 'tuesday' ? 'selected' : '' }}>Martes</option>
                                                    <option value="wednesday" {{ $preferences->weekly_summary_day == 'wednesday' ? 'selected' : '' }}>Miércoles</option>
                                                    <option value="thursday" {{ $preferences->weekly_summary_day == 'thursday' ? 'selected' : '' }}>Jueves</option>
                                                    <option value="friday" {{ $preferences->weekly_summary_day == 'friday' ? 'selected' : '' }}>Viernes</option>
                                                    <option value="saturday" {{ $preferences->weekly_summary_day == 'saturday' ? 'selected' : '' }}>Sábado</option>
                                                    <option value="sunday" {{ $preferences->weekly_summary_day == 'sunday' ? 'selected' : '' }}>Domingo</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-600 font-medium">Hora:</span>
                                                <input type="time"
                                                       name="weekly_summary_time"
                                                       value="{{ $preferences->weekly_summary_time }}"
                                                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            </div>
                                            <span class="text-xs text-gray-400">(Recomendado: Lunes 9:00)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen Mensual -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox"
                                               name="monthly_summary_enabled"
                                               value="1"
                                               {{ $preferences->monthly_summary_enabled ? 'checked' : '' }}
                                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-2xl">📈</span>
                                            <label class="text-sm font-medium text-gray-900">Resumen Mensual</label>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-3">Informe completo del mes: métricas, comparativas, consumo de plan y recomendaciones de optimización</p>

                                        <div class="flex items-center gap-3 flex-wrap">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-600 font-medium">Día del mes:</span>
                                                <input type="number"
                                                       name="monthly_summary_day"
                                                       value="{{ $preferences->monthly_summary_day }}"
                                                       min="1"
                                                       max="31"
                                                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm w-20 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-600 font-medium">Hora:</span>
                                                <input type="time"
                                                       name="monthly_summary_time"
                                                       value="{{ $preferences->monthly_summary_time }}"
                                                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            </div>
                                            <span class="text-xs text-gray-400">(Recomendado: Día 1 a las 9:00)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Alertas Importantes
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Recibe alertas cuando ocurran eventos importantes</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Advertencias de Límite -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox"
                                               name="limit_warnings_enabled"
                                               value="1"
                                               {{ $preferences->limit_warnings_enabled ? 'checked' : '' }}
                                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-2xl">⚠️</span>
                                            <label class="text-sm font-medium text-gray-900">Advertencias de Límite de Uso</label>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-3">Te avisaremos cuando estés cerca de alcanzar tu límite mensual de emails procesados</p>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600 font-medium">Alertar cuando llegue al:</span>
                                            <input type="number"
                                                   name="warning_threshold_percentage"
                                                   value="{{ $preferences->warning_threshold_percentage }}"
                                                   min="50"
                                                   max="100"
                                                   class="px-3 py-1.5 border border-gray-300 rounded-md text-sm w-20 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            <span class="text-xs text-gray-600 font-medium">%</span>
                                            <span class="text-xs text-gray-400">(Recomendado: 80%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alertas de Alta Actividad -->
                            <div class="flex items-start gap-4 p-4 rounded-lg hover:bg-gray-50 transition-colors border border-gray-200">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input type="checkbox"
                                           name="high_activity_alerts"
                                           value="1"
                                           {{ $preferences->high_activity_alerts ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-2xl">🔥</span>
                                        <label class="text-sm font-medium text-gray-900">Alertas de Alta Actividad</label>
                                    </div>
                                    <p class="text-xs text-gray-500">Te avisaremos si detectamos un pico inusual de emails (ejemplo: más de 50 emails en una hora)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Guardar -->
                    <div class="flex items-center justify-between pt-4">
                        <p class="text-xs text-gray-500">Los cambios se aplicarán inmediatamente después de guardar</p>
                        <button type="submit"
                                class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 transition-all shadow-sm hover:shadow">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
    </script>
</body>
</html>
