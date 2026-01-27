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
        [x-cloak] {
            display: none !important;
        }

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

        /* Ocultar imágenes y otros elementos HTML en el listado de emails */
        .email-item img,
        .email-item iframe,
        .email-item video,
        .email-item object,
        .email-item embed {
            display: none !important;
            max-height: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
        }

        /* CRÍTICO: Forzar que elementos flex respeten el ancho del contenedor */
        .email-item .flex-1 {
            min-width: 0 !important;
            max-width: 100% !important;
        }

        /* Asegurar que TODOS los elementos con truncate funcionen */
        .email-item .truncate {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        /* Prevenir que badges expandan el contenedor */
        .email-item [class*="inline-flex"] {
            flex-shrink: 0 !important;
        }

        /* Romper palabras y URLs largas */
        .email-item p,
        .email-item span {
            word-break: break-word !important;
            overflow-wrap: break-word !important;
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

        /* Animación para alertas de due dates */
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out forwards;
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

        /* Email item (click para abrir) */
        .email-item {
            position: relative;
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
        // Ya no se necesita el componente emailPreview porque los emails se abren en página completa
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
                            <div
                                class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9
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
                <!-- Email List (Full Width) -->
                <div class="w-full overflow-y-auto overflow-x-hidden bg-white">
                    @include('emails.partials.email-list')
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
        // ==========================================
        // NOTIFICACIONES DE DUE DATES AL CARGAR
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar stats del servidor (para evitar notificaciones duplicadas)
            const overdue = {{ $stats['overdue'] ?? 0 }};
            const dueToday = {{ $stats['due_today'] ?? 0 }};

            // Establecer estado inicial
            lastNotifiedStats = { overdue, due_today: dueToday };

            // Mostrar notificación inicial si hay emails vencidos o que vencen hoy
            if (overdue > 0 && dueToday > 0) {
                currentNotificationAlert = showDueDateAlert(
                    `🚨 Tienes ${overdue} email(s) vencido(s) y ${dueToday} que vencen hoy`,
                    'error',
                    'manual'
                );
            } else if (overdue > 0) {
                currentNotificationAlert = showDueDateAlert(
                    `🚨 Tienes ${overdue} email(s) vencido(s)`,
                    'error',
                    'manual'
                );
            } else if (dueToday > 0) {
                currentNotificationAlert = showDueDateAlert(
                    `⏰ Tienes ${dueToday} email(s) que vencen hoy`,
                    'warning',
                    false
                );
            }
        });

        // Función para mostrar alertas de due dates
        // persistent: false = auto-cierra en 8s, true = no se cierra, 'manual' = solo cierra con botón X
        function showDueDateAlert(message, type = 'warning', persistent = false) {
            const bgColor = type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-orange-500' : 'bg-blue-500';
            const borderColor = type === 'error' ? 'border-red-600' : type === 'warning' ? 'border-orange-600' : 'border-blue-600';

            // Determinar el enlace según el mensaje
            let filterLink = '/emails?filter=overdue';
            if (message.includes('vencido') && message.includes('vencen hoy')) {
                filterLink = '/emails?filter=overdue'; // Priorizar vencidos si hay ambos
            } else if (message.includes('vencen hoy')) {
                filterLink = '/emails?filter=due_today';
            }

            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 ${bgColor} ${borderColor} border-l-4 text-white px-6 py-4 rounded-lg shadow-2xl z-50 max-w-md animate-slide-in`;
            alert.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="flex-1">
                        <p class="font-semibold text-sm">${message}</p>
                        <div class="flex gap-2 mt-2">
                            ${message.includes('vencido') && message.includes('vencen hoy') ? `
                                <a href="/emails?filter=overdue"
                                   class="text-xs underline hover:no-underline">
                                    Ver vencidos →
                                </a>
                                <a href="/emails?filter=due_today"
                                   class="text-xs underline hover:no-underline">
                                    Ver hoy →
                                </a>
                            ` : `
                                <a href="${filterLink}"
                                   class="text-xs underline hover:no-underline">
                                    Ver emails →
                                </a>
                            `}
                        </div>
                    </div>
                    ${persistent !== true ? `
                        <button onclick="this.parentElement.parentElement.remove()"
                                class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    ` : ''}
                </div>
            `;

            document.body.appendChild(alert);

            // Auto-cerrar después de 8 segundos si no es persistente (ni true ni 'manual')
            if (persistent === false) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(100%)';
                    setTimeout(() => alert.remove(), 300);
                }, 8000);
            }

            // Retornar referencia al elemento para poder cerrarlo/actualizarlo después
            return alert;
        }

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

                // Actualizar estadísticas del sidebar (due dates, snooze)
                updateSidebarStats();

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
                <div class="relative mr-20" x-data="{ dropdownStyle: {} }" x-ref="workflowContainer">
                    <button @click.stop="workflowOpen = !workflowOpen; if(workflowOpen) { const rect = $el.getBoundingClientRect(); dropdownStyle = { top: rect.bottom + 4 + 'px', left: rect.left + 'px' }; }"
                        x-ref="workflowButton"
                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-full font-medium ${colorClass} hover:opacity-80 transition-opacity">
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
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity mr-20">
    <!-- Mover a carpeta -->                                                                                                                   
  <div class="relative" x-data="{ folderDropdownStyle: {} }" x-ref="folderContainer">                                                        
      <button                                                                                                                                
          @click.stop="folderMenuOpen = !folderMenuOpen; if(folderMenuOpen) { const rect = $el.getBoundingClientRect(); folderDropdownStyle =
   { top: rect.bottom + 4 + 'px', right: (window.innerWidth - rect.right) + 'px' }; }"                                                       
          x-ref="folderButton" class="p-1 hover:bg-gray-200 rounded transition-colors"                                                       
          title="Mover a carpeta">                                                                                                           
          <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"                                                           
              viewBox="0 0 24 24">                                                                                                           
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"                                                          
                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />                                           
          </svg>                                                                                                                             
      </button>                                                                                                                              
                                                                                                                                             
      <!-- Dropdown de carpetas usando TELEPORT al portal -->                                                                                
      <template x-teleport="#dropdowns-portal">                                                                                              
          <div x-show="folderMenuOpen" @click.away="folderMenuOpen = false" x-transition                                                     
              class="fixed w-64 bg-white rounded-lg shadow-2xl border border-gray-200 max-h-72 overflow-y-auto"                              
              :style="{ top: folderDropdownStyle.top, right: folderDropdownStyle.right, zIndex: 99999 }"                                     
              x-cloak>                                                                                                                       
              <div class="py-1" x-data="{                                                                                                    
                  folders: [],                                                                                                               
                  flatFolders: [],                                                                                                           
                                                                                                                                             
                  iconMap: {                                                                                                                 
                      'folder': '📁',                                                                                                        
                      'star': '⭐',                                                                                                          
                      'briefcase': '💼',                                                                                                     
                      'user': '👤',                                                                                                          
                      'archive': '🗄️',                                                                                                       
                      'inbox': '📥',                                                                                                         
                      'sent': '📤',                                                                                                          
                      'trash': '🗑️',                                                                                                         
                      'flag': '🚩',                                                                                                          
                      'check': '✅',                                                                                                         
                      'heart': '❤️',                                                                                                         
                      'bookmark': '🔖',                                                                                                      
                      'calendar': '📅',                                                                                                      
                      'document': '📄',                                                                                                      
                      'file': '📋',                                                                                                          
                      'chart': '📊',                                                                                                         
                      'gift': '🎁',                                                                                                          
                      'home': '🏠',                                                                                                          
                      'lock': '🔒',                                                                                                          
                      'bell': '🔔'                                                                                                           
                  },                                                                                                                         
                                                                                                                                             
                  getIcon(iconName) {                                                                                                        
                      if (iconName && iconName.length > 1 && /\\p{Emoji}/u.test(iconName)) {                                                 
                          return iconName;                                                                                                   
                      }                                                                                                                      
                      return this.iconMap[iconName] || '📁';                                                                                 
                  },                                                                                                                         
                                                                                                                                             
                  flattenFolders(folders, level = 0) {                                                                                       
                      let result = [];                                                                                                       
                      folders.forEach(folder => {                                                                                            
                          result.push({                                                                                                      
                              id: folder.id,                                                                                                 
                              name: folder.name,                                                                                             
                              icon: this.getIcon(folder.icon),                                                                               
                              color: folder.color || '#6b7280',                                                                              
                              level: level,                                                                                                  
                              indentStyle: level > 0 ? 'padding-left: ' + (level * 20) + 'px;' : '',                                         
                              showLine: level > 0                                                                                            
                          });                                                                                                                
                                                                                                                                             
                          if (folder.children && folder.children.length > 0) {                                                               
                              result = result.concat(this.flattenFolders(folder.children, level + 1));                                       
                          }                                                                                                                  
                      });                                                                                                                    
                      return result;                                                                                                         
                  }                                                                                                                          
              }" x-init="                                                                                                                    
                  fetch('/email-folders/tree')                                                                                               
                      .then(r => r.json())                                                                                                   
                      .then(data => {                                                                                                        
                          folders = data.folders || [];                                                                                      
                          flatFolders = flattenFolders(folders);                                                                             
                      })                                                                                                                     
              ">                                                                                                                             
                  <template x-for="folder in flatFolders" :key="folder.id">                                                                  
                      <button                                                                                                                
                          @click.stop="moveEmailToFolder(${email.id}, folder.id); folderMenuOpen = false"                                    
                          class="w-full text-left px-3 py-2 text-sm hover:bg-purple-50 flex items-center gap-2 transition-colors border-l-2  
  border-transparent hover:border-purple-400"                                                                                                
                          :style="folder.indentStyle">                                                                                       
                          <template x-if="folder.showLine">                                                                                  
                              <span class="text-gray-300 text-xs font-mono">└─</span>                                                        
                          </template>                                                                                                        
                          <span x-text="folder.icon" class="text-base flex-shrink-0"></span>                                                 
                          <span x-text="folder.name" class="truncate font-medium text-gray-700"></span>                                      
                      </button>                                                                                                              
                  </template>                                                                                                                
                  <template x-if="flatFolders.length === 0">                                                                                 
                      <p class="px-3 py-2 text-xs text-gray-400 text-center">No hay carpetas disponibles</p>                                 
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
                                  ${cleanExcerpt(email.excerpt)}
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

        // Función helper para limpiar y limitar excerpt
        function cleanExcerpt(excerpt) {
            if (!excerpt) return '';

            // Remover HTML tags
            const div = document.createElement('div');
            div.innerHTML = excerpt;
            let text = div.textContent || div.innerText || '';

            // Remover espacios múltiples y saltos de línea
            text = text.replace(/\s+/g, ' ').trim();

            // Limitar a 80 caracteres
            if (text.length > 80) {
                text = text.substring(0, 80) + '...';
            }

            return text;
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
                currentStatusFilter: '',
                allEmails: [], // Guardará todos los emails cargados     
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
                },

                // === FUNCIONES DE FILTRADO POR ESTADO ===                                                                                        
                filterByStatus(status) {
                    this.currentStatusFilter = status;

                    const allEmailItems = document.querySelectorAll('.email-item');

                    if (status === '') {
                        // Mostrar todos                                                                                                           
                        allEmailItems.forEach(item => {
                            item.style.display = '';
                        });
                    } else {
                        // Filtrar por estado                                                                                                      
                        allEmailItems.forEach(item => {
                            const emailId = item.getAttribute('data-email-id');
                            const emailData = this.getEmailDataById(emailId);

                            if (emailData && emailData.workflow_status === status) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }

                    console.log(`✅ Filtro aplicado: ${status || 'Todos'}`);
                },

                getEmailDataById(emailId) {
                    // Buscar en los emails cargados                                                                                               
                    const emailElement = document.querySelector(`#email-${emailId}`);
                    if (!emailElement) return null;

                    // Extraer workflow_status del badge                                                                                           
                    const badgeText = emailElement.querySelector('[class*="bg-"][class*="text-"]')?.textContent || '';

                    // Mapear texto del badge a workflow_status                                                                                    
                    const statusMap = {
                        '📬 Sin leer': 'unread',
                        '⏳ Pendiente': 'pending_validation',
                        '✉️ Responder': 'pending_response',
                        '🔄 En curso': 'in_progress',
                        '👁️ Revisión': 'in_review',
                        '✔️ Completado': 'completed',
                        '➖ Sin acción': 'no_action_needed',
                    };

                    return {
                        workflow_status: statusMap[badgeText.trim()] || 'unread'
                    };
                },

                getStatusCount(status) {
                    const allEmailItems = document.querySelectorAll('.email-item');
                    let count = 0;

                    allEmailItems.forEach(item => {
                        const emailId = item.getAttribute('data-email-id');
                        const emailData = this.getEmailDataById(emailId);

                        if (emailData && emailData.workflow_status === status) {
                            count++;
                        }
                    });

                    return count;
                },

                getTotalEmails() {
                    return document.querySelectorAll('.email-item').length;
                },
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
        }

        // Las funciones de preview ya no son necesarias - los emails se abren directamente en página completa

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
        document.addEventListener('DOMContentLoaded', function () {
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
                expandedFolders: {},
                contextMenuOpen: false,
                contextMenuFolderId: null,
                contextMenuPosition: { x: 0, y: 0 },

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

                toggleFolder(folderId) {
                    this.expandedFolders[folderId] = !this.expandedFolders[folderId];
                },

                isFolderExpanded(folderId) {
                    return this.expandedFolders[folderId] || false;
                },

                renderFolderRecursive(folder, depth) {
                    const hasChildren = folder.children && folder.children.length > 0;
                    const isExpanded = this.isFolderExpanded(folder.id);
                    const isSelected = this.selectedFolderId === folder.id;
                    const indentClass = depth > 0 ? `ml-${depth * 4}` : '';

                    let html = `
                        <div class="${indentClass}">
                            <div @click="selectFolder(${folder.id})"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-md mb-1 cursor-pointer group relative ${isSelected ? 'bg-purple-50 text-purple-900' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'}">
                    `;

                    // Chevron si tiene hijos
                    if (hasChildren) {
                        html += `
                            <button @click.stop="toggleFolder(${folder.id})" class="flex-shrink-0">
                                <svg class="w-3 h-3 transition-transform ${isExpanded ? 'rotate-90' : ''}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        `;
                    } else {
                        html += `<span class="w-3"></span>`;
                    }

                    // Ícono y nombre
                    html += `
                        <span style="color: ${folder.color || '#6B7280'}" class="text-sm flex-shrink-0">${this.getFolderIconEmoji(folder.icon)}</span>
                        <span class="flex-1 truncate text-sm">${folder.name}</span>
                        <span class="text-xs text-gray-400 font-medium">${folder.emails_count || 0}</span>
                    `;

                    // Botón menú para carpetas no del sistema
                    if (!folder.is_system_folder) {
                        html += `
                            <button @click.stop="openFolderMenu(${folder.id}, $event)" class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                </svg>
                            </button>
                        `;
                    }

                    html += `</div>`;

                    // Renderizar hijos recursivamente
                    if (hasChildren && isExpanded) {
                        for (const child of folder.children) {
                            html += this.renderFolderRecursive(child, depth + 1);
                        }
                    }

                    html += `</div>`;

                    return html;
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

                getFolderIconEmoji(icon) {
                    const emojis = {
                        folder: '📁',
                        star: '⭐',
                        briefcase: '💼',
                        user: '👤',
                        archive: '📦',
                        heart: '❤️',
                        flag: '🚩',
                        check: '✅',
                        clock: '⏰',
                        document: '📄'
                    };
                    return emojis[icon] || '📁';
                },

                openCreateFolderModal() {
                    window.dispatchEvent(new CustomEvent('open-folder-modal'));
                },

                openFolderMenu(folderId, event) {
                    event.stopPropagation();

                    // Si ya está abierto para esta carpeta, cerrarlo
                    if (this.contextMenuFolderId === folderId && this.contextMenuOpen) {
                        this.closeContextMenu();
                        return;
                    }

                    // Abrir menú para esta carpeta
                    this.contextMenuFolderId = folderId;
                    this.contextMenuOpen = true;

                    // Posicionar el menú cerca del botón
                    const rect = event.target.closest('button').getBoundingClientRect();
                    this.contextMenuPosition = {
                        x: rect.right - 150,
                        y: rect.bottom + 5
                    };
                },

                closeContextMenu() {
                    this.contextMenuOpen = false;
                    this.contextMenuFolderId = null;
                },

                async editFolder(folderId) {
                    this.closeContextMenu();

                    try {
                        const response = await fetch(`/email-folders/${folderId}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error('Error al cargar carpeta');

                        const data = await response.json();

                        // Disparar evento para abrir el modal en modo edición
                        window.dispatchEvent(new CustomEvent('edit-folder-modal', {
                            detail: data.folder
                        }));
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error al cargar los datos de la carpeta');
                    }
                },

                async deleteFolder(folderId) {
                    this.closeContextMenu();

                    const folder = this.findFolderById(folderId, this.folders);
                    if (!folder) return;

                    const confirmed = confirm(`¿Eliminar la carpeta "${folder.name}"?\n\nLos emails NO se eliminarán, solo se quitarán de esta carpeta.`);
                    if (!confirmed) return;

                    try {
                        const response = await fetch(`/email-folders/${folderId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                reassign_emails: false
                            })
                        });

                        if (!response.ok) throw new Error('Error al eliminar carpeta');

                        const data = await response.json();
                        console.log('✅ Carpeta eliminada:', data);

                        // Recargar árbol de carpetas
                        await this.loadFolders();

                        // Mensaje de éxito
                        alert('Carpeta eliminada correctamente');
                    } catch (error) {
                        console.error('❌ Error al eliminar carpeta:', error);
                        alert('Error al eliminar la carpeta. Por favor, inténtalo de nuevo.');
                    }
                },

                findFolderById(folderId, folders) {
                    for (const folder of folders) {
                        if (folder.id === folderId) return folder;
                        if (folder.children && folder.children.length > 0) {
                            const found = this.findFolderById(folderId, folder.children);
                            if (found) return found;
                        }
                    }
                    return null;
                }
            }));
        });

        // ==========================================
        // FUNCIÓN: CAMBIAR WORKFLOW STATUS
        // ==========================================
        async function changeWorkflowStatus(emailId, newStatus) {
            try {
                const response = await fetch(`/emails/${emailId}/workflow-status`, {
                    method: 'PATCH',
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
        // FUNCIÓN: ACTUALIZAR OPCIONES DE DROPDOWNS DINÁMICAMENTE
        // ==========================================
        function updateDropdownOptions(emailId, dueDateData) {
            console.log(`🔄 Actualizando opciones de dropdown para email ${emailId}`, dueDateData);

            // Buscar dropdowns globalmente (están teleportados fuera del email row)
            const dueDateDropdowns = document.querySelectorAll(`.due-date-dropdown[data-email-id="${emailId}"]`);
            console.log(`📍 Due date dropdowns encontrados:`, dueDateDropdowns.length);

            dueDateDropdowns.forEach((dueDateDropdown, index) => {
                console.log(`📍 Procesando due date dropdown ${index + 1}/${dueDateDropdowns.length}`);
                // Buscar si existe el botón "Quitar fecha límite"
                let removeButton = dueDateDropdown.querySelector('.remove-due-date-btn');
                console.log(`📍 Botón "Quitar fecha" existente:`, !!removeButton);
                console.log(`📍 Due date actual:`, dueDateData.dueDate);

                if (dueDateData.dueDate && !removeButton) {
                    // Hay due date pero no existe el botón - añadirlo
                    console.log(`➕ Intentando añadir botón "Quitar fecha límite"...`);
                    const separator = dueDateDropdown.querySelector('.due-date-separator');
                    console.log(`📍 Separador encontrado:`, !!separator);
                    if (separator && separator.parentNode) {
                        removeButton = document.createElement('div');
                        removeButton.className = 'remove-due-date-btn';
                        removeButton.innerHTML = `
                            <div class="border-t border-gray-200 my-1"></div>
                            <button onclick="removeDueDate(${emailId})"
                                class="w-full text-left px-3 py-2 text-xs hover:bg-red-50 flex items-center gap-2 text-red-600 transition-colors">
                                <span>❌</span>
                                <span class="font-medium">Quitar fecha límite</span>
                            </button>
                        `;
                        // Insertar después del datetime input (que viene después del separator)
                        const customDateContainer = separator.nextElementSibling;
                        if (customDateContainer && customDateContainer.nextSibling) {
                            customDateContainer.parentNode.insertBefore(removeButton, customDateContainer.nextSibling);
                        } else {
                            separator.parentNode.appendChild(removeButton);
                        }
                        console.log('✅ Añadido botón "Quitar fecha límite"');
                    }
                } else if (!dueDateData.dueDate && removeButton) {
                    // No hay due date pero existe el botón - quitarlo
                    removeButton.remove();
                    console.log('✅ Eliminado botón "Quitar fecha límite"');
                }
            });

            // Actualizar dropdown de Snooze (buscar globalmente, están teleportados)
            const snoozeDropdowns = document.querySelectorAll(`.snooze-dropdown[data-email-id="${emailId}"]`);
            console.log(`📍 Snooze dropdowns encontrados:`, snoozeDropdowns.length);

            snoozeDropdowns.forEach((snoozeDropdown, index) => {
                console.log(`📍 Procesando snooze dropdown ${index + 1}/${snoozeDropdowns.length}`);
                // Buscar si existe el botón "Despertar ahora"
                let wakeupButton = snoozeDropdown.querySelector('.wakeup-btn');
                console.log(`📍 Botón "Despertar ahora" existente:`, !!wakeupButton);
                console.log(`📍 Email snoozed:`, dueDateData.isSnoozed);

                if (dueDateData.isSnoozed && !wakeupButton) {
                    // Está snoozed pero no existe el botón - añadirlo
                    console.log(`➕ Intentando añadir botón "Despertar ahora"...`);
                    const separator = snoozeDropdown.querySelector('.snooze-separator');
                    console.log(`📍 Separador encontrado:`, !!separator);
                    if (separator && separator.parentNode) {
                        wakeupButton = document.createElement('div');
                        wakeupButton.className = 'wakeup-btn';
                        wakeupButton.innerHTML = `
                            <div class="border-t border-gray-200 my-1"></div>
                            <button onclick="wakeupEmail(${emailId})"
                                class="w-full text-left px-3 py-2 text-xs hover:bg-red-50 flex items-center gap-2 text-red-600 transition-colors">
                                <span>❌</span>
                                <span class="font-medium">Despertar ahora</span>
                            </button>
                        `;
                        // Insertar después del datetime input
                        const customDateContainer = separator.nextElementSibling;
                        if (customDateContainer && customDateContainer.nextSibling) {
                            customDateContainer.parentNode.insertBefore(wakeupButton, customDateContainer.nextSibling);
                        } else {
                            separator.parentNode.appendChild(wakeupButton);
                        }
                        console.log('✅ Añadido botón "Despertar ahora"');
                    }
                } else if (!dueDateData.isSnoozed && wakeupButton) {
                    // No está snoozed pero existe el botón - quitarlo
                    wakeupButton.remove();
                    console.log('✅ Eliminado botón "Despertar ahora"');
                }
            });
        }

        // ==========================================
        // FUNCIÓN: VERIFICAR Y MOSTRAR NOTIFICACIONES DE DUE DATES
        // ==========================================
        let lastNotifiedStats = { overdue: 0, due_today: 0 }; // Guardar último estado notificado
        let currentNotificationAlert = null; // Referencia a la alerta actual

        function checkAndShowDueDateNotifications(stats) {
            const overdue = stats.overdue || 0;
            const dueToday = stats.due_today || 0;

            // Verificar si las estadísticas han cambiado desde la última notificación
            const hasChanged =
                overdue !== lastNotifiedStats.overdue ||
                dueToday !== lastNotifiedStats.due_today;

            // Solo mostrar notificación si hay algo QUE NOTIFICAR y ha cambiado
            if (!hasChanged || (overdue === 0 && dueToday === 0)) {
                return;
            }

            // Cerrar notificación anterior si existe
            if (currentNotificationAlert) {
                currentNotificationAlert.remove();
                currentNotificationAlert = null;
            }

            // Actualizar último estado
            lastNotifiedStats = { overdue, due_today: dueToday };

            // Mostrar nueva notificación
            if (overdue > 0 && dueToday > 0) {
                currentNotificationAlert = showDueDateAlert(
                    `🚨 Tienes ${overdue} email(s) vencido(s) y ${dueToday} que vencen hoy`,
                    'error',
                    'manual'
                );
            } else if (overdue > 0) {
                currentNotificationAlert = showDueDateAlert(
                    `🚨 Tienes ${overdue} email(s) vencido(s)`,
                    'error',
                    'manual'
                );
            } else if (dueToday > 0) {
                currentNotificationAlert = showDueDateAlert(
                    `⏰ Tienes ${dueToday} email(s) que vencen hoy`,
                    'warning',
                    false
                );
            }
        }

        // ==========================================
        // FUNCIÓN: ACTUALIZAR ESTADÍSTICAS DEL SIDEBAR SIN RELOAD
        // ==========================================
        async function updateSidebarStats() {
            try {
                console.log('🔄 Actualizando estadísticas del sidebar...');

                const response = await fetch('/emails/api/stats', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.error('❌ Error obteniendo stats:', response.status);
                    return;
                }

                const data = await response.json();
                console.log('📊 Stats recibidas:', data.stats);

                // Actualizar contadores en sidebar
                const overdueEl = document.querySelector('[data-filter="overdue"] .badge-count');
                const dueTodayEl = document.querySelector('[data-filter="due_today"] .badge-count');
                const dueWeekEl = document.querySelector('[data-filter="due_this_week"] .badge-count');
                const snoozedEl = document.querySelector('[data-filter="snoozed"] .badge-count');

                // Actualizar "Vencidos"
                if (overdueEl) {
                    if (data.stats.overdue > 0) {
                        overdueEl.textContent = data.stats.overdue;
                        overdueEl.classList.remove('hidden');
                        console.log(`✅ Vencidos: ${data.stats.overdue}`);
                    } else {
                        overdueEl.classList.add('hidden');
                    }
                }

                // Actualizar "Vencen hoy"
                if (dueTodayEl) {
                    if (data.stats.due_today > 0) {
                        dueTodayEl.textContent = data.stats.due_today;
                        dueTodayEl.classList.remove('hidden');
                        console.log(`✅ Vencen hoy: ${data.stats.due_today}`);
                    } else {
                        dueTodayEl.classList.add('hidden');
                    }
                }

                // Actualizar "Vencen esta semana"
                if (dueWeekEl) {
                    if (data.stats.due_this_week > 0) {
                        dueWeekEl.textContent = data.stats.due_this_week;
                        dueWeekEl.classList.remove('hidden');
                        console.log(`✅ Vencen esta semana: ${data.stats.due_this_week}`);
                    } else {
                        dueWeekEl.classList.add('hidden');
                    }
                }

                // Actualizar "Pospuestos"
                if (snoozedEl) {
                    if (data.stats.snoozed > 0) {
                        snoozedEl.textContent = data.stats.snoozed;
                        snoozedEl.classList.remove('hidden');
                        console.log(`✅ Pospuestos: ${data.stats.snoozed}`);
                    } else {
                        snoozedEl.classList.add('hidden');
                    }
                }

                console.log('✅ Sidebar actualizado correctamente');

                // Verificar si hay que mostrar notificaciones de due dates
                checkAndShowDueDateNotifications(data.stats);

            } catch (error) {
                console.error('❌ Error actualizando stats del sidebar:', error);
            }
        }

        // ==========================================
        // FUNCIÓN: ACTUALIZAR BADGES DINÁMICAMENTE SIN RELOAD
        // ==========================================
        function updateEmailBadges(emailId, dueDateData) {
            console.log(`🔄 Iniciando actualización de badges para email ${emailId}`, dueDateData);

            // Encontrar el email en el DOM
            const emailRow = document.querySelector(`[data-email-id="${emailId}"]`);
            if (!emailRow) {
                console.warn(`❌ No se encontró el email con ID ${emailId} en el DOM`);
                return;
            }
            console.log(`✅ Email encontrado en DOM:`, emailRow);

            // Encontrar el contenedor de badges
            const badgesContainer = emailRow.querySelector('.email-badges');
            if (!badgesContainer) {
                console.warn(`❌ No se encontró el contenedor .email-badges para el email ${emailId}`);
                console.log(`🔍 HTML del email:`, emailRow.innerHTML.substring(0, 500));
                return;
            }
            console.log(`✅ Contenedor de badges encontrado:`, badgesContainer);

            // Reconstruir badges HTML según los datos recibidos
            let badgesHtml = '';

            // Badge de Due Date
            if (dueDateData.isOverdue) {
                badgesHtml += `<span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-red-100 text-red-700 animate-pulse" title="Venció: ${dueDateData.dueDateFull || ''}">
                    🚨 Vencido: ${dueDateData.dueDateShort || ''}
                </span>`;
                console.log('📍 Badge añadido: VENCIDO');
            } else if (dueDateData.isDueSoon) {
                badgesHtml += `<span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-yellow-100 text-yellow-700" title="Vence: ${dueDateData.dueDateFull || ''}">
                    ⏰ Vence: ${dueDateData.dueDateShort || ''}
                </span>`;
                console.log('📍 Badge añadido: VENCE PRONTO');
            } else if (dueDateData.dueDate) {
                badgesHtml += `<span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700" title="Vence: ${dueDateData.dueDateFull || ''}">
                    📅 ${dueDateData.dueDateShort || ''}
                </span>`;
                console.log('📍 Badge añadido: DUE DATE normal');
            }

            // Badge de Snooze
            if (dueDateData.isSnoozed) {
                badgesHtml += `<span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-blue-100 text-blue-700" title="Pospuesto hasta: ${dueDateData.snoozedUntilFull || ''}">
                    💤 Hasta: ${dueDateData.snoozedUntilShort || ''}
                </span>`;
                console.log('📍 Badge añadido: SNOOZED');
            }

            console.log(`📝 HTML generado para badges:`, badgesHtml);

            // Actualizar el DOM
            badgesContainer.innerHTML = badgesHtml;
            console.log(`✅ Badges actualizados correctamente para email ${emailId}`);
        }

        // ==========================================
        // FUNCIÓN: ESTABLECER DUE DATE (OPCIONES RÁPIDAS)
        // ==========================================
        async function setDueDate(emailId, preset) {
            try {
                // Calcular la fecha según el preset
                const now = new Date();
                let dueDate;

                switch(preset) {
                    case 'today':
                        dueDate = new Date(now.setHours(23, 59, 59, 999));
                        break;
                    case 'tomorrow':
                        dueDate = new Date(now.setDate(now.getDate() + 1));
                        dueDate.setHours(23, 59, 59, 999);
                        break;
                    case '3days':
                        dueDate = new Date(now.setDate(now.getDate() + 3));
                        dueDate.setHours(23, 59, 59, 999);
                        break;
                    case 'nextweek':
                        dueDate = new Date(now.setDate(now.getDate() + 7));
                        dueDate.setHours(23, 59, 59, 999);
                        break;
                    default:
                        throw new Error('Preset inválido');
                }

                // Formatear fecha para la API (ISO 8601)
                const dueDateISO = dueDate.toISOString();

                const response = await fetch(`/email-workflow/${emailId}/set-due-date`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ due_date: dueDateISO })
                });

                if (!response.ok) throw new Error('Error al establecer fecha límite');

                const data = await response.json();
                console.log('✅ Fecha límite establecida:', data);

                showNotification('Fecha límite establecida correctamente', 'success');

                // Actualizar badges sin reload - obtener datos actualizados del backend
                const emailResponse = await fetch(`/emails/api/${emailId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (emailResponse.ok) {
                    const emailData = await emailResponse.json();
                    console.log('📧 Datos del email recibidos:', emailData.email);
                    const dueDateInfo = {
                        isOverdue: emailData.email.isOverdue,
                        isDueSoon: emailData.email.isDueSoon,
                        dueDate: emailData.email.due_date,
                        dueDateShort: emailData.email.due_date_short,
                        dueDateFull: emailData.email.due_date_full,
                        isSnoozed: emailData.email.isSnoozed,
                        snoozedUntilShort: emailData.email.snoozed_until_short,
                        snoozedUntilFull: emailData.email.snoozed_until_full,
                    };
                    updateEmailBadges(emailId, dueDateInfo);
                    // Actualizar opciones de los dropdowns
                    updateDropdownOptions(emailId, dueDateInfo);
                    // Actualizar estadísticas del sidebar
                    updateSidebarStats();
                } else {
                    console.error('❌ Error obteniendo datos del email:', emailResponse.status);
                }

            } catch (error) {
                console.error('❌ Error al establecer fecha límite:', error);
                alert('Error al establecer la fecha límite. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: ESTABLECER DUE DATE PERSONALIZADA
        // ==========================================
        async function setDueDateCustom(emailId, dateString) {
            try {
                if (!dateString) return;

                // Convertir a fecha con hora 23:59:59
                const dueDate = new Date(dateString);
                dueDate.setHours(23, 59, 59, 999);
                const dueDateISO = dueDate.toISOString();

                const response = await fetch(`/email-workflow/${emailId}/set-due-date`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ due_date: dueDateISO })
                });

                if (!response.ok) throw new Error('Error al establecer fecha límite');

                const data = await response.json();
                console.log('✅ Fecha límite personalizada establecida:', data);

                showNotification('Fecha límite establecida correctamente', 'success');

                // Actualizar badges sin reload - obtener datos actualizados del backend
                const emailResponse = await fetch(`/emails/api/${emailId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (emailResponse.ok) {
                    const emailData = await emailResponse.json();
                    console.log('📧 Datos del email recibidos:', emailData.email);
                    const dueDateInfo = {
                        isOverdue: emailData.email.isOverdue,
                        isDueSoon: emailData.email.isDueSoon,
                        dueDate: emailData.email.due_date,
                        dueDateShort: emailData.email.due_date_short,
                        dueDateFull: emailData.email.due_date_full,
                        isSnoozed: emailData.email.isSnoozed,
                        snoozedUntilShort: emailData.email.snoozed_until_short,
                        snoozedUntilFull: emailData.email.snoozed_until_full,
                    };
                    updateEmailBadges(emailId, dueDateInfo);
                    // Actualizar opciones de los dropdowns
                    updateDropdownOptions(emailId, dueDateInfo);
                    // Actualizar estadísticas del sidebar
                    updateSidebarStats();
                } else {
                    console.error('❌ Error obteniendo datos del email:', emailResponse.status);
                }

            } catch (error) {
                console.error('❌ Error al establecer fecha límite personalizada:', error);
                alert('Error al establecer la fecha límite. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: QUITAR DUE DATE
        // ==========================================
        async function removeDueDate(emailId) {
            try {
                // Usar una fecha muy lejana en el pasado o null
                const response = await fetch(`/email-workflow/${emailId}/set-due-date`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ due_date: null })
                });

                if (!response.ok) throw new Error('Error al quitar fecha límite');

                const data = await response.json();
                console.log('✅ Fecha límite eliminada:', data);

                showNotification('Fecha límite eliminada', 'success');

                // Actualizar badges sin reload - obtener datos actualizados del backend
                const emailResponse = await fetch(`/emails/api/${emailId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (emailResponse.ok) {
                    const emailData = await emailResponse.json();
                    console.log('📧 Datos del email recibidos:', emailData.email);
                    const dueDateInfo = {
                        isOverdue: emailData.email.isOverdue,
                        isDueSoon: emailData.email.isDueSoon,
                        dueDate: emailData.email.due_date,
                        dueDateShort: emailData.email.due_date_short,
                        dueDateFull: emailData.email.due_date_full,
                        isSnoozed: emailData.email.isSnoozed,
                        snoozedUntilShort: emailData.email.snoozed_until_short,
                        snoozedUntilFull: emailData.email.snoozed_until_full,
                    };
                    updateEmailBadges(emailId, dueDateInfo);
                    // Actualizar opciones de los dropdowns
                    updateDropdownOptions(emailId, dueDateInfo);
                    // Actualizar estadísticas del sidebar
                    updateSidebarStats();

                    // Cerrar el dropdown con un pequeño delay
                    setTimeout(() => {
                        const dropdown = document.querySelector(`.due-date-dropdown[data-email-id="${emailId}"]`);
                        if (dropdown) {
                            // Simular click fuera para cerrar el dropdown
                            document.body.click();
                        }
                    }, 100);
                } else {
                    console.error('❌ Error obteniendo datos del email:', emailResponse.status);
                }

            } catch (error) {
                console.error('❌ Error al quitar fecha límite:', error);
                alert('Error al quitar la fecha límite. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: SNOOZE EMAIL (OPCIONES RÁPIDAS)
        // ==========================================
        async function snoozeEmail(emailId, preset) {
            try {
                // Calcular la fecha según el preset
                const now = new Date();
                let snoozeDate;

                switch(preset) {
                    case '1hour':
                        snoozeDate = new Date(now.getTime() + 1 * 60 * 60 * 1000);
                        break;
                    case '3hours':
                        snoozeDate = new Date(now.getTime() + 3 * 60 * 60 * 1000);
                        break;
                    case 'tomorrow9am':
                        snoozeDate = new Date(now);
                        snoozeDate.setDate(snoozeDate.getDate() + 1);
                        snoozeDate.setHours(9, 0, 0, 0);
                        break;
                    case '3days':
                        snoozeDate = new Date(now);
                        snoozeDate.setDate(snoozeDate.getDate() + 3);
                        snoozeDate.setHours(9, 0, 0, 0);
                        break;
                    case '1week':
                        snoozeDate = new Date(now);
                        snoozeDate.setDate(snoozeDate.getDate() + 7);
                        snoozeDate.setHours(9, 0, 0, 0);
                        break;
                    default:
                        throw new Error('Preset inválido');
                }

                // Formatear fecha para la API (ISO 8601)
                const snoozeDateISO = snoozeDate.toISOString();

                const response = await fetch(`/email-workflow/${emailId}/snooze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ snoozed_until: snoozeDateISO })
                });

                if (!response.ok) throw new Error('Error al posponer email');

                const data = await response.json();
                console.log('✅ Email pospuesto:', data);

                showNotification('Email pospuesto correctamente', 'success');

                // Actualizar badges sin reload - obtener datos actualizados del backend
                const emailResponse = await fetch(`/emails/api/${emailId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (emailResponse.ok) {
                    const emailData = await emailResponse.json();
                    console.log('📧 Datos del email recibidos:', emailData.email);
                    const dueDateInfo = {
                        isOverdue: emailData.email.isOverdue,
                        isDueSoon: emailData.email.isDueSoon,
                        dueDate: emailData.email.due_date,
                        dueDateShort: emailData.email.due_date_short,
                        dueDateFull: emailData.email.due_date_full,
                        isSnoozed: emailData.email.isSnoozed,
                        snoozedUntilShort: emailData.email.snoozed_until_short,
                        snoozedUntilFull: emailData.email.snoozed_until_full,
                    };
                    updateEmailBadges(emailId, dueDateInfo);
                    // Actualizar opciones de los dropdowns
                    updateDropdownOptions(emailId, dueDateInfo);
                    // Actualizar estadísticas del sidebar
                    updateSidebarStats();
                } else {
                    console.error('❌ Error obteniendo datos del email:', emailResponse.status);
                }

            } catch (error) {
                console.error('❌ Error al posponer email:', error);
                alert('Error al posponer el email. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: SNOOZE EMAIL PERSONALIZADO
        // ==========================================
        async function snoozeEmailCustom(emailId, dateTimeString) {
            try {
                if (!dateTimeString) return;

                // Convertir a ISO 8601
                const snoozeDate = new Date(dateTimeString);
                const snoozeDateISO = snoozeDate.toISOString();

                const response = await fetch(`/email-workflow/${emailId}/snooze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ snoozed_until: snoozeDateISO })
                });

                if (!response.ok) throw new Error('Error al posponer email');

                const data = await response.json();
                console.log('✅ Email pospuesto (personalizado):', data);

                showNotification('Email pospuesto correctamente', 'success');

                // Actualizar badges sin reload - obtener datos actualizados del backend
                const emailResponse = await fetch(`/emails/api/${emailId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (emailResponse.ok) {
                    const emailData = await emailResponse.json();
                    console.log('📧 Datos del email recibidos:', emailData.email);
                    const dueDateInfo = {
                        isOverdue: emailData.email.isOverdue,
                        isDueSoon: emailData.email.isDueSoon,
                        dueDate: emailData.email.due_date,
                        dueDateShort: emailData.email.due_date_short,
                        dueDateFull: emailData.email.due_date_full,
                        isSnoozed: emailData.email.isSnoozed,
                        snoozedUntilShort: emailData.email.snoozed_until_short,
                        snoozedUntilFull: emailData.email.snoozed_until_full,
                    };
                    updateEmailBadges(emailId, dueDateInfo);
                    // Actualizar opciones de los dropdowns
                    updateDropdownOptions(emailId, dueDateInfo);
                    // Actualizar estadísticas del sidebar
                    updateSidebarStats();
                } else {
                    console.error('❌ Error obteniendo datos del email:', emailResponse.status);
                }

            } catch (error) {
                console.error('❌ Error al posponer email:', error);
                alert('Error al posponer el email. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: DESPERTAR EMAIL (QUITAR SNOOZE)
        // ==========================================
        async function wakeupEmail(emailId) {
            try {
                const response = await fetch(`/email-workflow/${emailId}/wakeup`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error al despertar email');

                const data = await response.json();
                console.log('✅ Email despertado:', data);

                showNotification('Email despertado correctamente', 'success');

                // Actualizar badges sin reload - obtener datos actualizados del backend
                const emailResponse = await fetch(`/emails/api/${emailId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (emailResponse.ok) {
                    const emailData = await emailResponse.json();
                    console.log('📧 Datos del email recibidos:', emailData.email);
                    const dueDateInfo = {
                        isOverdue: emailData.email.isOverdue,
                        isDueSoon: emailData.email.isDueSoon,
                        dueDate: emailData.email.due_date,
                        dueDateShort: emailData.email.due_date_short,
                        dueDateFull: emailData.email.due_date_full,
                        isSnoozed: emailData.email.isSnoozed,
                        snoozedUntilShort: emailData.email.snoozed_until_short,
                        snoozedUntilFull: emailData.email.snoozed_until_full,
                    };
                    updateEmailBadges(emailId, dueDateInfo);
                    // Actualizar opciones de los dropdowns
                    updateDropdownOptions(emailId, dueDateInfo);
                    // Actualizar estadísticas del sidebar
                    updateSidebarStats();

                    // Cerrar el dropdown con un pequeño delay
                    setTimeout(() => {
                        const dropdown = document.querySelector(`.snooze-dropdown[data-email-id="${emailId}"]`);
                        if (dropdown) {
                            // Simular click fuera para cerrar el dropdown
                            document.body.click();
                        }
                    }, 100);
                } else {
                    console.error('❌ Error obteniendo datos del email:', emailResponse.status);
                }

            } catch (error) {
                console.error('❌ Error al despertar email:', error);
                alert('Error al despertar el email. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: BULK SET DUE DATE
        // ==========================================
        async function bulkSetDueDate(preset) {
            const selectedIds = Alpine.store ? Alpine.store('emails')?.selectedEmails : [];
            if (!selectedIds || selectedIds.length === 0) {
                alert('No hay emails seleccionados');
                return;
            }

            try {
                // Calcular fecha según preset (igual que setDueDate individual)
                const now = new Date();
                let dueDate;

                switch(preset) {
                    case 'today':
                        dueDate = new Date(now.setHours(23, 59, 59, 999));
                        break;
                    case 'tomorrow':
                        dueDate = new Date(now.setDate(now.getDate() + 1));
                        dueDate.setHours(23, 59, 59, 999);
                        break;
                    case '3days':
                        dueDate = new Date(now.setDate(now.getDate() + 3));
                        dueDate.setHours(23, 59, 59, 999);
                        break;
                    case 'nextweek':
                        dueDate = new Date(now.setDate(now.getDate() + 7));
                        dueDate.setHours(23, 59, 59, 999);
                        break;
                    default:
                        throw new Error('Preset inválido');
                }

                const dueDateISO = dueDate.toISOString();

                // Establecer due date para cada email seleccionado
                const promises = selectedIds.map(emailId =>
                    fetch(`/email-workflow/${emailId}/set-due-date`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ due_date: dueDateISO })
                    })
                );

                await Promise.all(promises);

                showNotification(`Fecha límite establecida en ${selectedIds.length} email(s)`, 'success');

                // Actualizar badges de cada email sin reload
                for (const emailId of selectedIds) {
                    try {
                        const emailResponse = await fetch(`/emails/api/${emailId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (emailResponse.ok) {
                            const emailData = await emailResponse.json();
                            const dueDateInfo = {
                                isOverdue: emailData.email.isOverdue,
                                isDueSoon: emailData.email.isDueSoon,
                                dueDate: emailData.email.due_date,
                                dueDateShort: emailData.email.due_date_short,
                                dueDateFull: emailData.email.due_date_full,
                                isSnoozed: emailData.email.isSnoozed,
                                snoozedUntilShort: emailData.email.snoozed_until_short,
                                snoozedUntilFull: emailData.email.snoozed_until_full,
                            };
                            updateEmailBadges(emailId, dueDateInfo);
                            updateDropdownOptions(emailId, dueDateInfo);
                        }
                    } catch (err) {
                        console.error(`Error actualizando badge del email ${emailId}:`, err);
                    }
                }

                // Actualizar estadísticas del sidebar después de procesar todos
                updateSidebarStats();

            } catch (error) {
                console.error('❌ Error en bulk set due date:', error);
                alert('Error al establecer fecha límite. Por favor, inténtalo de nuevo.');
            }
        }

        // ==========================================
        // FUNCIÓN: BULK SNOOZE
        // ==========================================
        async function bulkSnooze(preset) {
            const selectedIds = Alpine.store ? Alpine.store('emails')?.selectedEmails : [];
            if (!selectedIds || selectedIds.length === 0) {
                alert('No hay emails seleccionados');
                return;
            }

            try {
                // Calcular fecha según preset (igual que snoozeEmail individual)
                const now = new Date();
                let snoozeDate;

                switch(preset) {
                    case '1hour':
                        snoozeDate = new Date(now.getTime() + 1 * 60 * 60 * 1000);
                        break;
                    case '3hours':
                        snoozeDate = new Date(now.getTime() + 3 * 60 * 60 * 1000);
                        break;
                    case 'tomorrow9am':
                        snoozeDate = new Date(now);
                        snoozeDate.setDate(snoozeDate.getDate() + 1);
                        snoozeDate.setHours(9, 0, 0, 0);
                        break;
                    default:
                        throw new Error('Preset inválido');
                }

                const snoozeDateISO = snoozeDate.toISOString();

                // Posponer cada email seleccionado
                const promises = selectedIds.map(emailId =>
                    fetch(`/email-workflow/${emailId}/snooze`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ snoozed_until: snoozeDateISO })
                    })
                );

                await Promise.all(promises);

                showNotification(`${selectedIds.length} email(s) pospuesto(s)`, 'success');

                // Actualizar badges de cada email sin reload
                for (const emailId of selectedIds) {
                    try {
                        const emailResponse = await fetch(`/emails/api/${emailId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (emailResponse.ok) {
                            const emailData = await emailResponse.json();
                            const dueDateInfo = {
                                isOverdue: emailData.email.isOverdue,
                                isDueSoon: emailData.email.isDueSoon,
                                dueDate: emailData.email.due_date,
                                dueDateShort: emailData.email.due_date_short,
                                dueDateFull: emailData.email.due_date_full,
                                isSnoozed: emailData.email.isSnoozed,
                                snoozedUntilShort: emailData.email.snoozed_until_short,
                                snoozedUntilFull: emailData.email.snoozed_until_full,
                            };
                            updateEmailBadges(emailId, dueDateInfo);
                            updateDropdownOptions(emailId, dueDateInfo);
                        }
                    } catch (err) {
                        console.error(`Error actualizando badge del email ${emailId}:`, err);
                    }
                }

                // Actualizar estadísticas del sidebar después de procesar todos
                updateSidebarStats();

            } catch (error) {
                console.error('❌ Error en bulk snooze:', error);
                alert('Error al posponer emails. Por favor, inténtalo de nuevo.');
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

        // Atajos de teclado deshabilitados - los emails se abren en página completa
    </script>

    <!-- Modal Crear/Editar Carpeta -->
    <div x-data="folderModal()" @open-folder-modal.window="openModal()"
        @edit-folder-modal.window="openModal($event.detail.id)" x-show="isOpen" x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">

        <div @click.away="closeModal()" x-show="isOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-white rounded-lg shadow-xl w-full max-w-md">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" x-text="folderId ? 'Editar Carpeta' : 'Nueva Carpeta'">
                </h3>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitForm()" class="px-6 py-4 space-y-4">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre *
                    </label>
                    <input type="text" x-model="formData.name" required
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
                        <input type="color" x-model="formData.color"
                            class="w-16 h-10 rounded cursor-pointer border border-gray-300">
                        <input type="text" x-model="formData.color"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                    </div>
                </div>

                <!-- Ícono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ícono
                    </label>
                    <div class="grid grid-cols-5 gap-2">
                        <template x-for="iconOption in iconOptions" :key="iconOption.value">
                            <button type="button" @click="formData.icon = iconOption.value"
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
                    <textarea x-model="formData.description" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                        placeholder="Añade una descripción..."></textarea>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="closeModal()"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="submitting"
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

    <!-- Command Palette Global (Ctrl+K) -->
    @include('components.command-palette')

    <!-- Scripts adicionales -->
    @stack('scripts')

</body>

</html>