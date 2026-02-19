<!-- Create/Edit Contact Modal -->
<div x-show="modalOpen"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50"
         @click="modalOpen = false">
    </div>

    <!-- Modal -->
    <div class="flex items-center justify-center min-h-screen px-4">
        <div x-show="modalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <span x-text="editingContact ? 'Editar Contacto' : 'Nuevo Contacto'"></span>
                </h3>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 space-y-4">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           x-model="formData.email"
                           :readonly="editingContact !== null"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           :class="editingContact ? 'bg-gray-100 cursor-not-allowed' : ''"
                           placeholder="contacto@ejemplo.com">
                    <p x-show="editingContact" class="text-xs text-gray-500 mt-1">
                        El email no se puede modificar
                    </p>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre
                    </label>
                    <input type="text"
                           x-model="formData.name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           placeholder="Juan Pérez">
                </div>

                <!-- Company -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Empresa
                    </label>
                    <input type="text"
                           x-model="formData.company"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           placeholder="Acme Inc.">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Teléfono
                    </label>
                    <input type="tel"
                           x-model="formData.phone"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                           placeholder="+34 600 000 000">
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Prioridad
                    </label>
                    <select x-model="formData.priority"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900">
                        <option value="low">Baja</option>
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                        <option value="vip">VIP</option>
                    </select>
                </div>

                <!-- Compartir con Organización (solo plan Enterprise) -->
                @if(auth()->user()->hasOrganizationAccess() && auth()->user()->organizations()->wherePivot('is_active', true)->exists())
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.is_organization_shared"
                            class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">🏢 Compartir con mi organización</span>
                            <p class="text-xs text-gray-600 mt-1">
                                Todos los miembros podrán ver y usar este contacto
                            </p>
                        </div>
                    </label>
                </div>
                @endif

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notas
                    </label>
                    <textarea x-model="formData.notes"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white text-gray-900"
                              placeholder="Notas adicionales sobre el contacto..."></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                <button @click="modalOpen = false"
                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button @click="saveContact()"
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <span x-text="editingContact ? 'Actualizar' : 'Crear'"></span>
                </button>
            </div>

        </div>
    </div>
</div>
