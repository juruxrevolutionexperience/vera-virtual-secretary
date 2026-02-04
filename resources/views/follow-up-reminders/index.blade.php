@extends('layouts.app')

@section('title', 'Recordatorios de Seguimiento')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="remindersApp()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Recordatorios de Seguimiento</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Gestiona tus recordatorios y seguimientos de emails
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="alert('Crear recordatorio desde la vista de un email específico')"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Recordatorio
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pendientes</p>
                    <p class="text-2xl font-bold text-blue-600" x-text="stats.pending || 0"></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Vencidos</p>
                    <p class="text-2xl font-bold text-red-600" x-text="stats.overdue || 0"></p>
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
                    <p class="text-sm text-gray-600">Completados</p>
                    <p class="text-2xl font-bold text-green-600" x-text="stats.completed || 0"></p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Estado:</label>
            <select x-model="filters.status"
                    @change="loadReminders()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900">
                <option value="">Todos</option>
                <option value="pending">Pendientes</option>
                <option value="sent">Enviados</option>
                <option value="completed">Completados</option>
                <option value="cancelled">Cancelados</option>
            </select>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="filters.overdue" @change="loadReminders()" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm font-medium text-gray-700">Solo vencidos</span>
            </label>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600">Cargando recordatorios...</p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && reminders.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay recordatorios</h3>
        <p class="mt-1 text-sm text-gray-500">Los recordatorios te ayudan a hacer seguimiento de emails importantes</p>
    </div>

    <!-- Reminders List -->
    <div x-show="!loading && reminders.length > 0" class="space-y-4">
        <template x-for="reminder in reminders" :key="reminder.id">
            <div class="bg-white rounded-lg border-l-4 p-6 hover:shadow-lg transition-shadow"
                 :class="{
                     'border-red-500 bg-red-50': reminder.status === 'pending' && new Date(reminder.remind_at) < new Date(),
                     'border-blue-500 bg-blue-50': reminder.status === 'pending' && new Date(reminder.remind_at) >= new Date(),
                     'border-green-500 bg-green-50': reminder.status === 'completed',
                     'border-gray-500 bg-gray-50': reminder.status === 'cancelled'
                 }">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl" x-text="getStatusIcon(reminder)"></span>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="reminder.title"></h3>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="{
                                      'bg-red-100 text-red-800': reminder.status === 'pending' && new Date(reminder.remind_at) < new Date(),
                                      'bg-blue-100 text-blue-800': reminder.status === 'pending' && new Date(reminder.remind_at) >= new Date(),
                                      'bg-green-100 text-green-800': reminder.status === 'completed',
                                      'bg-gray-100 text-gray-800': reminder.status === 'cancelled'
                                  }"
                                  x-text="getStatusLabel(reminder)"></span>
                            <span class="px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded-full"
                                  x-show="reminder.is_recurring">
                                🔁 Recurrente
                            </span>
                        </div>

                        <!-- Notes -->
                        <p class="text-gray-600 mb-3" x-show="reminder.notes" x-text="reminder.notes"></p>

                        <!-- Email Info -->
                        <template x-if="reminder.email">
                            <div class="bg-white rounded-lg border border-gray-200 p-3 mb-3">
                                <p class="text-sm font-medium text-gray-900" x-text="reminder.email.subject"></p>
                                <p class="text-xs text-gray-600 mt-1">
                                    De: <span x-text="reminder.email.from_name || reminder.email.from_email"></span>
                                </p>
                                <a :href="`/emails/${reminder.email.id}`"
                                   class="text-xs text-purple-600 hover:text-purple-700 font-medium mt-2 inline-block">
                                    Ver email →
                                </a>
                            </div>
                        </template>

                        <!-- Remind At -->
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span>
                                <strong>Recordar:</strong>
                                <span x-text="formatDate(reminder.remind_at)"></span>
                                <span :class="{ 'text-red-600 font-bold': new Date(reminder.remind_at) < new Date() && reminder.status === 'pending' }">
                                    (<span x-text="getTimeAgo(reminder.remind_at)"></span>)
                                </span>
                            </span>
                            <span x-show="reminder.completed_at">
                                | Completado: <span x-text="formatDate(reminder.completed_at)"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2 ml-4">
                        <button @click="completeReminder(reminder)"
                                x-show="reminder.status === 'pending'"
                                class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            Completar
                        </button>
                        <button @click="snoozeReminder(reminder)"
                                x-show="reminder.status === 'pending'"
                                class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            Posponer
                        </button>
                        <button @click="cancelReminder(reminder)"
                                x-show="reminder.status === 'pending'"
                                class="px-3 py-1 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            Cancelar
                        </button>
                        <button @click="deleteReminder(reminder)"
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
            Mostrando <span x-text="pagination.from"></span> a <span x-text="pagination.to"></span> de <span x-text="pagination.total"></span> recordatorios
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
function remindersApp() {
    return {
        // State
        reminders: [],
        stats: {},
        loading: true,
        filters: {
            status: '',
            overdue: false,
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
            await this.loadReminders();
        },

        // Load stats
        async loadStats() {
            try {
                const response = await fetch('/follow-up-reminders/stats', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        // Load reminders
        async loadReminders(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                });

                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.overdue) params.append('overdue', '1');

                const response = await fetch(`/follow-up-reminders?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                this.reminders = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total,
                    from: data.from,
                    to: data.to,
                };
            } catch (error) {
                console.error('Error loading reminders:', error);
                alert('Error cargando recordatorios');
            } finally {
                this.loading = false;
            }
        },

        // Complete reminder
        async completeReminder(reminder) {
            try {
                const response = await fetch(`/follow-up-reminders/${reminder.id}/complete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                Object.assign(reminder, data);
                await this.loadStats();
            } catch (error) {
                console.error('Error completing reminder:', error);
                alert('Error completando recordatorio');
            }
        },

        // Snooze reminder
        async snoozeReminder(reminder) {
            const minutes = prompt('¿Cuántos minutos posponer? (ej: 60 = 1 hora, 1440 = 1 día)', '60');
            if (!minutes) return;

            try {
                const response = await fetch(`/follow-up-reminders/${reminder.id}/snooze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ minutes: parseInt(minutes) })
                });

                const data = await response.json();
                Object.assign(reminder, data);
                alert('Recordatorio pospuesto');
            } catch (error) {
                console.error('Error snoozing reminder:', error);
                alert('Error posponiendo recordatorio');
            }
        },

        // Cancel reminder
        async cancelReminder(reminder) {
            try {
                const response = await fetch(`/follow-up-reminders/${reminder.id}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                Object.assign(reminder, data);
                await this.loadStats();
            } catch (error) {
                console.error('Error cancelling reminder:', error);
                alert('Error cancelando recordatorio');
            }
        },

        // Delete reminder
        async deleteReminder(reminder) {
            if (!confirm(`¿Seguro que quieres eliminar el recordatorio "${reminder.title}"?`)) return;

            try {
                const response = await fetch(`/follow-up-reminders/${reminder.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) throw new Error('Error deleting reminder');

                await this.loadStats();
                await this.loadReminders();
                alert('Recordatorio eliminado');
            } catch (error) {
                console.error('Error deleting reminder:', error);
                alert('Error eliminando recordatorio');
            }
        },

        // Pagination
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadReminders(page);
            }
        },

        // Helpers
        getStatusIcon(reminder) {
            if (reminder.status === 'completed') return '✅';
            if (reminder.status === 'cancelled') return '❌';
            if (new Date(reminder.remind_at) < new Date()) return '🔴';
            return '⏰';
        },

        getStatusLabel(reminder) {
            if (reminder.status === 'completed') return 'COMPLETADO';
            if (reminder.status === 'cancelled') return 'CANCELADO';
            if (new Date(reminder.remind_at) < new Date()) return 'VENCIDO';
            return 'PENDIENTE';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        },

        getTimeAgo(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = date - now;
            const minutes = Math.floor(Math.abs(diff) / 60000);
            const hours = Math.floor(Math.abs(diff) / 3600000);
            const days = Math.floor(Math.abs(diff) / 86400000);

            if (diff < 0) {
                if (minutes < 60) return `hace ${minutes} min`;
                if (hours < 24) return `hace ${hours} h`;
                return `hace ${days} d`;
            } else {
                if (minutes < 60) return `en ${minutes} min`;
                if (hours < 24) return `en ${hours} h`;
                return `en ${days} d`;
            }
        },
    }
}
</script>
@endsection
