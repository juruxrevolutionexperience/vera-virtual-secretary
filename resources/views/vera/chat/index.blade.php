<!DOCTYPE html>
<html lang="es" x-data="veraAssistant()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vera - Tu Asistente Virtual</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Animaciones suaves */
        .message-enter {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Typing indicator */
        .typing-dot {
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.7;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        /* Scroll suave */
        .messages-container {
            scroll-behavior: smooth;
        }

        /* Hover effects */
        .action-card:hover {
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }

        /* Pulse animation para alertas */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="antialiased bg-[#fafafa] font-['Inter']">
    <div class="flex h-screen overflow-x-hidden">
        @include('components.dashboard-sidebar')

        <main class="flex-1 min-w-0 lg:ml-56 flex flex-col overflow-x-hidden">
            <!-- Header Mejorado -->
            <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-4">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">V</span>
                    </div>
                    <div>
                        <h1 class="text-sm font-semibold text-gray-900">Vera - Tu Asistente</h1>
                        <p class="text-xs text-gray-500">Gestiona tu día de forma inteligente</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Indicador de estado -->
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 rounded-lg">
                        <div class="w-2 h-2 bg-green-500 rounded-full pulse"></div>
                        <span class="text-xs font-medium text-green-700">Activa</span>
                    </div>
                </div>
            </header>

            <div class="flex-1 flex overflow-hidden">
                <!-- Chat Principal -->
                <div class="flex-1 flex flex-col">
                    <!-- Mensajes -->
                    <div class="flex-1 overflow-y-auto p-6 messages-container" x-ref="messagesContainer">

                        <!-- Saludo Proactivo Inicial -->
                        <div x-show="messages.length === 0 && !isLoading" class="space-y-6">
                            <!-- Saludo con Contexto -->
                            <div class="flex gap-3 max-w-3xl">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold">V</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-5 py-4 shadow-sm">
                                        <p class="text-sm text-gray-900 font-medium mb-3">¡Hola! 👋 Soy Vera, tu asistente virtual.</p>
                                        <p class="text-sm text-gray-600 mb-4">Aquí está tu resumen de hoy:</p>

                                        <!-- Mini stats -->
                                        <div class="grid grid-cols-3 gap-2 mb-4">
                                            <div class="bg-purple-50 rounded-lg p-2.5 text-center">
                                                <p class="text-lg font-bold text-purple-700" x-text="context.unread_emails || 0"></p>
                                                <p class="text-xs text-purple-600">Emails sin leer</p>
                                            </div>
                                            <div class="bg-blue-50 rounded-lg p-2.5 text-center">
                                                <p class="text-lg font-bold text-blue-700" x-text="context.today_meetings || 0"></p>
                                                <p class="text-xs text-blue-600">Reuniones hoy</p>
                                            </div>
                                            <div class="bg-green-50 rounded-lg p-2.5 text-center">
                                                <p class="text-lg font-bold text-green-700" x-text="context.pending_actions || 0"></p>
                                                <p class="text-xs text-green-600">Acciones pendientes</p>
                                            </div>
                                        </div>

                                        <p class="text-sm text-gray-700 font-medium mb-3">¿Qué quieres hacer primero?</p>

                                        <!-- Acciones Sugeridas Dinámicas -->
                                        <div class="space-y-2">
                                            <template x-for="action in suggestedActions" :key="action.id">
                                                <button
                                                    @click="executeQuickAction(action)"
                                                    class="action-card w-full flex items-center gap-3 p-3 bg-gray-50 hover:bg-purple-50 rounded-lg text-left border border-transparent hover:border-purple-200 transition-all"
                                                >
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="action.bgColor">
                                                        <span x-text="action.icon" class="text-lg"></span>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium text-gray-900" x-text="action.title"></p>
                                                        <p class="text-xs text-gray-500" x-text="action.description"></p>
                                                    </div>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 mt-2 px-1">
                                        <span class="text-xs text-gray-500">Ahora</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tips de comandos -->
                            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-4 max-w-3xl">
                                <p class="text-xs font-medium text-gray-700 mb-2">💡 Comandos rápidos:</p>
                                <div class="flex flex-wrap gap-2">
                                    <code class="px-2 py-1 bg-white rounded text-xs text-purple-600 font-mono">/inbox</code>
                                    <code class="px-2 py-1 bg-white rounded text-xs text-purple-600 font-mono">/agenda</code>
                                    <code class="px-2 py-1 bg-white rounded text-xs text-purple-600 font-mono">/responder</code>
                                    <code class="px-2 py-1 bg-white rounded text-xs text-purple-600 font-mono">/crear-regla</code>
                                    <code class="px-2 py-1 bg-white rounded text-xs text-purple-600 font-mono">/stats</code>
                                </div>
                            </div>
                        </div>

                        <!-- Mensajes del Chat -->
                        <div x-show="messages.length > 0" class="space-y-6">
                            <template x-for="msg in messages" :key="msg.id">
                                <div class="message-enter" :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                    <div class="flex gap-3 max-w-3xl" :class="msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            <div
                                                :class="msg.role === 'user' ? 'bg-gray-200' : 'bg-gradient-to-br from-purple-500 to-purple-600'"
                                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                            >
                                                <span
                                                    :class="msg.role === 'user' ? 'text-gray-700' : 'text-white'"
                                                    class="text-sm font-semibold"
                                                    x-text="msg.role === 'user' ? '{{ substr(auth()->user()->name, 0, 1) }}' : 'V'"
                                                ></span>
                                            </div>
                                        </div>

                                        <!-- Contenido del mensaje -->
                                        <div class="flex-1">
                                            <!-- Burbuja -->
                                            <div
                                                :class="msg.role === 'user' ? 'bg-purple-600 text-white rounded-2xl rounded-tr-sm' : 'bg-white border border-gray-200 rounded-2xl rounded-tl-sm shadow-sm'"
                                                class="px-4 py-3"
                                            >
                                                <div
                                                    class="text-sm leading-relaxed"
                                                    :class="msg.role === 'user' ? 'text-white' : 'text-gray-900'"
                                                    x-html="formatMessage(msg.content)"
                                                ></div>
                                            </div>

                                            <!-- Action Cards (si los hay) -->
                                            <div x-show="msg.action_cards && msg.action_cards.length > 0" class="mt-3 space-y-2">
                                                <template x-for="card in msg.action_cards" :key="card.id">
                                                    <div class="bg-white border border-gray-200 rounded-lg p-3 hover:border-purple-300 transition-colors">
                                                        <div class="flex items-start gap-3">
                                                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span x-text="card.icon" class="text-lg"></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-medium text-gray-900" x-text="card.title"></p>
                                                                <p class="text-xs text-gray-500 mt-0.5" x-text="card.subtitle"></p>

                                                                <!-- Botones de acción -->
                                                                <div class="flex gap-2 mt-2">
                                                                    <template x-for="btn in card.actions" :key="btn.label">
                                                                        <button
                                                                            @click="executeCardAction(btn.action, card.data)"
                                                                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                                                                            :class="btn.primary ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                                                            x-text="btn.label"
                                                                        ></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Timestamp y feedback -->
                                            <div class="flex items-center gap-2 mt-1.5 px-1" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                                                <span class="text-xs text-gray-500" x-text="formatTime(msg.created_at)"></span>

                                                <!-- Feedback solo para Vera -->
                                                <div x-show="msg.role === 'assistant'" class="flex gap-1">
                                                    <button
                                                        @click="giveFeedback(msg.id, 'helpful')"
                                                        class="p-1 hover:bg-gray-100 rounded"
                                                        title="Útil"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-gray-400 hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="giveFeedback(msg.id, 'not_helpful')"
                                                        class="p-1 hover:bg-gray-100 rounded"
                                                        title="No útil"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-gray-400 hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Typing Indicator -->
                        <div x-show="isLoading" class="flex justify-start">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-semibold">V</span>
                                    </div>
                                </div>
                                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-5 py-3 shadow-sm">
                                    <div class="flex space-x-1.5">
                                        <div class="w-2 h-2 bg-purple-400 rounded-full typing-dot"></div>
                                        <div class="w-2 h-2 bg-purple-400 rounded-full typing-dot"></div>
                                        <div class="w-2 h-2 bg-purple-400 rounded-full typing-dot"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="border-t border-gray-200 bg-white p-4">
                        <form @submit.prevent="sendMessage" class="flex gap-3">
                            <input
                                type="text"
                                x-model="newMessage"
                                placeholder="Escribe tu mensaje o usa / para comandos..."
                                class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                                :disabled="isLoading"
                            >
                            <button
                                type="submit"
                                class="px-6 py-3 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                                :disabled="isLoading || !newMessage.trim()"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                <span>Enviar</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Panel Derecho: CONTEXTO ACTUAL -->
                <div class="w-80 border-l border-gray-200 bg-white flex flex-col overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Contexto Actual</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Vista rápida de tu día</p>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        <!-- Widget: Emails Hoy -->
                        <div class="bg-purple-50 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-semibold text-purple-900">📧 Emails Hoy</h4>
                                <span class="text-xs text-purple-600" x-text="context.unread_emails || 0"></span>
                            </div>
                            <div class="space-y-1 text-xs text-purple-700">
                                <div class="flex justify-between">
                                    <span>Sin leer</span>
                                    <span class="font-medium" x-text="context.unread_emails || 0"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Importantes</span>
                                    <span class="font-medium" x-text="context.important_emails || 0"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Requieren acción</span>
                                    <span class="font-medium" x-text="context.action_required || 0"></span>
                                </div>
                            </div>
                            <button
                                @click="useQuickAction('/inbox')"
                                class="w-full mt-2 px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-md hover:bg-purple-700 transition-colors"
                            >
                                Ver inbox
                            </button>
                        </div>

                        <!-- Widget: Agenda Hoy -->
                        <div class="bg-blue-50 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-semibold text-blue-900">📅 Agenda Hoy</h4>
                                <span class="text-xs text-blue-600" x-text="context.today_meetings || 0"></span>
                            </div>
                            <div class="space-y-2" x-show="context.meetings && context.meetings.length > 0">
                                <template x-for="meeting in context.meetings.slice(0, 3)" :key="meeting.id">
                                    <div class="bg-white rounded p-2">
                                        <p class="text-xs font-medium text-gray-900" x-text="meeting.time"></p>
                                        <p class="text-xs text-gray-600 truncate" x-text="meeting.title"></p>
                                    </div>
                                </template>
                            </div>
                            <p x-show="!context.meetings || context.meetings.length === 0" class="text-xs text-blue-600">
                                No tienes reuniones hoy
                            </p>
                            <button
                                @click="useQuickAction('/agenda')"
                                class="w-full mt-2 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors"
                            >
                                Ver agenda completa
                            </button>
                        </div>

                        <!-- Widget: Reglas Activas -->
                        <div class="bg-green-50 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-semibold text-green-900">⚡ Reglas Activas</h4>
                                <span class="text-xs text-green-600" x-text="context.active_rules || 0"></span>
                            </div>
                            <div class="space-y-1 text-xs text-green-700">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full"></div>
                                    <span>Newsletters → archivar</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full"></div>
                                    <span>Clientes VIP → prioridad</span>
                                </div>
                            </div>
                            <button
                                @click="useQuickAction('/crear-regla')"
                                class="w-full mt-2 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors"
                            >
                                Crear nueva regla
                            </button>
                        </div>

                        <!-- Widget: Alertas -->
                        <div x-show="context.alerts && context.alerts.length > 0" class="bg-orange-50 rounded-lg p-3">
                            <h4 class="text-xs font-semibold text-orange-900 mb-2">🔔 Alertas</h4>
                            <template x-for="alert in context.alerts" :key="alert.id">
                                <div class="flex items-start gap-2 mb-2">
                                    <div class="w-1.5 h-1.5 bg-orange-500 rounded-full mt-1.5"></div>
                                    <p class="text-xs text-orange-700" x-text="alert.message"></p>
                                </div>
                            </template>
                        </div>

                        <!-- Widget: Lo que Vera hizo hoy (Log de Actividad) -->
                        <div class="bg-gray-50 rounded-lg p-3">
                            <h4 class="text-xs font-semibold text-gray-900 mb-2">📝 Lo que Vera hizo hoy</h4>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <template x-for="activity in activityLog.slice(0, 10)" :key="activity.id">
                                    <div class="flex gap-2 text-xs">
                                        <span class="text-gray-500 flex-shrink-0" x-text="activity.time"></span>
                                        <span class="text-gray-700" x-text="activity.description"></span>
                                    </div>
                                </template>
                            </div>
                            <p x-show="activityLog.length === 0" class="text-xs text-gray-500">
                                Aún no hay actividad hoy
                            </p>
                        </div>

                        <!-- Widget: Stats Rápidas -->
                        <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-lg p-3">
                            <h4 class="text-xs font-semibold text-gray-900 mb-2">📊 Esta Semana</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-white rounded p-2 text-center">
                                    <p class="text-lg font-bold text-purple-600" x-text="context.week_emails || 0"></p>
                                    <p class="text-xs text-gray-600">Emails</p>
                                </div>
                                <div class="bg-white rounded p-2 text-center">
                                    <p class="text-lg font-bold text-blue-600" x-text="context.week_meetings || 0"></p>
                                    <p class="text-xs text-gray-600">Reuniones</p>
                                </div>
                            </div>
                            <button
                                @click="useQuickAction('/stats')"
                                class="w-full mt-2 px-3 py-1.5 bg-gradient-to-r from-purple-600 to-blue-600 text-white text-xs font-medium rounded-md hover:from-purple-700 hover:to-blue-700 transition-colors"
                            >
                                Ver estadísticas completas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Settings Modal -->
    @include('components.settings-modal')

    <script>
        function veraAssistant() {
            return {
                conversationId: {{ $conversation->id }},
                messages: [],
                newMessage: '',
                isLoading: false,
                context: {
                    unread_emails: 0,
                    important_emails: 0,
                    action_required: 0,
                    today_meetings: 0,
                    meetings: [],
                    active_rules: 0,
                    alerts: [],
                    week_emails: 0,
                    week_meetings: 0,
                    pending_actions: 0
                },
                activityLog: [],
                suggestedActions: [],

                async init() {
                    await this.loadMessages();
                    await this.loadContext();
                },

                async loadMessages() {
                    try {
                        const response = await fetch(`/vera/chat/conversation/${this.conversationId}/messages`);
                        const data = await response.json();
                        if (data.success) {
                            this.messages = data.messages;
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    } catch (error) {
                        console.error('Error cargando mensajes:', error);
                    }
                },

                async loadContext() {
                    try {
                        const response = await fetch('/vera/chat/context');
                        const data = await response.json();

                        if (data.success) {
                            this.context = data.context;
                            this.activityLog = data.activity_log || [];
                            this.suggestedActions = data.suggested_actions || [];
                        }
                    } catch (error) {
                        console.error('Error cargando contexto:', error);
                    }
                },

                async executeQuickAction(action) {
                    this.newMessage = action.action;
                    await this.sendMessage();
                },

                useQuickAction(command) {
                    this.newMessage = command;
                    this.sendMessage();
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.isLoading) return;

                    let messageText = this.newMessage;

                    // Procesar comandos
                    if (messageText.startsWith('/')) {
                        messageText = this.processCommand(messageText);
                    }

                    this.newMessage = '';
                    this.isLoading = true;

                    // Agregar mensaje del usuario
                    this.messages.push({
                        id: Date.now(),
                        role: 'user',
                        content: messageText,
                        created_at: new Date().toISOString()
                    });
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const response = await fetch('/vera/chat/message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                conversation_id: this.conversationId,
                                message: messageText
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Agregar respuesta de Vera
                            this.messages.push(data.message);

                            // Actualizar log de actividad
                            if (data.activity) {
                                this.activityLog.unshift(data.activity);
                            }

                            // Recargar contexto para actualizar estadísticas
                            await this.loadContext();

                            this.$nextTick(() => this.scrollToBottom());
                        } else {
                            alert('Error: ' + (data.error || 'Error desconocido'));
                        }
                    } catch (error) {
                        console.error('Error enviando mensaje:', error);
                        alert('Error enviando mensaje. Por favor intenta de nuevo.');
                    } finally {
                        this.isLoading = false;
                    }
                },

                processCommand(cmd) {
                    const commands = {
                        '/inbox': '¿Qué emails importantes tengo en mi bandeja de entrada?',
                        '/agenda': '¿Qué tengo en mi agenda para hoy?',
                        '/responder': 'Ayúdame a responder el último email importante',
                        '/crear-regla': 'Quiero crear una nueva regla de automatización',
                        '/stats': 'Muéstrame mis estadísticas de esta semana',
                        '/entrenar': 'Quiero entrenar mi estilo de escritura'
                    };

                    const baseCmd = cmd.split(' ')[0];
                    return commands[baseCmd] || cmd;
                },

                async executeCardAction(action, data) {
                    // Ejecutar acciones desde las cards
                    console.log('Ejecutando acción:', action, data);

                    switch(action) {
                        case 'view_email':
                            // TODO: Abrir modal con email
                            alert('Ver email: ' + data.id);
                            break;
                        case 'respond':
                            // TODO: Generar respuesta
                            this.newMessage = `Ayúdame a responder el email "${data.subject}"`;
                            this.sendMessage();
                            break;
                        case 'delegate':
                            // TODO: Delegar a Vera
                            alert('Delegando email a Vera...');
                            break;
                        case 'archive':
                            // TODO: Archivar
                            alert('Archivando...');
                            break;
                    }
                },

                async giveFeedback(messageId, feedback) {
                    try {
                        await fetch(`/vera/chat/message/${messageId}/feedback`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ feedback })
                        });
                    } catch (error) {
                        console.error('Error enviando feedback:', error);
                    }
                },

                scrollToBottom() {
                    this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                },

                formatTime(timestamp) {
                    const date = new Date(timestamp);
                    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                },

                formatMessage(content) {
                    return content
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/`(.*?)`/g, '<code class="px-1.5 py-0.5 bg-gray-100 rounded text-xs font-mono">$1</code>')
                        .replace(/\n/g, '<br>');
                }
            }
        }

    </script>
</body>
</html>
