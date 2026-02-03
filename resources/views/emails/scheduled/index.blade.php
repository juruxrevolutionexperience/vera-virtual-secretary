<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Emails Programados - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

<div class="flex min-h-screen" x-data="scheduledEmailsApp()">
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

                <h1 class="text-sm font-semibold text-gray-900">📅 Emails Programados</h1>
                <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                <span class="hidden sm:inline-block text-xs text-gray-500">{{ $scheduledEmails->total() }} programados</span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('emails.index') }}"
                   class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Volver a Inbox
                </a>
            </div>
        </header>

        <!-- Filtros / Tabs -->
        <div class="px-6 pt-6">
            <div class="flex gap-2 border-b border-gray-200 mb-6">
                <a href="{{ route('scheduled-emails.index', ['status' => 'pending']) }}"
                   class="px-4 py-2 text-sm font-medium transition {{ request('status', 'pending') === 'pending' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-600 hover:text-gray-900' }}">
                    ⏳ Pendientes
                    @php
                        $pendingCount = \App\Models\ScheduledEmail::where('user_id', auth()->id())
                            ->whereIn('status', ['pending', 'processing'])->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="ml-1 px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">{{ $pendingCount }}</span>
                    @endif
                </a>

                <a href="{{ route('scheduled-emails.index', ['status' => 'sent']) }}"
                   class="px-4 py-2 text-sm font-medium transition {{ request('status') === 'sent' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-600 hover:text-gray-900' }}">
                    ✅ Enviados
                </a>

                <a href="{{ route('scheduled-emails.index', ['status' => 'failed']) }}"
                   class="px-4 py-2 text-sm font-medium transition {{ request('status') === 'failed' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-600 hover:text-gray-900' }}">
                    ❌ Fallidos
                    @php
                        $failedCount = \App\Models\ScheduledEmail::where('user_id', auth()->id())
                            ->where('status', 'failed')->count();
                    @endphp
                    @if($failedCount > 0)
                        <span class="ml-1 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">{{ $failedCount }}</span>
                    @endif
                </a>

                <a href="{{ route('scheduled-emails.index', ['status' => 'cancelled']) }}"
                   class="px-4 py-2 text-sm font-medium transition {{ request('status') === 'cancelled' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-600 hover:text-gray-900' }}">
                    🚫 Cancelados
                </a>
            </div>
        </div>

        <!-- Email List -->
        <div class="px-6 pb-6">
            @if($scheduledEmails->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    @foreach($scheduledEmails as $scheduledEmail)
                        <div class="border-b border-gray-100 p-4 hover:bg-gray-50 transition-colors" x-data="{ showActions: false }">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Asunto -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-sm font-semibold text-gray-900">
                                            {{ $scheduledEmail->subject ?? '(Sin asunto)' }}
                                        </h3>
                                    </div>

                                    <!-- Destinatarios -->
                                    <p class="text-xs text-gray-600 mb-2">
                                        <span class="font-medium">Para:</span>
                                        @if(is_array($scheduledEmail->to))
                                            {{ implode(', ', array_column($scheduledEmail->to, 'email')) }}
                                        @else
                                            {{ $scheduledEmail->to }}
                                        @endif
                                    </p>

                                    <!-- Fecha programada -->
                                    <div class="flex items-center gap-4 text-xs">
                                        <span class="flex items-center gap-1 text-purple-600 font-medium">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($scheduledEmail->scheduled_at)->format('d/m/Y H:i') }}
                                        </span>
                                        <span class="text-gray-500">
                                            ({{ \Carbon\Carbon::parse($scheduledEmail->scheduled_at)->diffForHumans() }})
                                        </span>

                                        <!-- Badge de estado -->
                                        @if($scheduledEmail->status === 'pending')
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">
                                                Pendiente
                                            </span>
                                        @elseif($scheduledEmail->status === 'processing')
                                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full">
                                                Procesando
                                            </span>
                                        @elseif($scheduledEmail->status === 'sent')
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full">
                                                Enviado
                                            </span>
                                        @elseif($scheduledEmail->status === 'failed')
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full">
                                                Fallido
                                            </span>
                                        @elseif($scheduledEmail->status === 'cancelled')
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full">
                                                Cancelado
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Mensaje de error (si falló) -->
                                    @if($scheduledEmail->status === 'failed' && $scheduledEmail->error_message)
                                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                            <strong>Error:</strong> {{ $scheduledEmail->error_message }}
                                        </div>
                                    @endif

                                    <!-- Preview del body (primeras líneas) -->
                                    @if($scheduledEmail->body)
                                        <p class="text-xs text-gray-500 mt-2">
                                            {{ Str::limit(strip_tags($scheduledEmail->body), 120) }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Botones de acción -->
                                <div class="flex items-center gap-2 ml-4">
                                    <!-- Ver detalle (siempre visible) -->
                                    <button @click="viewEmail({{ $scheduledEmail->id }})"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Ver detalle">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>

                                    @if(in_array($scheduledEmail->status, ['pending', 'processing']))
                                        <!-- Editar (solo para pendientes/procesando) -->
                                        <button @click="editEmail({{ $scheduledEmail->id }})"
                                                class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition"
                                                title="Editar">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                            </svg>
                                        </button>

                                        <!-- Cancelar (solo para pendientes/procesando) -->
                                        <button @click="cancelEmail({{ $scheduledEmail->id }})"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                title="Cancelar envío">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($scheduledEmail->status === 'failed')
                                        <!-- Reintentar (solo para fallidos) -->
                                        <button @click="retryEmail({{ $scheduledEmail->id }})"
                                                class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                                                title="Reintentar envío">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                <div class="mt-4">
                    {{ $scheduledEmails->links() }}
                </div>
            @else
                <!-- Estado vacío -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">No hay emails programados</h3>
                    <p class="text-sm text-gray-500 mb-4">Los emails que programes para enviar más tarde aparecerán aquí.</p>
                    <a href="{{ route('emails.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm rounded-lg transition">
                        Ir al Inbox
                    </a>
                </div>
            @endif
        </div>
    </main>
</div>

<!-- Alpine.js App -->
<script>
    function scheduledEmailsApp() {
        return {
            viewEmail(id) {
                // Mostrar modal o redirigir a vista de detalle
                window.location.href = `/scheduled-emails/${id}`;
            },

            editEmail(id) {
                // Redirigir a edición (implementar más adelante)
                window.location.href = `/scheduled-emails/${id}`;
            },

            cancelEmail(id) {
                if (!confirm('¿Estás seguro de cancelar este email programado?')) {
                    return;
                }

                fetch(`/scheduled-emails/${id}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar página para ver cambios
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo cancelar el email'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cancelar el email');
                });
            },

            retryEmail(id) {
                if (!confirm('¿Reintentar el envío de este email?')) {
                    return;
                }

                fetch(`/scheduled-emails/${id}/retry`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Email marcado para reenvío');
                        // Recargar página para ver cambios
                        window.location.reload();
                    } else {
                        alert('❌ Error: ' + (data.message || 'No se pudo reintentar el envío'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al reintentar el envío');
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
