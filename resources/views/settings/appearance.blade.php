<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Apariencia - Ajustes</title>
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-50 font-['Inter']">

    <div class="min-h-screen flex flex-col" x-data="appearanceSettings()">

        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('emails.index') }}" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Apariencia</h1>
                            <p class="text-sm text-gray-500">Personaliza cómo se ven tus emails</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Panel de Configuración -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Densidad de Visualización</h2>

                    <div class="space-y-4">

                        <!-- Opción: Compacta -->
                        <label class="flex items-start gap-4 p-4 border-2 rounded-lg cursor-pointer transition-all"
                            :class="selectedDensity === 'compact' ? 'border-purple-500 bg-purple-50' : 'border-gray-200                 
  hover:border-gray-300'">
                            <input type="radio" name="density" value="compact" x-model="selectedDensity"
                                class="mt-1 text-purple-600 focus:ring-purple-500">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">Compacta</span>
                                    <span class="text-xs text-gray-500">Máximo aprovechamiento</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Muestra más emails en pantalla con espaciado mínimo
                                </p>
                            </div>
                        </label>

                        <!-- Opción: Cómoda -->
                        <label class="flex items-start gap-4 p-4 border-2 rounded-lg cursor-pointer transition-all"
                            :class="selectedDensity === 'comfortable' ? 'border-purple-500 bg-purple-50' : 'border-gray-200             
  hover:border-gray-300'">
                            <input type="radio" name="density" value="comfortable" x-model="selectedDensity"
                                class="mt-1 text-purple-600 focus:ring-purple-500">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">Cómoda</span>
                                    <span
                                        class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full">Recomendada</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Balance perfecto entre espacio y legibilidad
                                </p>
                            </div>
                        </label>

                        <!-- Opción: Espaciosa -->
                        <label class="flex items-start gap-4 p-4 border-2 rounded-lg cursor-pointer transition-all"
                            :class="selectedDensity === 'spacious' ? 'border-purple-500 bg-purple-50' : 'border-gray-200                
  hover:border-gray-300'">
                            <input type="radio" name="density" value="spacious" x-model="selectedDensity"
                                class="mt-1 text-purple-600 focus:ring-purple-500">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">Espaciosa</span>
                                    <span class="text-xs text-gray-500">Máxima claridad</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Espaciado amplio para lectura cómoda
                                </p>
                            </div>
                        </label>

                    </div>

                    <!-- Botón Guardar -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <button @click="saveDensity()" :disabled="saving || selectedDensity === currentDensity" :class="saving || selectedDensity === currentDensity ? 'bg-gray-300 cursor-not-allowed' : 'bg-purple-600   
  hover:bg-purple-700'" class="w-full px-4 py-2.5 text-white font-medium rounded-lg transition-colors">
                            <span x-show="!saving">Guardar Preferencias</span>
                            <span x-show="saving">Guardando...</span>
                        </button>

                        <!-- Mensaje de éxito -->
                        <div x-show="showSuccess" x-transition class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 text-sm                
  text-green-700">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586   
  7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Preferencias guardadas correctamente</span>
                        </div>
                    </div>
                </div>

                <!-- Panel de Preview -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Vista Previa</h2>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- Header del preview -->
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Bandeja de entrada</span>
                        </div>

                        <!-- Lista de emails de ejemplo -->
                        <div class="bg-white divide-y divide-gray-100">

                            <!-- Email 1 -->
                            <div :class="getDensityClass()" class="px-4 hover:bg-gray-50 cursor-pointer transition-colors border-l-3       
  border-l-purple-500">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-purple-600">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-semibold text-gray-900 text-sm">John Doe</span>
                                            <span class="text-xs text-gray-500">10:30 AM</span>
                                        </div>
                                        <div class="text-sm text-gray-900 font-medium truncate">
                                            Reunión de proyecto importante
                                        </div>
                                        <div class="text-sm text-gray-500 truncate">
                                            Hola, necesitamos coordinar la reunión del viernes...
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email 2 -->
                            <div :class="getDensityClass()"
                                class="px-4 hover:bg-gray-50 cursor-pointer transition-colors">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-purple-600">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-medium text-gray-700 text-sm">Newsletter Team</span>
                                            <span class="text-xs text-gray-500">Ayer</span>
                                        </div>
                                        <div class="text-sm text-gray-700 truncate">
                                            Novedades de esta semana
                                        </div>
                                        <div class="text-sm text-gray-500 truncate">
                                            Descubre las últimas actualizaciones y mejoras...
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email 3 -->
                            <div :class="getDensityClass()"
                                class="px-4 hover:bg-gray-50 cursor-pointer transition-colors">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-purple-600">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-medium text-gray-700 text-sm">Support Team</span>
                                            <span class="text-xs text-gray-500">2 días</span>
                                        </div>
                                        <div class="text-sm text-gray-700 truncate">
                                            Respuesta a tu ticket #1234
                                        </div>
                                        <div class="text-sm text-gray-500 truncate">
                                            Hemos revisado tu consulta y tenemos una solución...
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Indicador de densidad actual -->
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Densidad actual:</span>
                            <span class="font-medium text-gray-900" x-text="getDensityLabel()"></span>
                        </div>
                    </div>
                </div>

            </div>

        </main>

    </div>

    <script>
        function appearanceSettings() {
            return {
                currentDensity: '{{ $currentDensity }}',
                selectedDensity: '{{ $currentDensity }}',
                saving: false,
                showSuccess: false,

                getDensityClass() {
                    const classes = {
                        'compact': 'py-2',
                        'comfortable': 'py-3',
                        'spacious': 'py-4'
                    };
                    return classes[this.selectedDensity] || classes.comfortable;
                },

                getDensityLabel() {
                    const labels = {
                        'compact': 'Compacta',
                        'comfortable': 'Cómoda',
                        'spacious': 'Espaciosa'
                    };
                    return labels[this.selectedDensity] || 'Cómoda';
                },

                async saveDensity() {
                    this.saving = true;
                    this.showSuccess = false;

                    try {
                        const response = await fetch('{{ route('settings.appearance.density') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                density: this.selectedDensity
                            })
                        });

                        if (!response.ok) throw new Error('Error al guardar');

                        const data = await response.json();

                        this.currentDensity = this.selectedDensity;
                        this.showSuccess = true;

                        // Guardar en localStorage para aplicar inmediatamente                                                             
                        localStorage.setItem('email-density', this.selectedDensity);

                        // Ocultar mensaje después de 3 segundos                                                                           
                        setTimeout(() => {
                            this.showSuccess = false;
                        }, 3000);

                        console.log('✅ Densidad guardada:', data);

                    } catch (error) {
                        console.error('❌ Error:', error);
                        alert('Error al guardar las preferencias. Inténtalo de nuevo.');
                    } finally {
                        this.saving = false;
                    }
                }
            }
        }                                                                                                                                  
    </script>

</body>

</html>