@extends('layouts.app')

@section('title', 'Templates de Email')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="templatesApp()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Templates de Email</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Crea plantillas reutilizables para responder emails más rápido
                </p>
            </div>
            <button @click="openCreateModal()"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Template
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex items-center gap-4">
        <div class="flex-1">
            <input type="text"
                   x-model="search"
                   @input="filterTemplates()"
                   placeholder="Buscar templates..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        </div>
        <select x-model="typeFilter"
                @change="filterTemplates()"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            <option value="">Todos los tipos</option>
            <option value="reply">Respuesta</option>
            <option value="forward">Reenvío</option>
            <option value="new">Nuevo Email</option>
            <option value="auto_response">Auto-respuesta</option>
        </select>
    </div>

    <!-- Templates Grid -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600">Cargando templates...</p>
    </div>

    <div x-show="!loading && filteredTemplates.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay templates</h3>
        <p class="mt-1 text-sm text-gray-500">Comienza creando tu primer template</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="template in filteredTemplates" :key="template.id">
            <div class="bg-white rounded-lg border border-gray-200 hover:border-purple-300 hover:shadow-lg transition-all p-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="template.name"></h3>
                        <span class="inline-block mt-1 px-2 py-1 text-xs font-medium rounded-full text-white"
                              :class="{
                                  'bg-blue-600': template.type === 'reply',
                                  'bg-green-600': template.type === 'forward',
                                  'bg-purple-600': template.type === 'new',
                                  'bg-orange-600': template.type === 'auto_response'
                              }"
                              x-text="getTypeLabel(template.type)"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleActive(template)"
                                :title="template.is_active ? 'Desactivar' : 'Activar'"
                                class="p-1 hover:bg-gray-100 rounded">
                            <svg class="w-5 h-5" :class="template.is_active ? 'text-green-600' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-1 hover:bg-gray-100 rounded">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-10">
                                <button @click="openEditModal(template); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </button>
                                <button @click="duplicateTemplate(template); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Duplicar
                                </button>
                                <button @click="deleteTemplate(template); open = false" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <p class="text-sm text-gray-600 mb-4 line-clamp-2" x-text="template.description || 'Sin descripción'"></p>

                <!-- Subject Preview -->
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-500 mb-1">Asunto:</p>
                    <p class="text-sm text-gray-900 font-medium truncate" x-text="template.subject"></p>
                </div>

                <!-- Body Preview -->
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-500 mb-1">Contenido:</p>
                    <p class="text-sm text-gray-600 line-clamp-3" x-text="template.body"></p>
                </div>

                <!-- Stats -->
                <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-200 pt-4">
                    <span x-text="`Usado ${template.usage_count || 0} veces`"></span>
                    <span x-show="template.is_default" class="text-purple-600 font-medium">⭐ Por defecto</span>
                </div>
            </div>
        </template>
    </div>

    <!-- Modal Crear/Editar Template -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div @click.away="closeModal()"
             x-show="modalOpen"
             x-transition
             class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
                <h3 class="text-lg font-semibold text-gray-900" x-text="isEditing ? 'Editar Template' : 'Nuevo Template'"></h3>
            </div>

            <!-- Form -->
            <form @submit.prevent="saveTemplate()" class="px-6 py-4 space-y-4">

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="form.name"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           placeholder="Ej: Respuesta de bienvenida">
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.type"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white text-gray-900">
                        <option value="reply" class="text-gray-900 bg-white">Respuesta</option>
                        <option value="forward" class="text-gray-900 bg-white">Reenvío</option>
                        <option value="new" class="text-gray-900 bg-white">Nuevo Email</option>
                        <option value="auto_response" class="text-gray-900 bg-white">Auto-respuesta</option>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Descripción
                    </label>
                    <input type="text"
                           x-model="form.description"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           placeholder="Breve descripción del template">
                </div>

                <!-- Subject -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Asunto <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="form.subject"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           placeholder="Asunto del email">
                </div>

                <!-- Body -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Mensaje <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="form.body"
                              required
                              rows="8"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                              placeholder="Contenido del email..."></textarea>
                    <p class="mt-2 text-xs text-gray-600">
                        💡 Usa placeholders: @{{nombre}}, @{{fecha}}, @{{asunto_original}}, @{{remitente}}
                    </p>
                </div>

                <!-- Compartir con Organización (solo plan Enterprise) -->
                @if(auth()->user()->hasOrganizationAccess() && auth()->user()->organizations()->wherePivot('is_active', true)->exists())
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_organization_shared"
                            class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">🏢 Compartir con mi organización</span>
                            <p class="text-xs text-gray-600 mt-1">
                                Todos los miembros podrán usar este template
                            </p>
                        </div>
                    </label>
                </div>
                @endif

                <!-- Checkboxes -->
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="form.is_active" class="rounded text-purple-600 bg-white">
                        <span class="text-sm text-gray-900 font-medium">Activo</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="form.is_default" class="rounded text-purple-600 bg-white">
                        <span class="text-sm text-gray-900 font-medium">Establecer como predeterminado</span>
                    </label>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button"
                            @click="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            :disabled="saving"
                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-md transition-colors disabled:opacity-50">
                        <span x-show="!saving" x-text="isEditing ? 'Actualizar' : 'Crear Template'"></span>
                        <span x-show="saving">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function templatesApp() {
    return {
        templates: [],
        filteredTemplates: [],
        loading: true,
        modalOpen: false,
        isEditing: false,
        saving: false,
        search: '',
        typeFilter: '',
        form: {
            id: null,
            name: '',
            type: 'reply',
            description: '',
            subject: '',
            body: '',
            is_active: true,
            is_default: false,
            is_organization_shared: false,
        },

        init() {
            this.loadTemplates();
        },

        async loadTemplates() {
            try {
                const response = await fetch('/email-templates', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Error al cargar templates');

                const data = await response.json();
                this.templates = data.templates;
                this.filteredTemplates = data.templates;
                this.loading = false;
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los templates');
                this.loading = false;
            }
        },

        filterTemplates() {
            let filtered = this.templates;

            // Filtro por búsqueda
            if (this.search) {
                filtered = filtered.filter(t =>
                    t.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    t.subject.toLowerCase().includes(this.search.toLowerCase()) ||
                    t.body.toLowerCase().includes(this.search.toLowerCase())
                );
            }

            // Filtro por tipo
            if (this.typeFilter) {
                filtered = filtered.filter(t => t.type === this.typeFilter);
            }

            this.filteredTemplates = filtered;
        },

        getTypeLabel(type) {
            const labels = {
                'reply': 'Respuesta',
                'forward': 'Reenvío',
                'new': 'Nuevo',
                'auto_response': 'Auto-respuesta'
            };
            return labels[type] || type;
        },

        openCreateModal() {
            this.isEditing = false;
            this.form = {
                id: null,
                name: '',
                type: 'reply',
                description: '',
                subject: '',
                body: '',
                is_active: true,
                is_default: false,
            };
            this.modalOpen = true;
        },

        openEditModal(template) {
            this.isEditing = true;
            this.form = {
                id: template.id,
                name: template.name,
                type: template.type,
                description: template.description || '',
                subject: template.subject,
                body: template.body,
                is_active: template.is_active,
                is_default: template.is_default,
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.isEditing = false;
        },

        async saveTemplate() {
            this.saving = true;

            try {
                const url = this.isEditing
                    ? `/email-templates/${this.form.id}`
                    : '/email-templates';

                const method = this.isEditing ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                if (!response.ok) throw new Error('Error al guardar template');

                const data = await response.json();

                // Actualizar lista
                await this.loadTemplates();

                this.closeModal();
                this.showNotification(data.message, 'success');

            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar el template');
            } finally {
                this.saving = false;
            }
        },

        async toggleActive(template) {
            try {
                const response = await fetch(`/email-templates/${template.id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error');

                await this.loadTemplates();
                this.showNotification('Estado actualizado', 'success');
            } catch (error) {
                alert('Error al cambiar estado');
            }
        },

        async duplicateTemplate(template) {
            const name = prompt('Nombre para el template duplicado:', template.name + ' (copia)');
            if (!name) return;

            try {
                const response = await fetch(`/email-templates/${template.id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name })
                });

                if (!response.ok) throw new Error('Error');

                await this.loadTemplates();
                this.showNotification('Template duplicado', 'success');
            } catch (error) {
                alert('Error al duplicar template');
            }
        },

        async deleteTemplate(template) {
            if (!confirm(`¿Eliminar el template "${template.name}"?`)) return;

            try {
                const response = await fetch(`/email-templates/${template.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error');

                await this.loadTemplates();
                this.showNotification('Template eliminado', 'success');
            } catch (error) {
                alert('Error al eliminar template');
            }
        },

        showNotification(message, type = 'success') {
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const notification = document.createElement('div');
            notification.className = `fixed top-4 left-1/2 -translate-x-1/2 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }
}
</script>
@endsection
