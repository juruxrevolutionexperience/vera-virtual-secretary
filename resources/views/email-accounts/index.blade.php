<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cuentas de Email - Vera</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Cuentas de Email</h1>
                        <p class="text-sm text-gray-600 mt-1">Gestiona tus cuentas conectadas de Gmail y Outlook</p>
                    </div>
                    <a href="{{ route('emails.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        ← Volver a Emails
                    </a>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Mensajes de éxito/error -->
            @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
            @endif

            <!-- Botones para conectar nuevas cuentas -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Conectar Nueva Cuenta</h2>
                <div class="flex gap-4">
                    <a href="{{ route('email-accounts.google.redirect') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Conectar Gmail
                    </a>

                    <a href="{{ route('email-accounts.microsoft.redirect') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#f25022" d="M0 0h11v11H0z"/>
                            <path fill="#00a4ef" d="M13 0h11v11H13z"/>
                            <path fill="#7fba00" d="M0 13h11v11H0z"/>
                            <path fill="#ffb900" d="M13 13h11v11H13z"/>
                        </svg>
                        Conectar Outlook
                    </a>
                </div>
            </div>

            <!-- Lista de cuentas conectadas -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Cuentas Conectadas</h2>
                </div>

                @if($accounts->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay cuentas conectadas</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza conectando tu primera cuenta de Gmail o Outlook</p>
                </div>
                @else
                <div class="divide-y divide-gray-200">
                    @foreach($accounts as $account)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <!-- Provider Icon -->
                                <div class="flex-shrink-0">
                                    @if($account->provider === 'gmail')
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.545l8.073-6.052C21.69 2.28 24 3.434 24 5.457z"/>
                                        </svg>
                                    </div>
                                    @else
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7.88 12.04q0 .45-.11.87-.1.41-.33.74-.22.33-.58.52-.37.2-.87.2t-.85-.2q-.35-.21-.57-.55-.22-.33-.33-.75-.1-.42-.1-.86t.1-.87q.1-.43.34-.76.22-.34.59-.54.36-.2.87-.2t.86.2q.35.21.57.55.22.34.31.77.1.43.1.88zM24 12v9.38q0 .46-.33.8-.33.32-.8.32H7.13q-.46 0-.8-.33-.32-.33-.32-.8V18H1q-.41 0-.7-.3-.3-.29-.3-.7V7q0-.41.3-.7Q.58 6 1 6h6.5V2.2q0-.44.32-.77.34-.33.8-.33h12.87q.47 0 .8.33.33.33.33.77V12zM19.5 9.5L21 8l-3.2-3.2-1.5 1.5L14.3 4.3 13 5.6 15.8 8.4l-1.5 1.5-3.2-3.2-1.5 1.5L12.8 11l-1.5 1.5 3.2 3.2 1.5-1.5 2.2 2.2 1.3-1.3-2.8-2.8 1.5-1.5zm-6.5-8H8.13q-.15 0-.25.1t-.1.25v4.5q0 .15.1.25t.25.1h4.87q.15 0 .25-.1t.1-.25v-4.5q0-.15-.1-.25t-.25-.1z"/>
                                        </svg>
                                    </div>
                                    @endif
                                </div>

                                <!-- Account Info -->
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center">
                                        <h3 class="text-sm font-medium text-gray-900">{{ $account->email_address }}</h3>
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $account->provider_color }}-100 text-{{ $account->provider_color }}-800">
                                            {{ $account->provider_name }}
                                        </span>
                                        @if($account->sync_status === 'active')
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1"></span>
                                            Activa
                                        </span>
                                        @elseif($account->sync_status === 'error')
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Error
                                        </span>
                                        @else
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Pausada
                                        </span>
                                        @endif
                                    </div>
                                    @if($account->display_name)
                                    <p class="text-sm text-gray-500">{{ $account->display_name }}</p>
                                    @endif
                                    <div class="mt-1 text-xs text-gray-500">
                                        @if($account->last_sync_at)
                                        Última sincronización: {{ $account->last_sync_at->diffForHumans() }}
                                        @else
                                        Sincronización pendiente
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="ml-4 flex items-center gap-2">
                                <!-- Toggle Sync -->
                                <button
                                    onclick="toggleSync({{ $account->id }})"
                                    class="px-3 py-1.5 text-sm {{ $account->sync_enabled ? 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }} rounded-md transition">
                                    {{ $account->sync_enabled ? 'Pausar' : 'Activar' }}
                                </button>

                                <!-- Sync Now -->
                                <button
                                    onclick="syncNow({{ $account->id }})"
                                    class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-md transition">
                                    Sincronizar
                                </button>

                                <!-- Delete -->
                                <form method="POST" action="{{ route('email-accounts.destroy', $account) }}"
                                      onsubmit="return confirm('¿Estás seguro de desconectar esta cuenta?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-sm bg-red-50 text-red-700 hover:bg-red-100 rounded-md transition">
                                        Desconectar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Sync Logs (últimos 3) -->
                        @if($account->syncLogs->isNotEmpty())
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <h4 class="text-xs font-medium text-gray-700 mb-2">Historial de Sincronización</h4>
                            <div class="space-y-2">
                                @foreach($account->syncLogs->take(3) as $log)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        @if($log->status === 'success')
                                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                        @else
                                        <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                        @endif
                                        <span class="text-gray-600">
                                            {{ ucfirst($log->sync_type) }} - {{ $log->started_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                    <div class="text-gray-500">
                                        <span class="text-green-600">+{{ $log->emails_created }}</span> /
                                        <span class="text-blue-600">~{{ $log->emails_updated }}</span>
                                        @if($log->emails_failed > 0)
                                        / <span class="text-red-600">!{{ $log->emails_failed }}</span>
                                        @endif
                                        <span class="ml-2">{{ $log->duration_formatted }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Toggle sync enable/disable
        function toggleSync(accountId) {
            fetch(`/email-accounts/${accountId}/toggle-sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cambiar el estado de sincronización');
            });
        }

        // Sync now
        function syncNow(accountId) {
            fetch(`/email-accounts/${accountId}/sync-now`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al iniciar la sincronización');
            });
        }
    </script>
</body>
</html>
