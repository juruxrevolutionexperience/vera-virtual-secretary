<!-- Modal Template Selector -->
<div x-data="templateSelector()"
     x-show="isOpen"
     x-cloak
     @open-template-selector.window="openSelector($event.detail)"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50"
         @click="closeSelector()">
    </div>

    <!-- Modal -->
    <div class="flex items-center justify-center min-h-screen px-4">
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Seleccionar Template
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Elige un template para tu respuesta
                    </p>
                </div>
                <button @click="closeSelector()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 py-4">

                <!-- Loading State -->
                <div x-show="loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="animate-spin h-10 w-10 mx-auto text-purple-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-600 mt-3">Cargando templates...</p>
                    </div>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && templates.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mt-4">No hay templates disponibles</h3>
                    <p class="text-gray-500 mt-2">Crea templates desde la sección de gestión</p>
                    <a href="/email-templates/manage" class="inline-block mt-4 text-purple-600 hover:text-purple-700 font-medium">
                        Ir a gestión de templates
                    </a>
                </div>

                <!-- Templates Grid -->
                <div x-show="!loading && templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="template in templates" :key="template.id">
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-300 hover:shadow-md transition-all cursor-pointer"
                             :class="{'ring-2 ring-purple-500': selectedTemplate && selectedTemplate.id === template.id}"
                             @click="selectTemplate(template)">

                            <!-- Template Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900" x-text="template.name"></h4>
                                    <p class="text-xs text-gray-500 mt-1" x-text="template.description || 'Sin descripción'"></p>
                                </div>
                                <div class="flex items-center gap-2 ml-3">
                                    <!-- Badge de tipo -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        <span x-text="template.type"></span>
                                    </span>
                                    <!-- Badge por defecto -->
                                    <span x-show="template.is_default" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        ⭐ Default
                                    </span>
                                </div>
                            </div>

                            <!-- Template Preview -->
                            <div class="bg-gray-50 rounded p-3 mb-3">
                                <p class="text-xs font-medium text-gray-700 mb-1">Asunto:</p>
                                <p class="text-sm text-gray-900 mb-2" x-text="template.subject"></p>
                                <p class="text-xs font-medium text-gray-700 mb-1">Mensaje:</p>
                                <p class="text-sm text-gray-600 line-clamp-3" x-text="template.body.substring(0, 150) + '...'"></p>
                            </div>

                            <!-- Template Actions -->
                            <div class="flex items-center gap-2">
                                <button @click.stop="previewTemplate(template)"
                                        class="flex-1 px-3 py-1.5 text-sm bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition-colors">
                                    👁️ Vista Previa
                                </button>
                                <button @click.stop="useTemplate(template)"
                                        class="flex-1 px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                                    ✓ Usar Template
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Preview Panel (when previewing) -->
                <div x-show="previewMode && renderedPreview"
                     class="mt-6 border-t pt-6"
                     x-transition>
                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">
                                Vista Previa con Datos Reales
                            </h4>
                            <button @click="previewMode = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Rendered Subject -->
                        <div class="mb-4">
                            <p class="text-xs font-medium text-gray-600 mb-1">ASUNTO:</p>
                            <p class="text-base font-semibold text-gray-900" x-text="renderedPreview.subject"></p>
                        </div>

                        <!-- Rendered Body -->
                        <div>
                            <p class="text-xs font-medium text-gray-600 mb-1">MENSAJE:</p>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-800 whitespace-pre-wrap" x-text="renderedPreview.body"></p>
                            </div>
                        </div>

                        <!-- Use Button -->
                        <div class="mt-4 flex justify-end">
                            <button @click="useTemplate(selectedTemplate)"
                                    class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                                ✓ Usar este Template
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                <p class="text-sm text-gray-500">
                    <span x-text="templates.length"></span> template(s) disponible(s)
                </p>
                <button @click="closeSelector()"
                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
