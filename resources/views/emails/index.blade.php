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
        [x-cloak] { display: none !important; }

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

        /* Densidades de vista */
        .email-item.density-compact {
            padding: 8px 16px !important;
        }

        .email-item.density-comfortable {
            padding: 12px 16px !important;
        }

        .email-item.density-spacious {
            padding: 16px 20px !important;
        }

        /* Densidades de vista - DIFERENCIAS MÁS NOTORIAS */
        .email-item.density-compact {
            padding: 6px 12px !important;
        }

        .email-item.density-compact .text-sm {
            font-size: 0.813rem !important;
            line-height: 1.3 !important;
        }

        .email-item.density-compact .text-xs {
            font-size: 0.688rem !important;
        }

        .email-item.density-comfortable {
            padding: 14px 16px !important;
        }

        .email-item.density-spacious {
            padding: 20px 24px !important;
        }

        .email-item.density-spacious .text-sm {
            font-size: 0.938rem !important;
            line-height: 1.6 !important;
        }

        .email-item.density-spacious .text-xs {
            font-size: 0.813rem !important;
        }

        /* Mejores hover effects */
        .email-item:hover {
            background-color: #f3f4f6;
            transform: translateX(2px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* Mejores indicadores de no leído */
        .email-item.unread {
            background-color: #fefce8;
            border-left: 4px solid #6366f1;
        }

        .email-item.unread:hover {
            background-color: #fef3c7;
        }

        .email-item {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .email-item * {
            transition: font-size 0.2s ease, line-height 0.2s ease;
        }

        /* Email seleccionado (vista previa activa) */
        .email-item.email-selected {
            background-color: #ede9fe !important;
            border-left: 4px solid #7c3aed !important;
            box-shadow: inset 0 0 0 1px #c4b5fd;
        }

        .email-item.email-selected:hover {
            background-color: #ddd6fe !important;
        }

        /* Tooltip para doble click */
        .email-item {
            position: relative;
        }

        .email-item::after {
            content: '💡 Doble click para abrir completo';
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1000;
        }

        .email-item:hover::after {
            opacity: 0.9;
            transition-delay: 1s;
        }

        .email-item.email-selected::after {
            content: '💡 Doble click o usa el botón ↗️';
        }

        /* Ocultar elementos con x-cloak hasta que Alpine.js esté listo */
        [x-cloak] {
            display: none !important;
        }

        /* Animaciones suaves para el modal */
        .modal-enter {
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]" x-data="emailApp()">

    <script>
        // Registrar componente Alpine.js para vista previa de emails
        document.addEventListener('alpine:init', () => {
            Alpine.data('emailPreview', () => ({
                selectedEmailId: null,
                email: {},
                loading: false,

                async loadEmail(emailId) {
                    this.loading = true;
                    this.selectedEmailId = emailId;

                    try {
                        const response = await fetch(`/emails/api/${emailId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            console.error('Error al cargar email:', response.status);
                            return;
                        }

                        const data = await response.json();
                        this.email = data.email;

                    } catch (error) {
                        console.error('Error en AJAX:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async toggleStar() {
                    try {
                        const response = await fetch(`/emails/${this.email.id}/toggle-star`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.email.is_starred = data.is_starred;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async archiveEmail() {
                    if (!confirm('¿Archivar este email?')) return;

                    try {
                        const response = await fetch(`/emails/${this.email.id}/archive`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async deleteEmail() {
                    if (!confirm('¿Eliminar este email?')) return;

                    try {
                        const response = await fetch(`/emails/${this.email.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleString('es-ES', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                }
            }));
        });
    </script>

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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5
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
                                                                                  shadow-lg"
                                    :style="'width: ' + progressPercentage + '%'" x-show="progressPercentage > 0"
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2
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
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962
                                                                                  7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
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

    <!-- Settings Modal (Estilo OpenAI) -->
    @include('components.settings-modal')

    <!-- Portal para dropdowns (fuera del flujo con overflow) -->
    <div id="dropdowns-portal" class="fixed" style="z-index: 99999;"></div>

    <script>
        // Auto-refresh de emails cada 2 minutos (SIN reload visible)
        let autoRefreshInterval = null;
        let autoRefreshEnabled = {{ $hasEmailAccounts ? 'true' : 'false' }};

        // Función para actualizar emails vía AJAX
        async function refreshEmailsAjax() {
            try {
                console.log('🔄 Actualizando emails vía AJAX...');

                // Obtener filtros actuales de la URL
                const urlParams = new URLSearchParams(window.location.search);
                const filter = urlParams.get('filter') || '';
                const category = urlParams.get('category') || '';
                const search = urlParams.get('search') || '';

                // Construir URL de la API
                let apiUrl = '/emails/api/list?';
                if (filter) apiUrl += `filter=${filter}&`;
                if (category) apiUrl += `category=${category}&`;
                if (search) apiUrl += `search=${search}&`;

                // Hacer petición AJAX
                const response = await fetch(apiUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.error('❌ Error al actualizar emails:', response.status);
                    return;
                }

                const data = await response.json();

                // Actualizar la lista de emails en el DOM
                updateEmailList(data.emails);

                // Actualizar estadísticas si existen
                updateStats(data.stats);

                console.log('✅ Emails actualizados:', data.emails.length);

            } catch (error) {
                console.error('❌ Error en AJAX refresh:', error);
            }
        }

        // Función para actualizar el DOM con nuevos emails
        // Función para actualizar el DOM con nuevos emails
        // ==========================================
        // FUNCIÓN AUXILIAR: WORKFLOW BADGE HTML
        // ==========================================
        function getWorkflowBadgeHtml(email) {
            const workflowColors = {
                'unread': 'bg-blue-100 text-blue-700',
                'pending_validation': 'bg-yellow-100 text-yellow-700',
                'pending_response': 'bg-orange-100 text-orange-700',
                'in_progress': 'bg-purple-100 text-purple-700',
                'in_review': 'bg-indigo-100 text-indigo-700',
                'approved': 'bg-green-100 text-green-700',
                'completed': 'bg-emerald-100 text-emerald-700',
                'archived': 'bg-gray-100 text-gray-600',
                'no_action_needed': 'bg-slate-100 text-slate-600',
            };

            const workflowLabels = {
                'unread': '📬 Sin leer',
                'pending_validation': '⏳ Pendiente',
                'pending_response': '✉️ Responder',
                'in_progress': '🔄 En curso',
                'in_review': '👁️ Revisión',
                'approved': '✅ Aprobado',
                'completed': '✔️ Completado',
                'archived': '📦 Archivado',
                'no_action_needed': '➖ Sin acción',
            };

            const status = email.workflow_status || 'unread';
            const colorClass = workflowColors[status] || 'bg-gray-100 text-gray-600';
            const label = workflowLabels[status] || status;

            return `
                <div class="relative" x-data="{ dropdownStyle: {} }" x-ref="workflowContainer">
                    <button @click.stop="workflowOpen = !workflowOpen; if(workflowOpen) { const rect = $el.getBoundingClientRect(); dropdownStyle = { top: rect.bottom + 4 + 'px', left: rect.left + 'px' }; }"
                        x-ref="workflowButton"
                        class="inline-flex items-center text-[10px] px-2 py-1 rounded-full font-medium ${colorClass} hover:opacity-80 transition-opacity">
                        ${label}
                        <svg class="w-2.5 h-2.5 ml-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <template x-teleport="#dropdowns-portal">
                        <div x-show="workflowOpen"
                             @click.away="workflowOpen = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="fixed w-48 bg-white rounded-lg shadow-2xl border border-gray-200"
                             :style="{ top: dropdownStyle.top, left: dropdownStyle.left, zIndex: 99999 }"
                             x-cloak>
                            <div class="py-1">
                                <button @click.stop="changeWorkflowStatus(${email.id}, 'pending_validation'); workflowOpen = false"
                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                    ⏳ Pendiente validación
                                </button>
                                <button @click.stop="changeWorkflowStatus(${email.id}, 'pending_response'); workflowOpen = false"
                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                    ✉️ Requiere respuesta
                                </button>
                                <button @click.stop="changeWorkflowStatus(${email.id}, 'in_progress'); workflowOpen = false"
                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                    🔄 En curso
                                </button>
                                <button @click.stop="changeWorkflowStatus(${email.id}, 'in_review'); workflowOpen = false"
                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    👁️ En revisión
                                </button>
                                <button @click.stop="changeWorkflowStatus(${email.id}, 'completed'); workflowOpen = false"
                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    ✔️ Completado
                                </button>
                                <div class="border-t border-gray-200 my-1"></div>
                                <button @click.stop="changeWorkflowStatus(${email.id}, 'no_action_needed'); workflowOpen = false"
                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                    ➖ Sin acción necesaria
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            `;
        }

        // ==========================================
        // FUNCIÓN AUXILIAR: QUICK ACTIONS HTML
        // ==========================================
        function getQuickActionsHtml(email) {
            return `
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <!-- Mover a carpeta -->
                    <div class="relative" x-data="{ folderDropdownStyle: {} }" x-ref="folderContainer">
                        <button @click.stop="folderMenuOpen = !folderMenuOpen; if(folderMenuOpen) { const rect = $el.getBoundingClientRect(); folderDropdownStyle = { top: rect.bottom + 4 + 'px', left: rect.left + 'px' }; }"
                            x-ref="folderButton"
                            class="p-1 hover:bg-gray-200 rounded transition-colors"
                            title="Mover a carpeta">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </button>

                        <template x-teleport="#dropdowns-portal">
                            <div x-show="folderMenuOpen"
                                 @click.away="folderMenuOpen = false"
                                 x-transition
                                 class="fixed w-48 bg-white rounded-lg shadow-2xl border border-gray-200 max-h-64 overflow-y-auto"
                                 :style="{ top: folderDropdownStyle.top, left: folderDropdownStyle.left, zIndex: 99999 }"
                                 x-cloak>
                                <div class="py-1" x-data="{ folders: [] }" x-init="
                                    fetch('/email-folders/tree')
                                        .then(r => r.json())
                                        .then(data => folders = data.folders || [])
                                ">
                                    <template x-for="folder in folders" :key="folder.id">
                                        <button @click.stop="moveEmailToFolder(${email.id}, folder.id); folderMenuOpen = false"
                                            class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                            <span x-text="folder.name"></span>
                                        </button>
                                    </template>
                                    <template x-if="folders.length === 0">
                                        <p class="px-3 py-2 text-xs text-gray-400">No hay carpetas</p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Estrella -->
                    <button @click.stop="performAction(${email.id}, 'toggle-star')"
                        class="p-1 hover:bg-gray-200 rounded transition-colors"
                        title="${email.is_starred ? 'Quitar estrella' : 'Destacar'}">
                        <svg class="w-3.5 h-3.5 ${email.is_starred ? 'text-yellow-500 fill-yellow-500' : 'text-gray-500'}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </button>
                </div>
            `;
        }

        function updateEmailList(emails) {
            // Usar ID específico para encontrar el contenedor correcto
            const emailListContainer = document.getElementById('emails-container');

            if (!emailListContainer) {
                console.error('❌ No se encontró el contenedor de emails');
                return;
            }

            // Si no hay emails, mostrar mensaje
            if (emails.length === 0) {
                emailListContainer.innerHTML = `
              <div class="flex flex-col items-center justify-center h-64 text-center px-4">
                  <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 
  00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                  </svg>
                  <h3 class="text-lg font-semibold text-gray-900 mb-1">No hay emails</h3>
                  <p class="text-sm text-gray-500">Tu bandeja de entrada está vacía.</p>
              </div>
          `;
                return;
            }

            // Construir HTML de emails (manteniendo estructura EXACTA del original)
            let emailsHtml = '';
            emails.forEach(email => {
                const isUnread = !email.is_read;
                const categoryColors = {
                    'primary': '#6366f1',
                    'promotions': '#f59e0b',
                    'social': '#10b981',
                    'updates': '#3b82f6',
                    'forums': '#8b5cf6',
                    'spam': '#ef4444',
                    'trash': '#6b7280'
                };
                const categoryIcons = {
                    'primary': '📧',
                    'promotions': '🏷️',
                    'social': '👥',
                    'updates': '🔔',
                    'forums': '💬',
                    'spam': '⚠️',
                    'trash': '🗑️'
                };

                const categoryColor = categoryColors[email.category] || '#6b7280';
                const categoryIcon = categoryIcons[email.category] || '📧';

                const density = localStorage.getItem('email-density') || 'comfortable';
                emailsHtml += `
      <div class="email-item group density-${density} border-b border-gray-100 cursor-pointer relative ${isUnread ? 'unread' : ''}"
                  x-data="{ workflowOpen: false, folderMenuOpen: false }"
                  @close-dropdowns.window="workflowOpen = false; folderMenuOpen = false"
                  onclick="window.location='/emails/${email.id}'">
                  
                  <div class="flex items-start gap-3">
                      <input type="checkbox" value="${email.id}"
                          class="email-checkbox mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
                          onclick="event.stopPropagation()"
                          x-on:click="toggleEmailSelection(${email.id})"
                          x-bind:checked="selectedEmails.includes(${email.id})">
                      
                      <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between mb-1">
                              <div class="flex items-center gap-2 min-w-0 flex-1">
                                  <span class="text-sm ${isUnread ? 'font-semibold text-gray-900' : 'font-medium text-gray-700'} 
  truncate">
                                      ${email.from_name || email.from_email}
                                  </span>
                                  ${email.category ? `
                                      <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                                          style="background-color: ${categoryColor}20; color: ${categoryColor}">
                                          ${categoryIcon} ${email.category.charAt(0).toUpperCase() + email.category.slice(1)}
                                      </span>
                                  ` : ''}
                              </div>
                              <span class="text-xs text-gray-500 ml-2 flex-shrink-0">
                                  ${formatDate(email.received_at)}
                              </span>
                          </div>
                          
                          <div class="flex items-center gap-2 mb-1">
                              <p class="text-sm ${isUnread ? 'font-medium text-gray-900' : 'text-gray-600'} truncate flex-1">
                                  ${email.subject || '(Sin asunto)'}
                              </p>

                              ${getWorkflowBadgeHtml(email)}
                              ${getQuickActionsHtml(email)}
                          </div>
                          
                          <div class="flex items-center gap-2">
                              <p class="text-xs text-gray-500 truncate flex-1">
                                  ${email.excerpt || ''}
                              </p>
                          </div>
                          
                          ${email.has_attachments || email.vera_processed ? `
                              <div class="flex items-center gap-3 mt-2">
                                  ${email.has_attachments ? `
                                      <div class="flex items-center gap-1 text-gray-500">
                                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 
  6.585a6 6 0 108.486 8.486L20.5 13" />
                                          </svg>
                                          <span class="text-[10px]">${email.attachments_count || ''}</span>
                                      </div>
                                  ` : ''}
                                  
                                  ${email.vera_processed ? `
                                      <div class="flex items-center gap-1 text-purple-600" title="Procesado por Vera">
                                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                          </svg>
                                          <span class="text-[10px] font-medium">Vera</span>
                                      </div>
                                  ` : ''}
                              </div>
                          ` : ''}
                      </div>
                      
                      ${email.is_starred ? `
                          <button onclick="event.stopPropagation(); performAction(${email.id}, 'toggle-star')"
                              class="flex-shrink-0 mt-1">
                              <svg class="w-4 h-4 text-yellow-500 fill-yellow-500" fill="none" stroke="currentColor"
                                  viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 
  1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 
  2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 
  00.951-.69l1.519-4.674z" />
                              </svg>
                          </button>
                      ` : ''}
                  </div>
              </div>
          `;
            });

            // Actualizar contenido manteniendo el contenedor intacto
            emailListContainer.innerHTML = emailsHtml;
        }

        // Función para actualizar estadísticas
        function updateStats(stats) {
            // Actualizar contadores en sidebar si existen
            const totalEl = document.querySelector('[data-stat="total"]');
            const unreadEl = document.querySelector('[data-stat="unread"]');
            const starredEl = document.querySelector('[data-stat="starred"]');

            if (totalEl) totalEl.textContent = stats.total || 0;
            if (unreadEl) unreadEl.textContent = stats.unread || 0;
            if (starredEl) starredEl.textContent = stats.starred || 0;
        }

        // Función helper para formatear fecha
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Ahora';
            if (diffMins < 60) return `${diffMins}m`;
            if (diffHours < 24) return `${diffHours}h`;
            if (diffDays < 7) return `${diffDays}d`;

            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        }

        function startAutoRefresh() {
            if (!autoRefreshEnabled) return;

            // Limpiar intervalo anterior si existe
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }

            // Auto-refresh cada 2 minutos sin reload visible
            autoRefreshInterval = setInterval(() => {
                refreshEmailsAjax();
            }, 120000); // 2 minutos
        }

        // Iniciar auto-refresh cuando se carga la página
        if (autoRefreshEnabled) {
            startAutoRefresh();
            console.log('✅ Auto-refresh AJAX activado: cada 2 minutos (sin reload visible)');
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

                            // Detener polling y actualizar emails vía AJAX
                            this.stopPolling();
                            setTimeout(() => {
                                console.log('✅ Sincronización completada, actualizando emails vía AJAX...');
                                refreshEmailsAjax(); // Usar AJAX en lugar de reload

                                // Después de sincronizar, verificar si necesita configurar Vera
                                checkVeraOnboarding();
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

        // Función global para cargar email en vista previa
        let currentSelectedEmailId = null;
        let clickTimeout = null;

        function handleEmailClick(emailId, event) {
            // Prevenir que se dispare también el doble click
            if (clickTimeout !== null) {
                return;
            }

            // Esperar 300ms para distinguir entre click simple y doble click
            clickTimeout = setTimeout(() => {
                loadEmailPreview(emailId);
                clickTimeout = null;
            }, 300);
        }

        function openEmailFullPage(emailId) {
            // Cancelar el timeout del click simple
            if (clickTimeout !== null) {
                clearTimeout(clickTimeout);
                clickTimeout = null;
            }

            // Abrir página completa
            window.location.href = `/emails/${emailId}`;
        }

        function loadEmailPreview(emailId) {
            // Remover highlight del anterior
            if (currentSelectedEmailId) {
                const prevEmail = document.getElementById(`email-${currentSelectedEmailId}`);
                if (prevEmail) {
                    prevEmail.classList.remove('email-selected');
                }
            }

            // Agregar highlight al nuevo
            const emailElement = document.getElementById(`email-${emailId}`);
            if (emailElement) {
                emailElement.classList.add('email-selected');
            }

            // Guardar el ID actual
            currentSelectedEmailId = emailId;

            // Disparar evento Alpine.js
            window.dispatchEvent(new CustomEvent('load-email', {
                detail: { emailId: emailId }
            }));
        }

        // Función para verificar si necesita configurar Vera
        function checkVeraOnboarding() {
            // Solo verificar si hay cuentas de email conectadas y NO hay sincronización en curso
            @if($hasEmailAccounts && !$syncingAccount)
                fetch('/vera/check-onboarding')
                    .then(response => response.json())
                    .then(data => {
                        if (data.needs_onboarding) {
                            console.log('🚀 Vera necesita configuración, abriendo modal...');

                            setTimeout(() => {
                                // Abrir el modal de configuración
                                window.dispatchEvent(new CustomEvent('open-settings-modal'));

                                // Cambiar a la categoría Vera
                                setTimeout(() => {
                                    const modalElement = document.querySelector('[x-data*="settingsModal"]');
                                    if (modalElement && modalElement.__x) {
                                        modalElement.__x.$data.activeCategory = 'vera';
                                        modalElement.__x.$data.hasVeraConfig = false;
                                        console.log('✅ Modal abierto en categoría Vera');
                                    }
                                }, 300);
                            }, 1000);
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error verificando onboarding:', error);
                    });
            @endif
        }

        // Verificar onboarding cuando carga la página (si no está sincronizando)
        document.addEventListener('DOMContentLoaded', function() {
            @if($hasEmailAccounts && !$syncingAccount)
                checkVeraOnboarding();
            @endif
        });

        // ==========================================
        // COMPONENTE ALPINE.JS: ÁRBOL DE CARPETAS
        // ==========================================
        document.addEventListener('alpine:init', () => {
            Alpine.data('foldersTree', () => ({
                folders: [],
                loading: true,
                selectedFolderId: null,

                init() {
                    this.loadFolders();
                },

                async loadFolders() {
                    try {
                        const response = await fetch('/email-folders/tree', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error('Error al cargar carpetas');

                        const data = await response.json();
                        this.folders = data.folders || [];
                    } catch (error) {
                        console.error('Error:', error);
                        this.folders = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async selectFolder(folderId) {
                    this.selectedFolderId = folderId;

                    try {
                        const response = await fetch(`/email-folders/${folderId}/emails`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error('Error al cargar emails');

                        const data = await response.json();

                        // Actualizar el título del header para mostrar la carpeta seleccionada
                        const headerTitle = document.querySelector('header h1');
                        if (headerTitle && data.folder) {
                            headerTitle.textContent = `📁 ${data.folder.name}`;
                        }

                        // Actualizar contador de mensajes
                        const messageCount = document.querySelector('header span.text-gray-500');
                        if (messageCount) {
                            messageCount.textContent = `${data.count || 0} mensajes`;
                        }

                        updateEmailList(data.emails);
                    } catch (error) {
                        console.error('Error al cargar emails de carpeta:', error);
                    }
                },

                getFolderIcon(icon) {
                    const icons = {
                        folder: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>',
                        star: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
                        briefcase: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/></svg>',
                        user: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>',
                        archive: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>'
                    };
                    return icons[icon] || icons.folder;
                },

                openCreateFolderModal() {
                    window.dispatchEvent(new CustomEvent('open-folder-modal'));
                },

                openFolderMenu(folderId) {
                    // TODO: Implementar menú contextual
                    console.log('Abrir menú para carpeta:', folderId);
                }
            }));
        });

        // ==========================================
        // FUNCIÓN: CAMBIAR WORKFLOW STATUS
        // ==========================================
        async function changeWorkflowStatus(emailId, newStatus) {
            try {
                const response = await fetch(`/email-workflow/${emailId}/change-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                if (!response.ok) throw new Error('Error al cambiar estado');

                const data = await response.json();
                console.log('✅ Estado de workflow actualizado:', data);

                // Recargar la página para ver el cambio
                window.location.reload();

            } catch (error) {
                console.error('❌ Error al cambiar estado de workflow:', error);
                alert('Error al cambiar el estado. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: MOVER EMAIL A CARPETA
        // ==========================================
        async function moveEmailToFolder(emailId, folderId) {
            try {
                const response = await fetch(`/email-folders/${folderId}/assign-email`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email_id: emailId })
                });

                if (!response.ok) throw new Error('Error al mover email');

                const data = await response.json();
                console.log('✅ Email movido a carpeta:', data);

                // Mostrar notificación de éxito (sin recargar)
                showNotification('Email movido a carpeta correctamente', 'success');

                // Opcional: Actualizar contador de carpetas en el sidebar
                // sin recargar toda la página
                if (window.Alpine && window.Alpine.store) {
                    // Recargar árbol de carpetas
                    window.location.reload(); // Por ahora, recargar para simplicidad
                }

            } catch (error) {
                console.error('❌ Error al mover email a carpeta:', error);
                alert('Error al mover el email. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: MOSTRAR NOTIFICACIÓN
        // ==========================================
        function showNotification(message, type = 'success') {
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';

            const notification = document.createElement('div');
            notification.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Fade out después de 3 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Atajos de teclado: Flechas arriba/abajo para navegar entre emails
        document.addEventListener('keydown', (event) => {
            // Solo activar si no estamos en un input/textarea
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                return;
            }

            // Obtener todos los emails visibles
            const emailItems = Array.from(document.querySelectorAll('.email-item'));
            if (emailItems.length === 0) return;

            // Encontrar el email actualmente seleccionado
            let currentIndex = emailItems.findIndex(item => item.classList.contains('email-selected'));

            // Si no hay ninguno seleccionado, empezar desde el primero
            if (currentIndex === -1 && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
                currentIndex = 0;
                const firstEmailId = emailItems[0].getAttribute('data-email-id');
                if (firstEmailId) {
                    loadEmailPreview(parseInt(firstEmailId));
                    emailItems[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            // Navegar con flechas
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                const nextIndex = Math.min(currentIndex + 1, emailItems.length - 1);
                if (nextIndex !== currentIndex) {
                    const nextEmailId = emailItems[nextIndex].getAttribute('data-email-id');
                    if (nextEmailId) {
                        loadEmailPreview(parseInt(nextEmailId));
                        emailItems[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                const prevIndex = Math.max(currentIndex - 1, 0);
                if (prevIndex !== currentIndex) {
                    const prevEmailId = emailItems[prevIndex].getAttribute('data-email-id');
                    if (prevEmailId) {
                        loadEmailPreview(parseInt(prevEmailId));
                        emailItems[prevIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        });
    </script>

    <!-- Modal Crear/Editar Carpeta -->
    <div x-data="folderModal()"
         @open-folder-modal.window="openModal()"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">

        <div @click.away="closeModal()"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="bg-white rounded-lg shadow-xl w-full max-w-md">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" x-text="folderId ? 'Editar Carpeta' : 'Nueva Carpeta'"></h3>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitForm()" class="px-6 py-4 space-y-4">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre *
                    </label>
                    <input type="text"
                           x-model="formData.name"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Ej: Proyectos 2026">
                </div>

                <!-- Carpeta Padre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Carpeta Padre
                    </label>
                    <select x-model="formData.parent_folder_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Carpeta raíz --</option>
                        <template x-for="folder in allFolders" :key="folder.id">
                            <option :value="folder.id" x-text="folder.full_path"></option>
                        </template>
                    </select>
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Color
                    </label>
                    <div class="flex gap-2">
                        <input type="color"
                               x-model="formData.color"
                               class="w-16 h-10 rounded cursor-pointer border border-gray-300">
                        <input type="text"
                               x-model="formData.color"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                               readonly>
                    </div>
                </div>

                <!-- Ícono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ícono
                    </label>
                    <div class="grid grid-cols-5 gap-2">
                        <template x-for="iconOption in iconOptions" :key="iconOption.value">
                            <button type="button"
                                    @click="formData.icon = iconOption.value"
                                    :class="formData.icon === iconOption.value ? 'bg-purple-100 border-purple-500' : 'bg-white border-gray-300'"
                                    class="p-3 border-2 rounded-lg hover:bg-gray-50 transition-colors text-2xl">
                                <span x-text="iconOption.emoji"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción (opcional)
                    </label>
                    <textarea x-model="formData.description"
                              rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="Añade una descripción..."></textarea>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button"
                            @click="closeModal()"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            :disabled="submitting"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!submitting">Guardar</span>
                        <span x-show="submitting">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('folderModal', () => ({
                isOpen: false,
                submitting: false,
                folderId: null,
                allFolders: [],
                formData: {
                    name: '',
                    parent_folder_id: '',
                    color: '#6B7280',
                    icon: 'folder',
                    description: ''
                },
                iconOptions: [
                    { value: 'folder', emoji: '📁' },
                    { value: 'star', emoji: '⭐' },
                    { value: 'briefcase', emoji: '💼' },
                    { value: 'user', emoji: '👤' },
                    { value: 'archive', emoji: '📦' },
                    { value: 'heart', emoji: '❤️' },
                    { value: 'flag', emoji: '🚩' },
                    { value: 'check', emoji: '✅' },
                    { value: 'clock', emoji: '⏰' },
                    { value: 'document', emoji: '📄' }
                ],

                async openModal(folderId = null) {
                    this.folderId = folderId;
                    this.isOpen = true;

                    // Cargar lista de carpetas para selector de padre
                    await this.loadAllFolders();

                    // Si es edición, cargar datos de carpeta
                    if (folderId) {
                        await this.loadFolderData(folderId);
                    } else {
                        this.resetForm();
                    }
                },

                closeModal() {
                    this.isOpen = false;
                    this.resetForm();
                },

                resetForm() {
                    this.formData = {
                        name: '',
                        parent_folder_id: '',
                        color: '#6B7280',
                        icon: 'folder',
                        description: ''
                    };
                    this.folderId = null;
                },

                async loadAllFolders() {
                    try {
                        const response = await fetch('/email-folders/tree');
                        const data = await response.json();
                        this.allFolders = this.flattenFolders(data.folders || []);
                    } catch (error) {
                        console.error('Error al cargar carpetas:', error);
                    }
                },

                flattenFolders(folders, result = []) {
                    folders.forEach(folder => {
                        result.push(folder);
                        if (folder.children && folder.children.length > 0) {
                            this.flattenFolders(folder.children, result);
                        }
                    });
                    return result;
                },

                async loadFolderData(folderId) {
                    try {
                        const response = await fetch(`/email-folders/${folderId}`);
                        const data = await response.json();

                        this.formData = {
                            name: data.folder.name,
                            parent_folder_id: data.folder.parent_folder_id || '',
                            color: data.folder.color || '#6B7280',
                            icon: data.folder.icon || 'folder',
                            description: data.folder.description || ''
                        };
                    } catch (error) {
                        console.error('Error al cargar datos de carpeta:', error);
                    }
                },

                async submitForm() {
                    this.submitting = true;

                    try {
                        const url = this.folderId
                            ? `/email-folders/${this.folderId}`
                            : '/email-folders';

                        const method = this.folderId ? 'PUT' : 'POST';

                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ...this.formData,
                                parent_folder_id: this.formData.parent_folder_id || null
                            })
                        });

                        if (!response.ok) throw new Error('Error al guardar carpeta');

                        const data = await response.json();

                        // Recargar árbol de carpetas
                        window.location.reload();

                        this.closeModal();
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error al guardar la carpeta. Por favor, inténtalo de nuevo.');
                    } finally {
                        this.submitting = false;
                    }
                }
            }));
        });
    </script>

</body>

</html>