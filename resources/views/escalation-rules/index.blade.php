@extends('layouts.app')

@section('title', 'Reglas de Escalado')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="escalationRulesApp()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Reglas de Escalado</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Automatiza acciones cuando los emails cumplan ciertas condiciones
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="processEscalations()"
                        :disabled="processing"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': processing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="processing ? 'Procesando...' : 'Procesar Ahora'"></span>
                </button>
                <button @click="alert('La creación de reglas complejas se implementará próximamente. Por ahora use la API.')"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Regla
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Reglas</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_rules || 0"></p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Activas</p>
                    <p class="text-2xl font-bold text-green-600" x-text="stats.active_rules || 0"></p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Inactivas</p>
                    <p class="text-2xl font-bold text-gray-600" x-text="stats.inactive_rules || 0"></p>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Ejecuciones</p>
                    <p class="text-2xl font-bold text-blue-600" x-text="stats.total_triggers || 0"></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Estado:</label>
            <select x-model="filters.is_active"
                    @change="loadRules()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900">
                <option value="">Todas</option>
                <option value="1">Activas</option>
                <option value="0">Inactivas</option>
            </select>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600">Cargando reglas...</p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && rules.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay reglas de escalado</h3>
        <p class="mt-1 text-sm text-gray-500">Las reglas automatizan acciones cuando se cumplen condiciones</p>
    </div>

    <!-- Rules List -->
    <div x-show="!loading && rules.length > 0" class="space-y-4">
        <template x-for="rule in rules" :key="rule.id">
            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900" x-text="rule.name"></h3>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                  x-text="rule.is_active ? 'ACTIVA' : 'INACTIVA'"></span>
                            <span class="px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded-full">
                                Prioridad: <span x-text="rule.priority"></span>
                            </span>
                        </div>

                        <!-- Description -->
                        <p class="text-gray-600 mb-3" x-text="rule.description || 'Sin descripción'" x-show="rule.description"></p>

                        <!-- Details -->
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div class="text-sm">
                                <span class="text-gray-500">Acción:</span>
                                <span class="font-medium text-gray-900 ml-2" x-text="getActionLabel(rule.action_type)"></span>
                            </div>
                            <div class="text-sm">
                                <span class="text-gray-500">Ejecuciones:</span>
                                <span class="font-medium text-gray-900 ml-2" x-text="rule.times_triggered || 0"></span>
                            </div>
                        </div>

                        <!-- Conditions (simplified) -->
                        <div class="text-xs text-gray-500">
                            <strong>Condiciones:</strong>
                            <span x-text="Object.keys(rule.conditions || {}).length + ' configuradas'"></span>
                        </div>

                        <!-- Last triggered -->
                        <div class="text-xs text-gray-500 mt-2" x-show="rule.last_triggered_at">
                            Última ejecución: <span x-text="formatDate(rule.last_triggered_at)"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2 ml-4">
                        <button @click="toggleRule(rule)"
                                :class="rule.is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700'"
                                class="px-3 py-1 text-sm text-white rounded-lg transition-colors">
                            <span x-text="rule.is_active ? 'Desactivar' : 'Activar'"></span>
                        </button>
                        <button @click="deleteRule(rule)"
                                class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && pagination.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-sm text-gray-600">
            Mostrando <span x-text="pagination.from"></span> a <span x-text="pagination.to"></span> de <span x-text="pagination.total"></span> reglas
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
function escalationRulesApp() {
    return {
        // State
        rules: [],
        stats: {},
        loading: true,
        processing: false,
        filters: {
            is_active: '',
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
            await this.loadRules();
        },

        // Load stats
        async loadStats() {
            try {
                const response = await fetch('/escalation-rules/stats', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        // Load rules
        async loadRules(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                });

                if (this.filters.is_active !== '') {
                    params.append('is_active', this.filters.is_active);
                }

                const response = await fetch(`/escalation-rules?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                this.rules = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total,
                    from: data.from,
                    to: data.to,
                };
            } catch (error) {
                console.error('Error loading rules:', error);
                alert('Error cargando reglas');
            } finally {
                this.loading = false;
            }
        },

        // Process escalations
        async processEscalations() {
            if (this.processing) return;

            this.processing = true;
            try {
                const response = await fetch('/escalation-rules/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                alert(`Procesamiento completado:\n- Emails: ${data.stats.emails_processed}\n- Reglas evaluadas: ${data.stats.rules_evaluated}\n- Coincidencias: ${data.stats.rules_matched}\n- Acciones: ${data.stats.actions_executed}`);

                await this.loadStats();
                await this.loadRules();
            } catch (error) {
                console.error('Error processing escalations:', error);
                alert('Error procesando escalados');
            } finally {
                this.processing = false;
            }
        },

        // Toggle rule
        async toggleRule(rule) {
            try {
                const url = rule.is_active
                    ? `/escalation-rules/${rule.id}/deactivate`
                    : `/escalation-rules/${rule.id}/activate`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                Object.assign(rule, data);
                await this.loadStats();
            } catch (error) {
                console.error('Error toggling rule:', error);
                alert('Error cambiando estado de la regla');
            }
        },

        // Delete rule
        async deleteRule(rule) {
            if (!confirm(`¿Seguro que quieres eliminar la regla "${rule.name}"?`)) return;

            try {
                const response = await fetch(`/escalation-rules/${rule.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) throw new Error('Error deleting rule');

                await this.loadStats();
                await this.loadRules();
                alert('Regla eliminada');
            } catch (error) {
                console.error('Error deleting rule:', error);
                alert('Error eliminando regla');
            }
        },

        // Pagination
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadRules(page);
            }
        },

        // Helpers
        getActionLabel(type) {
            const labels = {
                create_alert: 'Crear Alerta',
                notify_email: 'Notificar por Email',
                change_priority: 'Cambiar Prioridad',
                assign_to_folder: 'Asignar a Carpeta',
                add_tag: 'Añadir Etiqueta',
                mark_as_urgent: 'Marcar como Urgente',
                forward_email: 'Reenviar Email',
                custom: 'Personalizado',
            };
            return labels[type] || type;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        },
    }
}
</script>
@endsection
