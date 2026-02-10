@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Gestión de Sesiones Activas</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Administra todos los dispositivos y ubicaciones desde donde has iniciado sesión
                </p>
            </div>

            <!-- Alertas de actividad sospechosa -->
            @if(count($suspiciousActivity) > 0)
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Actividad sospechosa detectada</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($suspiciousActivity as $alert)
                                        <li>{{ $alert['message'] }} (Severidad: {{ $alert['severity'] }})</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Total de Sesiones</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $sessions->count() }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Sesiones Activas</div>
                    <div class="mt-1 text-3xl font-semibold text-green-600">{{ $activeSessions->count() }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Sesiones Expiradas</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-400">{{ $sessions->count() - $activeSessions->count() }}</div>
                </div>
            </div>

            <!-- Botón de cerrar todas las sesiones -->
            <div class="mb-6 flex justify-end">
                <button
                    onclick="revokeAllSessions()"
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    🚫 Cerrar todas las sesiones (excepto esta)
                </button>
            </div>

            <!-- Lista de sesiones -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Sesiones Activas</h3>

                    @forelse($sessions as $session)
                        <div class="border rounded-lg p-4 mb-4 @if($session->is_current) bg-green-50 border-green-200 @elseif($session->isExpired()) bg-gray-50 border-gray-200 @else bg-white border-gray-200 @endif">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-2xl">{{ $session->device_icon }}</div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-sm font-semibold text-gray-900">
                                                    {{ $session->browser ?? 'Navegador desconocido' }} en {{ $session->platform ?? 'SO desconocido' }}
                                                </h4>
                                                @if($session->is_current)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        ✓ Sesión actual
                                                    </span>
                                                @elseif($session->isExpired())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Expirada
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Activa
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-sm text-gray-500">
                                                <div>📍 {{ $session->location ?? $session->ip_address ?? 'Ubicación desconocida' }}</div>
                                                <div>🕐 Última actividad: {{ $session->last_activity ? $session->last_activity->diffForHumans() : 'Desconocida' }}</div>
                                                @if($session->expires_at)
                                                    <div>⏰ Expira: {{ $session->expires_at->format('d/m/Y H:i') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if(!$session->is_current && $session->isActive())
                                    <button
                                        onclick="revokeSession({{ $session->id }})"
                                        class="ml-4 inline-flex items-center px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        Cerrar sesión
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="text-4xl mb-2">🔒</div>
                            <p class="text-gray-500">No hay sesiones registradas</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function revokeSession(sessionId) {
            if (!confirm('¿Estás seguro de que deseas cerrar esta sesión?')) {
                return;
            }

            fetch(`/security/sessions/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Sesión cerrada exitosamente');
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al cerrar la sesión');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cerrar la sesión');
            });
        }

        function revokeAllSessions() {
            if (!confirm('¿Estás seguro de que deseas cerrar todas las demás sesiones? Esta acción cerrará tu sesión en todos los otros dispositivos.')) {
                return;
            }

            fetch('/security/sessions/revoke-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || `${data.count} sesiones cerradas exitosamente`);
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al cerrar las sesiones');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cerrar las sesiones');
            });
        }
    </script>
    @endpush
@endsection
