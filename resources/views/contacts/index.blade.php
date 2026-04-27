@extends('layouts.app')

@section('title', 'Contactos')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="contactsApp()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Contactos</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Gestiona tus contactos y ve el historial de comunicación
                </p>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <button @click="syncFromEmails()"
                        :disabled="syncing"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar'"></span>
                </button>
                <button @click="openCreateModal()"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Contacto
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">VIP</p>
                    <p class="text-2xl font-bold text-yellow-600" x-text="stats.vip || 0"></p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Frecuentes</p>
                    <p class="text-2xl font-bold text-blue-600" x-text="stats.frequent || 0"></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Recientes (30d)</p>
                    <p class="text-2xl font-bold text-green-600" x-text="stats.recent_30_days || 0"></p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row items-center gap-4">
            <div class="flex-1 w-full">
                <input type="text"
                       x-model="search"
                       @input="debounceSearch()"
                       placeholder="Buscar por nombre, email, empresa..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <select x-model="filters.priority"
                        @change="loadContacts()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Todas las prioridades</option>
                    <option value="vip">VIP</option>
                    <option value="high">Alta</option>
                    <option value="normal">Normal</option>
                    <option value="low">Baja</option>
                </select>
                <button @click="toggleFilter('starred')"
                        :class="filters.starred ? 'bg-yellow-100 text-yellow-700 border-yellow-300' : 'bg-white text-gray-600 border-gray-300'"
                        class="px-4 py-2 border rounded-lg hover:bg-yellow-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" :class="filters.starred ? 'fill-yellow-600' : 'fill-none'" stroke="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Favoritos
                </button>
                <button @click="toggleFilter('blocked')"
                        :class="filters.blocked ? 'bg-red-100 text-red-700 border-red-300' : 'bg-white text-gray-600 border-gray-300'"
                        class="px-4 py-2 border rounded-lg hover:bg-red-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Bloqueados
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600">Cargando contactos...</p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && contacts.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay contactos</h3>
        <p class="mt-1 text-sm text-gray-500">Los contactos se crean automáticamente cuando recibes emails</p>
        <div class="mt-6">
            <button @click="syncFromEmails()"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg">
                Sincronizar desde Emails
            </button>
        </div>
    </div>

    <!-- Contacts Grid -->
    <div x-show="!loading && contacts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="contact in contacts" :key="contact.id">
            <div class="bg-white rounded-lg border border-gray-200 hover:border-purple-300 hover:shadow-lg transition-all p-6 relative">
                <!-- Priority Badge -->
                <div class="absolute top-4 right-4 flex items-center gap-2">
                    <span x-show="contact.is_starred"
                          class="inline-flex items-center">
                        <svg class="w-5 h-5 fill-yellow-400" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </span>
                    <span x-show="contact.priority === 'vip'"
                          class="px-2 py-0.5 text-xs font-bold bg-yellow-100 text-yellow-800 rounded-full">
                        VIP
                    </span>
                    <span x-show="contact.is_blocked"
                          class="px-2 py-0.5 text-xs font-bold bg-red-100 text-red-800 rounded-full">
                        Bloqueado
                    </span>
                </div>

                <!-- Avatar -->
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center text-white text-2xl font-bold">
                            <span x-text="getInitials(contact.name || contact.email)"></span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 truncate" x-text="contact.name || 'Sin nombre'"></h3>
                        <p class="text-sm text-gray-600 truncate" x-text="contact.email"></p>
                        <p x-show="contact.company" class="text-xs text-gray-500 truncate mt-1" x-text="contact.company"></p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-4 mb-4 pt-4 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600" x-text="contact.emails_received || 0"></p>
                        <p class="text-xs text-gray-600">Recibidos</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600" x-text="contact.emails_sent || 0"></p>
                        <p class="text-xs text-gray-600">Enviados</p>
                    </div>
                </div>

                <!-- Last Contact -->
                <div class="mb-4 pb-4 border-b border-gray-200">
                    <p class="text-xs text-gray-500">
                        Último contacto:
                        <span x-text="formatDate(contact.last_contact_at)"></span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <button @click="toggleStar(contact)"
                            :title="contact.is_starred ? 'Quitar favorito' : 'Marcar como favorito'"
                            class="flex-1 px-3 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" :class="contact.is_starred ? 'fill-yellow-400' : 'fill-none'" stroke="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </button>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="px-3 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false"
                             class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-10">
                            <button @click="viewContact(contact); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 rounded-t-lg">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Ver Historial
                            </button>
                            <button @click="openEditModal(contact); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar
                            </button>
                            <button @click="markAsVip(contact); open = false"
                                    x-show="contact.priority !== 'vip'"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Marcar como VIP
                            </button>
                            <button @click="toggleBlock(contact); open = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                                    :class="contact.is_blocked ? 'text-green-600' : 'text-red-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span x-text="contact.is_blocked ? 'Desbloquear' : 'Bloquear'"></span>
                            </button>
                            <button @click="deleteContact(contact); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2 rounded-b-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && pagination.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-sm text-gray-600">
            Mostrando <span x-text="pagination.from"></span> a <span x-text="pagination.to"></span> de <span x-text="pagination.total"></span> contactos
        </div>
        <div class="flex items-center gap-2">
            <button @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
            </button>
            <template x-for="page in getPageNumbers()" :key="page">
                <button @click="goToPage(page)"
                        :class="page === pagination.current_page ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="px-3 py-1 border border-gray-300 rounded-lg"
                        x-text="page"></button>
            </template>
            <button @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @include('contacts.partials.form-modal')
</div>

<script>
function contactsApp() {
    return {
        // State
        contacts: [],
        stats: {},
        loading: true,
        syncing: false,
        search: '',
        searchTimeout: null,
        filters: {
            priority: '',
            starred: false,
            blocked: false,
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0,
        },

        // Modal state
        modalOpen: false,
        editingContact: null,
        formData: {
            email: '',
            name: '',
            company: '',
            phone: '',
            notes: '',
            priority: 'normal',
            is_organization_shared: false,
        },

        // Init
        async init() {
            await this.loadStats();
            await this.loadContacts();
        },

        // Load stats
        async loadStats() {
            try {
                const response = await fetch('/contacts/stats', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        // Load contacts
        async loadContacts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                });

                if (this.search) params.append('search', this.search);
                if (this.filters.priority) params.append('priority', this.filters.priority);
                if (this.filters.starred) params.append('starred', '1');
                if (this.filters.blocked) params.append('blocked', '1');

                const response = await fetch(`/contacts?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                this.contacts = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total,
                    from: data.from,
                    to: data.to,
                };
            } catch (error) {
                console.error('Error loading contacts:', error);
                showNotification('Error cargando contactos', 'error');
            } finally {
                this.loading = false;
            }
        },

        // Debounce search
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadContacts(1);
            }, 500);
        },

        // Toggle filter
        toggleFilter(filter) {
            this.filters[filter] = !this.filters[filter];
            this.loadContacts(1);
        },

        // Sync from emails
        async syncFromEmails() {
            if (this.syncing) return;

            this.syncing = true;
            try {
                const response = await fetch('/contacts/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                showNotification(`Sincronización completada: ${data.stats.created} creados, ${data.stats.updated} actualizados`, 'success');

                await this.loadStats();
                await this.loadContacts();
            } catch (error) {
                console.error('Error syncing:', error);
                showNotification('Error sincronizando contactos', 'error');
            } finally {
                this.syncing = false;
            }
        },

        // Open create modal
        openCreateModal() {
            this.editingContact = null;
            this.formData = {
                email: '',
                name: '',
                company: '',
                phone: '',
                notes: '',
                priority: 'normal',
            };
            this.modalOpen = true;
        },

        // Open edit modal
        openEditModal(contact) {
            this.editingContact = contact;
            this.formData = {
                email: contact.email,
                name: contact.name || '',
                company: contact.company || '',
                phone: contact.phone || '',
                notes: contact.notes || '',
                priority: contact.priority || 'normal',
            };
            this.modalOpen = true;
        },

        // Save contact
        async saveContact() {
            try {
                const url = this.editingContact
                    ? `/contacts/${this.editingContact.id}`
                    : '/contacts';

                const method = this.editingContact ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.formData),
                });

                if (response.status === 422) {
                    const errorData = await response.json();
                    const firstError = errorData.errors
                        ? Object.values(errorData.errors)[0][0]
                        : (errorData.message || 'Por favor revisa los campos');
                    showNotification(firstError, 'warning');
                    return;
                }

                if (!response.ok) throw new Error('Error saving contact');

                this.modalOpen = false;
                await this.loadStats();
                await this.loadContacts();

                showNotification(this.editingContact ? 'Contacto actualizado' : 'Contacto creado', 'success');
            } catch (error) {
                console.error('Error saving contact:', error);
                showNotification('Error guardando contacto', 'error');
            }
        },

        // Toggle star
        async toggleStar(contact) {
            try {
                const response = await fetch(`/contacts/${contact.id}/toggle-star`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                contact.is_starred = data.is_starred;
                await this.loadStats();
            } catch (error) {
                console.error('Error toggling star:', error);
            }
        },

        // Mark as VIP
        async markAsVip(contact) {
            try {
                const response = await fetch(`/contacts/${contact.id}/mark-vip`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                contact.priority = data.priority;
                await this.loadStats();
                showNotification('Contacto marcado como VIP', 'success');
            } catch (error) {
                console.error('Error marking as VIP:', error);
            }
        },

        // Toggle block
        async toggleBlock(contact) {
            try {
                const url = contact.is_blocked
                    ? `/contacts/${contact.id}/unblock`
                    : `/contacts/${contact.id}/block`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                contact.is_blocked = data.is_blocked;
                await this.loadStats();
            } catch (error) {
                console.error('Error toggling block:', error);
            }
        },

        // Delete contact
        async deleteContact(contact) {
            if (!confirm(`¿Seguro que quieres eliminar a ${contact.name || contact.email}?`)) return;

            try {
                const response = await fetch(`/contacts/${contact.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) throw new Error('Error deleting contact');

                await this.loadStats();
                await this.loadContacts();
                showNotification('Contacto eliminado', 'success');
            } catch (error) {
                console.error('Error deleting contact:', error);
                showNotification('Error eliminando contacto', 'error');
            }
        },

        // View contact (redirect to show page)
        viewContact(contact) {
            window.location.href = `/contacts/${contact.id}`;
        },

        // Pagination
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadContacts(page);
            }
        },

        getPageNumbers() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            // Show max 5 pages
            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        // Helpers
        getInitials(text) {
            if (!text) return '?';
            const words = text.split(' ').filter(w => w.length > 0);
            if (words.length === 0) return '?';
            if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
            return (words[0][0] + words[words.length - 1][0]).toUpperCase();
        },

        formatDate(dateString) {
            if (!dateString) return 'Nunca';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));

            if (days === 0) return 'Hoy';
            if (days === 1) return 'Ayer';
            if (days < 7) return `Hace ${days} días`;
            if (days < 30) return `Hace ${Math.floor(days / 7)} semanas`;
            if (days < 365) return `Hace ${Math.floor(days / 30)} meses`;
            return date.toLocaleDateString('es-ES');
        },
    }
}

function showNotification(message, type = 'success') {
    const colors = {
        success: 'bg-green-500',
        error:   'bg-red-500',
        info:    'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    const bgColor = colors[type] || 'bg-gray-500';

    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-1/2 -translate-x-1/2 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>
@endsection
