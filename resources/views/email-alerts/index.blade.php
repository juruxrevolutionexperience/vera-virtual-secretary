@extends('layouts.app')

@section('title', 'Centro de Alertas')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="alertsApp()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Centro de Alertas</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Gestiona las alertas y notificaciones de tus emails
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="runChecks()"
                        :disabled="checking"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': checking }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="checking ? 'Verificando...' : 'Verificar Ahora'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Activas</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_active || 0"></p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Críticas</p>
                    <p class="text-2xl font-bold text-red-600" x-text="stats.critical || 0"></p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Alta Prioridad</p>
                    <p class="text-2xl font-bold text-orange-600" x-text="stats.high || 0"></p>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Por Tipo</p>
                    <p class="text-sm text-gray-900 mt-1">
                        <template x-for="(count, type) in stats.by_type" :key="type">
                            <span class="block" x-text="type + ': ' + count"></span>
                        </template>
                        <span x-show="!stats.by_type || Object.keys(stats.by_type).length === 0" class="text-gray-400">Sin alertas</span>
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row items-center gap-4">
            <div class="flex items-center gap-2 w-full md:w-auto">
                <label class="text-sm font-medium text-gray-700">Estado:</label>
                <select x-model="filters.status"
                        @change="loadAlerts()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900">
                    <option value="">Activas</option>
                    <option value="pending">Pendientes</option>
                    <option value="acknowledged">Reconocidas</option>
                    <option value="resolved">Resueltas</option>
                    <option value="dismissed">Descartadas</option>
                </select>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <label class="text-sm font-medium text-gray-700">Severidad:</label>
                <select x-model="filters.severity"
                        @change="loadAlerts()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900">
                    <option value="">Todas</option>
                    <option value="critical">Críticas</option>
                    <option value="high">Alta</option>
                    <option value="medium">Media</option>
                    <option value="low">Baja</option>
                </select>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <label class="text-sm font-medium text-gray-700">Tipo:</label>
                <select x-model="filters.type"
                        @change="loadAlerts()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900">
                    <option value="">Todos</option>
                    <option value="deadline">Fecha límite</option>
                    <option value="no_response">Sin respuesta</option>
                    <option value="overdue">Vencido</option>
                    <option value="high_priority">Alta prioridad</option>
                    <option value="follow_up">Seguimiento</option>
                    <option value="escalation">Escalado</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600">Cargando alertas...</p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && alerts.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay alertas</h3>
        <p class="mt-1 text-sm text-gray-500">Las alertas aparecerán aquí cuando se detecten situaciones que requieran atención</p>
        <div class="mt-6">
            <button @click="runChecks()"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg">
                Verificar Alertas
            </button>
        </div>
    </div>

    <!-- Alerts List -->
    <div x-show="!loading && alerts.length > 0" class="space-y-4">
        <template x-for="alert in alerts" :key="alert.id">
            <div class="bg-white rounded-lg border-l-4 p-6 hover:shadow-lg transition-shadow"
                 :class="{
                     'border-red-500 bg-red-50': alert.severity === 'critical',
                     'border-orange-500 bg-orange-50': alert.severity === 'high',
                     'border-yellow-500 bg-yellow-50': alert.severity === 'medium',
                     'border-blue-500 bg-blue-50': alert.severity === 'low'
                 }">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl" x-text="getAlertIcon(alert.type)"></span>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="alert.title"></h3>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="{
                                      'bg-red-100 text-red-800': alert.severity === 'critical',
                                      'bg-orange-100 text-orange-800': alert.severity === 'high',
                                      'bg-yellow-100 text-yellow-800': alert.severity === 'medium',
                                      'bg-blue-100 text-blue-800': alert.severity === 'low'
                                  }"
                                  x-text="alert.severity.toUpperCase()"></span>
                            <span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full"
                                  x-text="getTypeLabel(alert.type)"></span>
                        </div>

                        <!-- Message -->
                        <p class="text-gray-700 mb-3" x-text="alert.message"></p>

                        <!-- Email Info -->
                        <template x-if="alert.email">
                            <div class="bg-white rounded-lg border border-gray-200 p-3 mb-3">
                                <p class="text-sm font-medium text-gray-900" x-text="alert.email.subject"></p>
                                <p class="text-xs text-gray-600 mt-1">
                                    De: <span x-text="alert.email.from_name || alert.email.from_email"></span>
                                </p>
                                <a :href="`/emails/${alert.email.id}`"
                                   class="text-xs text-purple-600 hover:text-purple-700 font-medium mt-2 inline-block">
                                    Ver email →
                                </a>
                            </div>
                        </template>

                        <!-- Metadata -->
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span>Creada: <span x-text="formatDate(alert.created_at)"></span></span>
                            <span x-show="alert.acknowledged_at">
                                Reconocida: <span x-text="formatDate(alert.acknowledged_at)"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2 ml-4">
                        <button @click="acknowledgeAlert(alert)"
                                x-show="alert.status === 'pending'"
                                class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            Reconocer
                        </button>
                        <button @click="resolveAlert(alert)"
                                x-show="alert.status !== 'resolved'"
                                class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            Resolver
                        </button>
                        <button @click="dismissAlert(alert)"
                                x-show="alert.status !== 'dismissed'"
                                class="px-3 py-1 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            Descartar
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && pagination.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-sm text-gray-600">
            Mostrando <span x-text="pagination.from"></span> a <span x-text="pagination.to"></span> de <span x-text="pagination.total"></span> alertas
        </div>
        <div class="flex items-center gap-2">
            <button @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
            </button>
            <span class="text-sm text-gray-600">
                Página <span x-text="pagination.current_page"></span> de <span x-text="pagination.last_page"></span>
            </span>
            <button @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>
    </div>
</div>

<script>
function alertsApp() {
    return {
        // State
        alerts: [],
        stats: {},
        loading: true,
        checking: false,
        filters: {
            status: '',
            severity: '',
            type: '',
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0,
        },

        // Init
        async init() {
            await this.loadStats();
            await this.loadAlerts();
        },

        // Load stats
        async loadStats() {
            try {
                const response = await fetch('/email-alerts/stats', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        // Load alerts
        async loadAlerts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                });

                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.severity) params.append('severity', this.filters.severity);
                if (this.filters.type) params.append('type', this.filters.type);

                const response = await fetch(`/email-alerts?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                this.alerts = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total,
                    from: data.from,
                    to: data.to,
                };
            } catch (error) {
                console.error('Error loading alerts:', error);
                alert('Error cargando alertas');
            } finally {
                this.loading = false;
            }
        },

        // Run checks
        async runChecks() {
            if (this.checking) return;

            this.checking = true;
            try {
                const response = await fetch('/email-alerts/run-checks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                alert(`Verificación completada:\n- Deadline: ${data.stats.deadline_alerts}\n- Sin respuesta: ${data.stats.no_response_alerts}\n- Vencidos: ${data.stats.overdue_alerts}\n- Alta prioridad: ${data.stats.high_priority_alerts}`);

                await this.loadStats();
                await this.loadAlerts();
            } catch (error) {
                console.error('Error running checks:', error);
                alert('Error ejecutando verificación');
            } finally {
                this.checking = false;
            }
        },

        // Acknowledge alert
        async acknowledgeAlert(alert) {
            try {
                const response = await fetch(`/email-alerts/${alert.id}/acknowledge`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                Object.assign(alert, data);
                await this.loadStats();
            } catch (error) {
                console.error('Error acknowledging alert:', error);
                alert('Error reconociendo alerta');
            }
        },

        // Resolve alert
        async resolveAlert(alert) {
            try {
                const response = await fetch(`/email-alerts/${alert.id}/resolve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ notes: '' })
                });

                const data = await response.json();
                Object.assign(alert, data);
                await this.loadStats();
                await this.loadAlerts();
            } catch (error) {
                console.error('Error resolving alert:', error);
                alert('Error resolviendo alerta');
            }
        },

        // Dismiss alert
        async dismissAlert(alert) {
            try {
                const response = await fetch(`/email-alerts/${alert.id}/dismiss`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                Object.assign(alert, data);
                await this.loadStats();
                await this.loadAlerts();
            } catch (error) {
                console.error('Error dismissing alert:', error);
                alert('Error descartando alerta');
            }
        },

        // Pagination
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadAlerts(page);
            }
        },

        // Helpers
        getAlertIcon(type) {
            const icons = {
                deadline: '⏰',
                no_response: '❌',
                follow_up: '🔔',
                escalation: '⚠️',
                overdue: '🚨',
                high_priority: '🔴',
                custom: '📌',
            };
            return icons[type] || '🔔';
        },

        getTypeLabel(type) {
            const labels = {
                deadline: 'Fecha Límite',
                no_response: 'Sin Respuesta',
                follow_up: 'Seguimiento',
                escalation: 'Escalado',
                overdue: 'Vencido',
                high_priority: 'Alta Prioridad',
                custom: 'Personalizado',
            };
            return labels[type] || type;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);

            if (minutes < 1) return 'Ahora';
            if (minutes < 60) return `Hace ${minutes} min`;
            if (hours < 24) return `Hace ${hours} h`;
            if (days < 7) return `Hace ${days} d`;
            return date.toLocaleDateString('es-ES');
        },
    }
}
</script>
@endsection
