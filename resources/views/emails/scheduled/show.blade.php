<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar Email Programado - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

<div class="flex min-h-screen" x-data="editScheduledEmailApp()">
    <!-- Sidebar -->
    @include('emails.partials.sidebar')

    <!-- Main Content -->
    <main class="flex-1 md:ml-56">
        <!-- Top Bar -->
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6">
            <div class="flex items-center gap-3 flex-1">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 class="text-sm font-semibold text-gray-900">✏️ Editar Email Programado</h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('scheduled-emails.index') }}"
                   class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    ← Volver a la lista
                </a>
            </div>
        </header>

        <!-- Form Content -->
        <div class="p-6">
            <div class="max-w-4xl mx-auto">

                <!-- Estado actual del email -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-1">Estado del email</h3>
                            <div class="flex items-center gap-3">
                                @if($scheduledEmail->status === 'pending')
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                        ⏳ Pendiente de envío
                                    </span>
                                @elseif($scheduledEmail->status === 'processing')
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full">
                                        🔄 Procesando
                                    </span>
                                @elseif($scheduledEmail->status === 'sent')
                                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                        ✅ Enviado
                                    </span>
                                @elseif($scheduledEmail->status === 'failed')
                                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">
                                        ❌ Fallido
                                    </span>
                                @elseif($scheduledEmail->status === 'cancelled')
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                                        🚫 Cancelado
                                    </span>
                                @endif

                                <span class="text-xs text-gray-500">
                                    Programado para: <strong>{{ \Carbon\Carbon::parse($scheduledEmail->scheduled_at)->format('d/m/Y H:i') }}</strong>
                                    ({{ \Carbon\Carbon::parse($scheduledEmail->scheduled_at)->diffForHumans() }})
                                </span>
                            </div>
                        </div>

                        @if(in_array($scheduledEmail->status, ['pending', 'processing']))
                            <button @click="cancelEmail()"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                                🗑️ Cancelar envío
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Formulario de edición -->
                @if(in_array($scheduledEmail->status, ['pending', 'processing']))
                <form @submit.prevent="saveChanges" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

                    <!-- Destinatarios (Para) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Para <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <template x-for="(recipient, index) in recipients.to" :key="index">
                                <div class="flex gap-2">
                                    <input type="email"
                                           x-model="recipients.to[index]"
                                           class="flex-1 rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                                           placeholder="email@example.com"
                                           required>
                                    <button type="button"
                                            @click="removeRecipient('to', index)"
                                            x-show="recipients.to.length > 1"
                                            class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                        ✕
                                    </button>
                                </div>
                            </template>
                            <button type="button"
                                    @click="addRecipient('to')"
                                    class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                + Agregar destinatario
                            </button>
                        </div>
                    </div>

                    <!-- CC (opcional) -->
                    <div class="mb-6" x-show="showCc || recipients.cc.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            CC (con copia)
                        </label>
                        <div class="space-y-2">
                            <template x-for="(recipient, index) in recipients.cc" :key="index">
                                <div class="flex gap-2">
                                    <input type="email"
                                           x-model="recipients.cc[index]"
                                           class="flex-1 rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                                           placeholder="email@example.com">
                                    <button type="button"
                                            @click="removeRecipient('cc', index)"
                                            class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                        ✕
                                    </button>
                                </div>
                            </template>
                            <button type="button"
                                    @click="addRecipient('cc')"
                                    class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                + Agregar CC
                            </button>
                        </div>
                    </div>

                    <div class="mb-6" x-show="!showCc && recipients.cc.length === 0">
                        <button type="button"
                                @click="showCc = true"
                                class="text-sm text-gray-600 hover:text-gray-700">
                            Agregar CC
                        </button>
                    </div>

                    <!-- Asunto -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Asunto <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="subject"
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                               placeholder="Asunto del email"
                               required>
                    </div>

                    <!-- Cuerpo del mensaje -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mensaje <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="body"
                                  rows="12"
                                  class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 font-mono text-sm"
                                  placeholder="Escribe tu mensaje aquí..."
                                  required></textarea>
                        <p class="text-xs text-gray-500 mt-2">
                            Puedes usar HTML para formatear el mensaje.
                        </p>
                    </div>

                    <!-- Fecha y hora programada -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha y hora de envío <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local"
                               x-model="scheduledAt"
                               :min="minDateTime"
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                               required>
                        <p class="text-xs text-gray-500 mt-2">
                            El email se enviará automáticamente en la fecha y hora seleccionada.
                        </p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between pt-4 border-t">
                        <a href="{{ route('scheduled-emails.index') }}"
                           class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                            Cancelar
                        </a>

                        <button type="submit"
                                :disabled="saving"
                                class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!saving">💾 Guardar cambios</span>
                            <span x-show="saving">⏳ Guardando...</span>
                        </button>
                    </div>

                </form>
                @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No se puede editar</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Este email ya fue {{ $scheduledEmail->status === 'sent' ? 'enviado' : ($scheduledEmail->status === 'failed' ? 'marcado como fallido' : 'cancelado') }}
                        y no puede ser modificado.
                    </p>
                    <a href="{{ route('scheduled-emails.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm rounded-lg transition">
                        Volver a la lista
                    </a>
                </div>
                @endif

            </div>
        </div>
    </main>
</div>

<!-- Alpine.js App -->
<script>
    function editScheduledEmailApp() {
        return {
            // Data inicial desde Laravel
            subject: @json($scheduledEmail->subject ?? ''),
            body: @json($scheduledEmail->body ?? ''),
            scheduledAt: '{{ \Carbon\Carbon::parse($scheduledEmail->scheduled_at)->format('Y-m-d\TH:i') }}',

            recipients: {
                to: @json(is_array($scheduledEmail->to) ? array_column($scheduledEmail->to, 'email') : [$scheduledEmail->to]),
                cc: @json(!empty($scheduledEmail->cc) && is_array($scheduledEmail->cc) ? array_column($scheduledEmail->cc, 'email') : []),
            },

            showCc: @json(!empty($scheduledEmail->cc)),
            saving: false,

            get minDateTime() {
                // Fecha mínima: ahora + 5 minutos
                const now = new Date();
                now.setMinutes(now.getMinutes() + 5);
                return now.toISOString().slice(0, 16);
            },

            addRecipient(type) {
                this.recipients[type].push('');
            },

            removeRecipient(type, index) {
                this.recipients[type].splice(index, 1);
            },

            saveChanges() {
                this.saving = true;

                // Preparar datos para enviar
                const data = {
                    subject: this.subject,
                    body: this.body,
                    scheduled_at: this.scheduledAt,
                    to: this.recipients.to.filter(email => email.trim() !== '').map(email => ({
                        email: email,
                        type: 'to'
                    })),
                    cc: this.recipients.cc.filter(email => email.trim() !== '').map(email => ({
                        email: email,
                        type: 'cc'
                    })),
                };

                fetch('/scheduled-emails/{{ $scheduledEmail->id }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        alert('✅ Email programado actualizado correctamente');
                        // Redirigir a la lista
                        window.location.href = '/scheduled-emails';
                    } else {
                        alert('❌ Error: ' + (data.message || 'No se pudo actualizar el email'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al actualizar el email');
                })
                .finally(() => {
                    this.saving = false;
                });
            },

            cancelEmail() {
                if (!confirm('¿Estás seguro de cancelar este email programado?\n\nNo se enviará y no podrás recuperarlo.')) {
                    return;
                }

                fetch('/scheduled-emails/{{ $scheduledEmail->id }}/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Email programado cancelado');
                        window.location.href = '/scheduled-emails';
                    } else {
                        alert('❌ Error: ' + (data.message || 'No se pudo cancelar el email'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al cancelar el email');
                });
            }
        }
    }

    // Toggle sidebar en móvil
    function toggleSidebar() {
        const sidebar = document.querySelector('[data-sidebar]');
        if (sidebar) {
            sidebar.classList.toggle('hidden');
        }
    }
</script>

</body>
</html>
