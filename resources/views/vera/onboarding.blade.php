<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Onboarding - Vera AI</title>

    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased bg-gradient-to-br from-purple-50 via-white to-indigo-50 min-h-screen"
    x-data="veraOnboarding()">
    
    <div class="max-w-6xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-3">Bienvenido al Onboarding de Vera</h1>
            <p class="text-xl text-gray-600">Tu Asistente de Email IA</p>
        </div>

        <!-- Análisis Completado -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📊 Reporte de Análisis</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-purple-50 rounded-xl p-6">
                    <p class="text-sm text-purple-600 font-medium mb-1">Total Emails</p>
                    <p class="text-3xl font-bold text-purple-900">{{ $analysis['total_emails'] ?? 0 }}</p>
                </div>
                <div class="bg-indigo-50 rounded-xl p-6">
                    <p class="text-sm text-indigo-600 font-medium mb-1">Emails Recibidos</p>
                    <p class="text-3xl font-bold text-indigo-900">{{ $analysis['received_count'] ?? 0 }}</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-6">
                    <p class="text-sm text-blue-600 font-medium mb-1">Emails Enviados</p>
                    <p class="text-3xl font-bold text-blue-900">{{ $analysis['sent_count'] ?? 0 }}</p>
                </div>
            </div>

            <div class="prose max-w-none">
                {!! $report !!}
            </div>
        </div>

        <!-- Configuración Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">⚙️ Configuración de Vera</h2>
            
            <form @submit.prevent="saveConfiguration()">
                @foreach($questions as $index => $question)
                    <div class="mb-8 pb-8 border-b border-gray-200 last:border-0">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">
                            Pregunta {{ $index + 1 }} de {{ count($questions) }}
                        </h3>
                        <p class="text-gray-700 mb-4">{{ $question['question'] }}</p>
                        
                        <div class="space-y-3">
                            @foreach($question['options'] as $optionKey => $option)
                                <label class="flex items-start gap-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 hover:bg-purple-50 transition-all">
                                    <input type="radio" 
                                           name="question_{{ $index }}" 
                                           value="{{ $optionKey }}"
                                           class="mt-1 text-purple-600 focus:ring-purple-500"
                                           x-model="answers[{{ $index }}]">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $option['label'] }}</p>
                                        <p class="text-sm text-gray-600">{{ $option['description'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-4 px-8 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                        Guardar Configuración
                    </button>
                    <a href="{{ route('dashboard') }}"
                       class="px-8 py-4 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-semibold rounded-xl transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function veraOnboarding() {
            return {
                answers: {},

                async saveConfiguration() {
                    const formData = this.buildFormData();

                    try {
                        const response = await fetch('/vera/onboarding', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(formData)
                        });

                        if (response.ok) {
                            alert('✅ Configuración guardada exitosamente!');
                            window.location.href = '/dashboard';
                        } else {
                            alert('Error al guardar configuración');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error al guardar configuración');
                    }
                },

                buildFormData() {
                    return {
                        primary_goal: this.answers[0] || 'balanced',
                        writing_style: this.answers[1] || 'professional',
                        response_mode: this.answers[2] || 'filter_focus',
                        newsletter_handling: this.answers[3] || 'auto_archive',
                        priority_contacts: [],
                        active_hours_start: this.answers[5] || '00:00',
                        active_hours_end: this.answers[5] || '23:59',
                        autonomy_level: this.answers[6] || 'moderate',
                    };
                }
            }
        }
    </script>
</body>
</html>
