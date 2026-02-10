<x-app-layout>
    <div class="py-12" x-data="gdprApp()">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Privacidad y RGPD</h2>
                    <p class="mt-1 text-sm text-gray-600">Gestiona tus datos personales conforme al Reglamento General de Protección de Datos</p>
                </div>
                <a href="{{ route('security.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a Seguridad
                </a>
            </div>

            <!-- Mensajes -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-4 py-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('warning') }}
                </div>
            @endif

            <!-- Alerta si hay solicitud de eliminación activa -->
            @if($hasActiveDeletion)
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="font-medium text-red-800">Solicitud de eliminación pendiente</h3>
                            <p class="mt-1 text-sm text-red-700">Tienes una solicitud de eliminación de datos en proceso. Puedes cancelarla más abajo.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- RGPD Info banner -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mr-3 mt-0.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-blue-900 text-sm">Tus derechos bajo el RGPD</h3>
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach([
                                ['icon' => '📋', 'title' => 'Acceso', 'desc' => 'Puedes exportar todos tus datos'],
                                ['icon' => '🗑️', 'title' => 'Olvido', 'desc' => 'Puedes solicitar eliminar tu cuenta'],
                                ['icon' => '✏️', 'title' => 'Rectificación', 'desc' => 'Puedes corregir tus datos'],
                                ['icon' => '🔒', 'title' => 'Limitación', 'desc' => 'Puedes limitar el tratamiento'],
                            ] as $right)
                                <div class="bg-white rounded-lg p-3 text-center">
                                    <span class="text-lg">{{ $right['icon'] }}</span>
                                    <p class="text-xs font-semibold text-blue-900 mt-1">{{ $right['title'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $right['desc'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- ========== EXPORTAR DATOS ========== -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Exportar Mis Datos</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Derecho de portabilidad de datos</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">
                            Descarga una copia completa de todos tus datos: emails, contactos, configuraciones, historial de actividad y más.
                            El archivo estará disponible durante 7 días.
                        </p>

                        <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm">
                            <p class="font-medium text-gray-900 mb-2">El archivo incluirá:</p>
                            <ul class="space-y-1 text-gray-600">
                                <li class="flex items-center"><svg class="w-3.5 h-3.5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Perfil y preferencias</li>
                                <li class="flex items-center"><svg class="w-3.5 h-3.5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Contactos y historial de emails</li>
                                <li class="flex items-center"><svg class="w-3.5 h-3.5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Plantillas y configuración de Vera AI</li>
                                <li class="flex items-center"><svg class="w-3.5 h-3.5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Registros de actividad</li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('gdpr.export') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Solicitar Exportación
                            </button>
                        </form>

                        <!-- Historial de exportaciones -->
                        @if($exportRequests->isNotEmpty())
                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Exportaciones anteriores</h4>
                                <div class="space-y-2">
                                    @foreach($exportRequests as $export)
                                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                                            <div>
                                                <span class="text-xs font-medium text-gray-900">
                                                    {{ $export->created_at->format('d/m/Y H:i') }}
                                                </span>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $export->status === 'completed' ? 'bg-green-100 text-green-800' :
                                                       ($export->status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                       ($export->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                                    {{ ucfirst($export->status) }}
                                                </span>
                                            </div>
                                            @if($export->status === 'completed' && !$export->isExpired())
                                                <a href="{{ route('privacy.download', $export->download_token) }}"
                                                   class="text-xs text-purple-600 hover:text-purple-800 font-medium">
                                                    Descargar
                                                </a>
                                            @elseif($export->isExpired())
                                                <span class="text-xs text-gray-400">Expirado</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ========== ELIMINAR DATOS ========== -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Eliminar Mis Datos</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Derecho al olvido (Art. 17 RGPD)</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">
                            Puedes solicitar la eliminación de todos tus datos o solo datos específicos.
                            <strong class="text-red-600">La eliminación completa de cuenta es irreversible.</strong>
                        </p>

                        @if(!$hasActiveDeletion)
                            <form method="POST" action="{{ route('gdpr.delete') }}" x-data="{ deletionType: 'partial', selectedTypes: [] }">
                                @csrf
                                <div class="space-y-4">
                                    <!-- Tipo de eliminación -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900 mb-2">Tipo de eliminación</label>
                                        <div class="space-y-2">
                                            <label class="flex items-start cursor-pointer p-3 border rounded-lg hover:border-orange-300 transition-colors"
                                                   :class="deletionType === 'partial' ? 'border-orange-400 bg-orange-50' : 'border-gray-200'">
                                                <input type="radio" name="deletion_type" value="partial"
                                                       x-model="deletionType" class="mt-0.5 text-orange-600 focus:ring-orange-500">
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900">Eliminación parcial</p>
                                                    <p class="text-xs text-gray-500">Elimina solo datos específicos</p>
                                                </div>
                                            </label>
                                            <label class="flex items-start cursor-pointer p-3 border rounded-lg hover:border-red-300 transition-colors"
                                                   :class="deletionType === 'full_account' ? 'border-red-400 bg-red-50' : 'border-gray-200'">
                                                <input type="radio" name="deletion_type" value="full_account"
                                                       x-model="deletionType" class="mt-0.5 text-red-600 focus:ring-red-500">
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900">Eliminar cuenta completa</p>
                                                    <p class="text-xs text-red-600">⚠️ Irreversible - Requiere aprobación</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Tipos de datos (solo para parcial) -->
                                    <div x-show="deletionType === 'partial'" x-cloak>
                                        <label class="block text-sm font-medium text-gray-900 mb-2">Datos a eliminar</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($availableDataTypes as $key => $label)
                                                <label class="flex items-center cursor-pointer p-2 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors">
                                                    <input type="checkbox" name="data_types[]" value="{{ $key }}"
                                                           class="text-purple-600 focus:ring-purple-500 rounded">
                                                    <span class="ml-2 text-xs text-gray-700">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Motivo -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900 mb-1">Motivo (opcional)</label>
                                        <textarea name="reason" rows="2"
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 bg-white text-sm focus:border-purple-500 focus:ring-purple-500 focus:ring-1 focus:outline-none"
                                                  placeholder="¿Por qué deseas eliminar estos datos?"></textarea>
                                    </div>

                                    <button type="submit"
                                            @click.prevent="
                                                if (deletionType === 'full_account') {
                                                    if (confirm('⚠️ ADVERTENCIA\n\nEsta acción eliminará PERMANENTEMENTE tu cuenta y todos tus datos.\n\n¿Estás completamente seguro?')) {
                                                        $el.closest('form').submit();
                                                    }
                                                } else {
                                                    $el.closest('form').submit();
                                                }
                                            "
                                            :class="deletionType === 'full_account' ? 'bg-red-600 hover:bg-red-700' : 'bg-orange-600 hover:bg-orange-700'"
                                            class="w-full text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Solicitar Eliminación
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-red-700 font-medium mb-1">Solicitud activa</p>
                                <p class="text-xs text-red-600">Ya tienes una solicitud de eliminación en proceso.</p>
                            </div>
                        @endif

                        <!-- Historial de solicitudes de eliminación -->
                        @if($deletionRequests->isNotEmpty())
                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Solicitudes anteriores</h4>
                                <div class="space-y-2">
                                    @foreach($deletionRequests as $deletion)
                                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                                            <div>
                                                <span class="text-xs font-medium text-gray-900">
                                                    {{ $deletion->created_at->format('d/m/Y') }} -
                                                    {{ $deletion->deletion_type === 'full_account' ? 'Cuenta completa' : 'Parcial' }}
                                                </span>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $deletion->status === 'completed' ? 'bg-green-100 text-green-800' :
                                                       ($deletion->status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                       ($deletion->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-800')) }}">
                                                    {{ ucfirst($deletion->status) }}
                                                </span>
                                            </div>
                                            @if($deletion->status === 'pending')
                                                <form method="POST" action="{{ route('gdpr.deletion.cancel', $deletion) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Info de retención de datos -->
            <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">📋 Información sobre Retención de Datos</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    @foreach([
                        ['icon' => '📧', 'title' => 'Emails', 'desc' => 'Se conservan durante tu suscripción activa', 'period' => 'Activo'],
                        ['icon' => '👤', 'title' => 'Datos de perfil', 'desc' => 'Necesarios para el funcionamiento del servicio', 'period' => 'Activo'],
                        ['icon' => '📊', 'title' => 'Logs de actividad', 'desc' => 'Se mantienen para seguridad y auditoría', 'period' => '12 meses'],
                        ['icon' => '🔐', 'title' => 'Sesiones', 'desc' => 'Se eliminan automáticamente al expirar', 'period' => '30 días'],
                        ['icon' => '📩', 'title' => 'Exportaciones', 'desc' => 'El archivo de descarga expira', 'period' => '7 días'],
                        ['icon' => '🗑️', 'title' => 'Datos eliminados', 'desc' => 'Eliminación permanente tras solicitud aprobada', 'period' => '30 días'],
                    ] as $item)
                        <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                            <span class="text-xl mr-3 flex-shrink-0">{{ $item['icon'] }}</span>
                            <div>
                                <p class="font-medium text-gray-900">{{ $item['title'] }}</p>
                                <p class="text-xs text-gray-500">{{ $item['desc'] }}</p>
                                <span class="inline-block mt-1 text-xs text-purple-600 font-medium bg-purple-50 px-2 py-0.5 rounded">{{ $item['period'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
    function gdprApp() {
        return {}
    }
    </script>
</x-app-layout>
