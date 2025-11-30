<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Emails - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }

        .sidebar {
            transition: transform 0.2s ease;
        }

        @media (max-width: 768px) {
            .sidebar.hidden {
                transform: translateX(-100%);
            }
        }

        .email-item:hover {
            background-color: #f9fafb;
        }

        .email-item.unread {
            background-color: #fafbfc;
            border-left: 3px solid #6366f1;
        }

        .priority-high {
            border-left-color: #ef4444 !important;
        }

        .priority-urgent {
            border-left-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }

        .category-badge {
            transition: all 0.15s ease;
        }

        .category-badge:hover {
            transform: translateX(2px);
        }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]" x-data="emailApp()">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('emails.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 md:ml-56">
            <!-- Top Bar -->
            @include('emails.partials.header')

            <!-- Banner: No Email Accounts Connected -->
            @if(!$hasEmailAccounts)
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-200">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Conecta tu cuenta de email</h3>
                                    <p class="text-sm text-gray-600 mt-1">Para comenzar a usar Vera, conecta tu cuenta de
                                        Gmail o Outlook y empieza a sincronizar tus emails automáticamente.</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('email-accounts.google.redirect') }}"
                                    class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-all transform hover:scale-105">
                                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                                        <path fill="#4285F4"
                                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                        <path fill="#34A853"
                                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                        <path fill="#FBBC05"
                                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                        <path fill="#EA4335"
                                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                    </svg>
                                    <span class="font-medium text-gray-700">Conectar Gmail</span>
                                </a>
                                <a href="{{ route('email-accounts.microsoft.redirect') }}"
                                    class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-all transform hover:scale-105">
                                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                                        <path fill="#f25022" d="M0 0h11v11H0z" />
                                        <path fill="#00a4ef" d="M13 0h11v11H13z" />
                                        <path fill="#7fba00" d="M0 13h11v11H0z" />
                                        <path fill="#ffb900" d="M13 13h11v11H13z" />
                                    </svg>
                                    <span class="font-medium text-gray-700">Conectar Outlook</span>
                                </a>
                                <a href="{{ route('email-accounts.index') }}"
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium underline">
                                    Gestionar cuentas →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Loading/Syncing Screen -->
            @if($syncingAccount)
                <div class="bg-white border-b border-gray-200" x-data="syncProgress({{ $syncingAccount->id }})">
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full
                          mb-4 shadow-lg">
                                <!-- Animated envelope icon -->
                                <svg class="w-10 h-10 text-white animate-pulse" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5
                          19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">Sincronizando tu bandeja de entrada</h2>
                            <p class="text-lg text-gray-600" x-text="statusMessage">Conectando con Gmail...</p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-700">Progreso de sincronización</span>
                                <span class="text-sm font-semibold text-indigo-600"
                                    x-text="progressPercentage + '%'">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500 ease-out
                          shadow-lg" :style="'width: ' + progressPercentage + '%'" x-show="progressPercentage > 0"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform scale-x-0"
                                    x-transition:enter-end="opacity-100 transform scale-x-100">
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600 mb-1">Emails sincronizados</p>
                                        <p class="text-3xl font-bold text-gray-900" x-text="syncedCount">0</p>
                                    </div>
                                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600 mb-1">Total esperado</p>
                                        <p class="text-3xl font-bold text-gray-900" x-text="totalExpected">--</p>
                                    </div>
                                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2
                           2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0
                          01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-green-50 to-teal-50 rounded-lg p-6 border border-green-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600 mb-1">Estado</p>
                                        <p class="text-xl font-semibold text-gray-900" x-text="syncStatus">Iniciando...</p>
                                    </div>
                                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962
                          7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info message -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start space-x-3">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9
                          9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm text-blue-800 font-medium">Esto puede tardar unos minutos</p>
                                <p class="text-sm text-blue-700 mt-1">Estamos sincronizando todos tus emails. No cierres
                                    esta ventana, se actualizará
                                    automáticamente cuando termine.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Email Content Area -->
            <div class="flex h-[calc(100vh-56px)]">
                <!-- Email List -->
                <div class="w-full lg:w-2/5 border-r border-gray-200 overflow-y-auto bg-white">
                    @include('emails.partials.email-list')
                </div>

                <!-- Email Detail / Preview -->
                <div class="hidden lg:block lg:w-3/5 overflow-y-auto bg-white">
                    @include('emails.partials.email-detail')
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleSidebar()">
    </div>

    <script>
        // Auto-refresh de emails cada 2 minutos
        let autoRefreshInterval = null;
        let autoRefreshEnabled = {{ $hasEmailAccounts ? 'true' : 'false' }};

        function startAutoRefresh() {
            if (!autoRefreshEnabled) return;

            // Limpiar intervalo anterior si existe
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }

            // Auto-refresh cada 2 minutos (120000 ms)
            autoRefreshInterval = setInterval(() => {
                console.log('Auto-refreshing emails...');
                window.location.reload();
            }, 120000); // 2 minutos
        }

        // Iniciar auto-refresh cuando se carga la página
        if (autoRefreshEnabled) {
            startAutoRefresh();
            console.log('Auto-refresh activado: cada 2 minutos');
        }

        // Limpiar intervalo cuando el usuario sale de la página
        window.addEventListener('beforeunload', () => {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });

        // Función para manejar el progreso de sincronización
        function syncProgress(accountId) {
            return {
                accountId: accountId,
                progressPercentage: 0,
                syncedCount: 0,
                totalExpected: 0,
                syncStatus: 'Iniciando...',
                statusMessage: 'Conectando con Gmail...',
                isSyncing: true,
                pollInterval: null,

                init() {
                    console.log('🚀 Iniciando monitor de sincronización para cuenta:', this.accountId);
                    this.startPolling();
                },

                async fetchSyncStatus() {
                    try {
                        const response = await fetch(`/email-accounts/${this.accountId}/sync-status`);
                        const data = await response.json();

                        console.log('📊 Status update:', data);

                        // Actualizar datos
                        this.progressPercentage = data.sync.progress_percentage || 0;
                        this.syncedCount = data.sync.synced_count || 0;
                        this.totalExpected = data.sync.total_expected || 0;
                        this.isSyncing = data.sync.is_syncing;

                        // Actualizar mensajes según estado
                        if (data.sync.sync_type === 'initial') {
                            this.syncStatus = 'Sincronización inicial';
                            this.statusMessage = `Sincronizando ${this.syncedCount} de ${this.totalExpected} emails...`;
                        } else if (!this.isSyncing && data.sync.has_completed_initial_sync) {
                            this.syncStatus = '¡Completado!';
                            this.statusMessage = '✅ Sincronización completada exitosamente';
                            this.progressPercentage = 100;

                            // Detener polling y recargar página después de 2 segundos
                            this.stopPolling();
                            setTimeout(() => {
                                console.log('✅ Sincronización completada, recargando página...');
                                window.location.reload();
                            }, 2000);
                        }

                        // Si hay error
                        if (data.sync.sync_error) {
                            this.syncStatus = 'Error';
                            this.statusMessage = data.sync.sync_error;
                            this.stopPolling();
                        }

                    } catch (error) {
                        console.error('❌ Error al obtener estado de sincronización:', error);
                    }
                },

                startPolling() {
                    // Hacer la primera consulta inmediatamente
                    this.fetchSyncStatus();

                    // Luego consultar cada 3 segundos
                    this.pollInterval = setInterval(() => {
                        this.fetchSyncStatus();
                    }, 3000);

                    console.log('⏰ Polling iniciado (cada 3 segundos)');
                },

                stopPolling() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                        console.log('⏸️ Polling detenido');
                    }
                },

                destroy() {
                    this.stopPolling();
                }
            }
        }

        function emailApp() { }


        function emailApp() {
            return {
                selectedEmails: [],
                showBulkActions: false,
                currentFilter: '{{ request()->filter ?? "all" }}',
                currentCategory: '{{ request()->category ?? "" }}',

                toggleEmailSelection(emailId) {
                    if (this.selectedEmails.includes(emailId)) {
                        this.selectedEmails = this.selectedEmails.filter(id => id !== emailId);
                    } else {
                        this.selectedEmails.push(emailId);
                    }
                    this.showBulkActions = this.selectedEmails.length > 0;
                },

                selectAllEmails() {
                    const checkboxes = document.querySelectorAll('.email-checkbox');
                    const allSelected = this.selectedEmails.length === checkboxes.length;

                    if (allSelected) {
                        this.selectedEmails = [];
                    } else {
                        this.selectedEmails = Array.from(checkboxes).map(cb => parseInt(cb.value));
                    }
                    this.showBulkActions = this.selectedEmails.length > 0;
                },

                async performAction(emailId, action) {
                    try {
                        const response = await fetch(`/emails/${emailId}/${action}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async bulkAction(action) {
                    if (this.selectedEmails.length === 0) return;

                    try {
                        const response = await fetch('/emails/bulk-action', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                action: action,
                                email_ids: this.selectedEmails
                            })
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>

</html>