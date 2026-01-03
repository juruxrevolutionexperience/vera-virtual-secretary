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
      <div class="email-item density-${density} border-b border-gray-100 cursor-pointer relative ${isUnread ? 'unread' : ''}"
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

</body>

</html>