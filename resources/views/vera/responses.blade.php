<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
            </svg>
            Respuestas Automáticas de Vera
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header con contador -->
            <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Respuestas Pendientes</h3>
                        <p class="text-sm text-gray-600 mt-1">Revisa y aprueba las respuestas generadas por Vera</p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-purple-600" id="pendingCount">-</div>
                        <div class="text-sm text-gray-500">pendientes</div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div id="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                <p class="mt-4 text-gray-600">Cargando respuestas...</p>
            </div>

            <!-- Empty state -->
            <div id="emptyState" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No hay respuestas pendientes</h3>
                <p class="mt-2 text-sm text-gray-500">¡Excelente! No tienes respuestas pendientes de revisión.</p>
            </div>

            <!-- Lista de respuestas -->
            <div id="responsesList" class="space-y-6"></div>
        </div>
    </div>

    <script>
        let responses = [];

        async function loadResponses() {
            try {
                const response = await fetch('/vera/responses/pending');
                const data = await response.json();

                responses = data.responses || [];
                document.getElementById('pendingCount').textContent = data.count;

                if (responses.length === 0) {
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('emptyState').classList.remove('hidden');
                } else {
                    renderResponses();
                }
            } catch (error) {
                console.error('Error loading responses:', error);
                alert('Error cargando respuestas');
            }
        }

        function renderResponses() {
            const container = document.getElementById('responsesList');
            container.innerHTML = '';

            responses.forEach(response => {
                container.appendChild(createResponseCard(response));
            });

            document.getElementById('loading').classList.add('hidden');
            document.getElementById('responsesList').classList.remove('hidden');
        }

        function createResponseCard(response) {
            const card = document.createElement('div');
            card.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden';
            card.id = `response-${response.id}`;

            const confidenceColor = response.confidence_score >= 0.8 ? 'green' :
                                   response.confidence_score >= 0.6 ? 'yellow' : 'red';

            card.innerHTML = `
                <div class="p-6">
                    <!-- Email Original -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900">Email Original</h4>
                            <span class="px-3 py-1 bg-${confidenceColor}-100 text-${confidenceColor}-700 rounded-full text-sm font-medium">
                                Confianza: ${Math.round(response.confidence_score * 100)}%
                            </span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">
                                <div><strong>De:</strong> ${response.email.from_name} &lt;${response.email.from_email}&gt;</div>
                                <div><strong>Asunto:</strong> ${response.email.subject}</div>
                            </div>
                            <div class="mt-3 text-sm text-gray-800">${response.email.snippet || response.email.body_text?.substring(0, 200) + '...' || ''}</div>
                        </div>
                    </div>

                    <!-- Respuesta de Vera -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Respuesta Generada por Vera
                        </h4>
                        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                            <textarea id="response-text-${response.id}" class="w-full bg-transparent border-none focus:outline-none text-gray-800 resize-none" rows="6" readonly>${response.generated_response}</textarea>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            <strong>Razón:</strong> ${response.reasoning || 'N/A'}
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex flex-wrap gap-2">
                        <button onclick="approveAndSend(${response.id})" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                            ✅ Aprobar y Enviar
                        </button>
                        <button onclick="editResponse(${response.id})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            ✏️ Editar
                        </button>
                        <button onclick="rejectResponse(${response.id})" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                            ❌ Rechazar
                        </button>
                    </div>
                </div>
            `;

            return card;
        }

        async function approveAndSend(id) {
            if (!confirm('¿Seguro que quieres aprobar y enviar esta respuesta?')) return;

            try {
                // Aprobar
                await fetch(`/vera/responses/${id}/approve`, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
                // Enviar
                const sendResponse = await fetch(`/vera/responses/${id}/send`, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
                const data = await sendResponse.json();

                if (data.success) {
                    alert('✅ Respuesta enviada exitosamente');
                    document.getElementById(`response-${id}`).remove();
                    updateCount();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            } catch (error) {
                alert('❌ Error al enviar respuesta');
                console.error(error);
            }
        }

        function editResponse(id) {
            const textarea = document.getElementById(`response-text-${id}`);
            const wasReadonly = textarea.hasAttribute('readonly');

            if (wasReadonly) {
                textarea.removeAttribute('readonly');
                textarea.classList.add('border', 'border-blue-300', 'p-2', 'rounded');
                textarea.focus();
                alert('Edita la respuesta y luego haz clic en "Guardar y Enviar"');

                const card = document.getElementById(`response-${id}`);
                const actionsDiv = card.querySelector('.flex.flex-wrap');
                actionsDiv.innerHTML = `
                    <button onclick="saveAndSend(${id})" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                        💾 Guardar y Enviar
                    </button>
                    <button onclick="cancelEdit(${id})" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition">
                        ❌ Cancelar
                    </button>
                `;
            }
        }

        async function saveAndSend(id) {
            const textarea = document.getElementById(`response-text-${id}`);
            const newText = textarea.value;

            try {
                // Actualizar
                await fetch(`/vera/responses/${id}`, {
                    method: 'PATCH',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({generated_response: newText})
                });
                // Enviar
                const sendResponse = await fetch(`/vera/responses/${id}/send`, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
                const data = await sendResponse.json();

                if (data.success) {
                    alert('✅ Respuesta actualizada y enviada');
                    document.getElementById(`response-${id}`).remove();
                    updateCount();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            } catch (error) {
                alert('❌ Error al guardar respuesta');
                console.error(error);
            }
        }

        function cancelEdit(id) {
            location.reload();
        }

        async function rejectResponse(id) {
            const reason = prompt('¿Por qué rechazas esta respuesta? (opcional)');

            try {
                await fetch(`/vera/responses/${id}/reject`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({reason})
                });

                alert('❌ Respuesta rechazada');
                document.getElementById(`response-${id}`).remove();
                updateCount();
            } catch (error) {
                alert('❌ Error al rechazar respuesta');
                console.error(error);
            }
        }

        function updateCount() {
            const count = document.querySelectorAll('[id^="response-"]').length;
            document.getElementById('pendingCount').textContent = count;

            if (count === 0) {
                document.getElementById('responsesList').classList.add('hidden');
                document.getElementById('emptyState').classList.remove('hidden');
            }
        }

        // Cargar al iniciar
        loadResponses();
    </script>
</x-app-layout>
