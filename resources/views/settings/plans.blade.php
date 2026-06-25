<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planes de Vera') }}
        </h2>
    </x-slot>

    <div class="py-6 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="text-center mb-8">
                <p class="text-gray-600">
                    Elige el plan que mejor se adapte a tus necesidades
                </p>
                @if($currentUsage)
                    <p class="text-sm text-gray-500 mt-2">
                        Uso actual: {{ $currentUsage['emails_processed'] }} / {{ $currentUsage['email_limit'] }} emails este mes
                    </p>
                @endif
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                @foreach($plans as $planKey => $plan)
                    <div class="relative">
                        <!-- Popular Badge -->
                        @if(isset($plan['popular']) && $plan['popular'])
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
                                <span class="bg-violet-600 text-white px-4 py-1 rounded-full text-xs font-semibold shadow-lg">
                                    Más Popular
                                </span>
                            </div>
                        @endif

                        <!-- Plan Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6 h-full flex flex-col
                            {{ $plan['is_current'] ? 'ring-2 ring-violet-500' : '' }}
                            {{ isset($plan['popular']) && $plan['popular'] ? 'md:transform md:scale-105' : '' }}">

                            <!-- Current Plan Badge -->
                            @if($plan['is_current'])
                                <div class="mb-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-violet-100 text-violet-800 text-xs font-semibold">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Plan Actual
                                    </span>
                                </div>
                            @endif

                            <!-- Plan Name & Price -->
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan['name'] }}</h3>
                            <div class="mb-6">
                                <span class="text-4xl font-bold text-gray-900">{{ $plan['price'] }}€</span>
                                <span class="text-gray-600">/mes</span>
                            </div>

                            <!-- Límite de mensajes -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <div class="text-gray-600 text-sm mb-1">Mensajes con IA al mes</div>
                                <div class="text-3xl font-bold text-gray-900">{{ number_format($plan['email_limit']) }}</div>
                            </div>

                            <!-- Features List -->
                            @php
                                $featureLabels = [
                                    'basic_analysis'    => 'Análisis de mensajes con IA',
                                    'sentiment'         => 'Detección de quejas e incidencias',
                                    'weekly_learning'   => 'Aprendizaje semanal',
                                    'suggested_replies' => 'Respuestas sugeridas',
                                    'auto_response'     => 'Modo autónomo (responde sola)',
                                    'analytics'         => 'Analíticas',
                                    'organizations'     => 'Multi-sede / multi-profesional',
                                    'priority_support'  => 'Soporte prioritario',
                                    'crm_basic'         => 'CRM básico (ficha + historial + notas)',
                                    'crm_advanced'      => 'CRM completo (segmentación + informes)',
                                ];
                            @endphp
                            <div class="flex-grow mb-6">
                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-violet-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-gray-700 text-sm font-medium">{{ number_format($plan['email_limit']) }} mensajes/mes · WhatsApp + Email + Agenda</span>
                                    </div>
                                    @foreach($plan['features'] as $feature)
                                        <div class="flex items-start">
                                            <svg class="w-5 h-5 text-violet-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="text-gray-700 text-sm">{{ $featureLabels[$feature] ?? $feature }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Action Button -->
                            @if($plan['is_current'])
                                <button disabled class="w-full bg-gray-100 text-gray-400 px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
                                    Plan Actual
                                </button>
                            @else
                                <button
                                    onclick="updatePlan('{{ $planKey }}')"
                                    class="w-full bg-violet-600 hover:bg-violet-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors"
                                    id="btn-{{ $planKey }}"
                                >
                                    Cambiar a {{ $plan['name'] }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Additional Info -->
            <div class="bg-white shadow sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Información sobre los planes
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
                    <div>
                        <h4 class="text-gray-900 font-semibold mb-2">Cambios de plan</h4>
                        <ul class="space-y-2">
                            <li>• Al actualizar, tu límite se reinicia inmediatamente</li>
                            <li>• Puedes cambiar de plan en cualquier momento</li>
                            <li>• Los cambios se aplican de forma instantánea</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-gray-900 font-semibold mb-2">Procesamiento de emails</h4>
                        <ul class="space-y-2">
                            <li>• Emails procesados solo con reglas son gratis</li>
                            <li>• Solo emails complejos usan IA (≈20% del total)</li>
                            <li>• Vera sigue funcionando aunque alcances el límite</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Preguntas frecuentes</h3>
                <div class="space-y-6 text-sm">
                    <div>
                        <h4 class="text-gray-900 font-semibold mb-2">¿Qué pasa si supero mi límite?</h4>
                        <p class="text-gray-600">Vera seguirá procesando tus emails usando solo reglas (sin IA). No perderás funcionalidad básica, solo el análisis avanzado con inteligencia artificial.</p>
                    </div>
                    <div>
                        <h4 class="text-gray-900 font-semibold mb-2">¿Cuándo se reinicia mi límite?</h4>
                        <p class="text-gray-600">Tu límite se reinicia automáticamente el primer día de cada mes a las 00:00.</p>
                    </div>
                    <div>
                        <h4 class="text-gray-900 font-semibold mb-2">¿Puedo cambiar de plan en cualquier momento?</h4>
                        <p class="text-gray-600">Sí, puedes actualizar o cambiar tu plan cuando lo desees. Al actualizar, tu límite se reinicia inmediatamente.</p>
                    </div>
                    <div>
                        <h4 class="text-gray-900 font-semibold mb-2">¿Qué son los tokens?</h4>
                        <p class="text-gray-600">Los tokens son unidades de medida del procesamiento de IA. En promedio, un email procesado con IA consume alrededor de 1,100 tokens. Esta métrica técnica te permite entender el consumo detallado.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        async function updatePlan(plan) {
            const button = document.getElementById(`btn-${plan}`);
            const originalText = button.textContent;

            // Confirm
            const planNames = {
                'basic': 'Básico (49€/mes)',
                'pro': 'Pro (89€/mes)',
                'enterprise': 'Centro (149€/mes)'
            };

            if (!confirm(`¿Confirmas el cambio al plan ${planNames[plan]}?\n\nTu límite se reiniciará inmediatamente.`)) {
                return;
            }

            // Disable button
            button.disabled = true;
            button.textContent = 'Procesando...';

            try {
                const response = await fetch('{{ route("settings.plans.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ plan })
                });

                const data = await response.json();

                if (data.success) {
                    // Success - reload page to show updated plan
                    window.location.reload();
                } else {
                    alert('Error al actualizar el plan: ' + (data.message || 'Error desconocido'));
                    button.disabled = false;
                    button.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al actualizar el plan. Por favor, intenta de nuevo.');
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    </script>
</x-app-layout>
