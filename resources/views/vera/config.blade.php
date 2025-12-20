<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuración - Vera AI</title>

    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased bg-gradient-to-br from-purple-50 via-white to-indigo-50 min-h-screen">
    
    <div class="max-w-6xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Configuración de Vera</h1>
                <p class="text-gray-600 mt-1">Ajusta cómo Vera gestiona tus emails</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-purple-600 hover:text-purple-700 font-medium">
                ← Volver al Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600 mb-1">Emails Procesados</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['processed_emails'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600 mb-1">Archivados Auto</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['auto_archived'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600 mb-1">Respuestas Generadas</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['responses_generated'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600 mb-1">Último Análisis</p>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $stats['last_analysis'] ? $stats['last_analysis']->diffForHumans() : 'Nunca' }}
                </p>
            </div>
        </div>

        <!-- Config Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Ajustes</h2>
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Objetivo Principal</label>
                    <select class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="filter" {{ $config->primary_goal === 'filter' ? 'selected' : '' }}>
                            Filtrar y organizar
                        </option>
                        <option value="respond" {{ $config->primary_goal === 'respond' ? 'selected' : '' }}>
                            Ayudar a responder
                        </option>
                        <option value="balanced" {{ $config->primary_goal === 'balanced' ? 'selected' : '' }}>
                            Ambos (filtrar y responder)
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nivel de Autonomía</label>
                    <select class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="conservative" {{ $config->autonomy_level === 'conservative' ? 'selected' : '' }}>
                            Conservador - Preguntarme todo
                        </option>
                        <option value="moderate" {{ $config->autonomy_level === 'moderate' ? 'selected' : '' }}>
                            Moderado - Balance (Recomendado)
                        </option>
                        <option value="aggressive" {{ $config->autonomy_level === 'aggressive' ? 'selected' : '' }}>
                            Agresivo - Máxima autonomía
                        </option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" 
                           {{ $config->auto_respond_enabled ? 'checked' : '' }}
                           class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <label class="text-sm font-medium text-gray-700">
                        Activar respuestas automáticas
                    </label>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" 
                           {{ $config->auto_archive_newsletters ? 'checked' : '' }}
                           class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <label class="text-sm font-medium text-gray-700">
                        Archivar newsletters automáticamente
                    </label>
                </div>
            </div>

            <div class="flex gap-4 mt-8">
                <button class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    Guardar Cambios
                </button>
                <button class="px-6 py-3 border-2 border-purple-600 text-purple-600 hover:bg-purple-50 rounded-lg font-semibold transition-colors">
                    Re-analizar Emails
                </button>
            </div>
        </div>
    </div>
</body>
</html>
