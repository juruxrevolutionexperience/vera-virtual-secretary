<div class="flex flex-col h-full" x-data="emailPreview" @load-email.window="loadEmail($event.detail.emailId)">

    <!-- Estado inicial: Sin email seleccionado -->
    <div x-show="!selectedEmailId && !loading" class="flex-1 flex flex-col items-center justify-center text-center px-8">
        <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Selecciona un email</h3>
        <p class="text-sm text-gray-500 max-w-sm">
            Haz clic en cualquier email de la lista para ver su contenido completo aquí.
        </p>
        <p class="text-xs text-gray-400 mt-4">
            💡 <strong>Tip:</strong> También puedes usar las teclas ↑ ↓ para navegar
        </p>
    </div>

    <!-- Loading skeleton -->
    <div x-show="loading" class="flex-1 flex flex-col p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div class="h-3 bg-gray-200 rounded w-1/2 mb-6"></div>
        <div class="space-y-3">
            <div class="h-3 bg-gray-200 rounded"></div>
            <div class="h-3 bg-gray-200 rounded"></div>
            <div class="h-3 bg-gray-200 rounded w-5/6"></div>
        </div>
    </div>

    <!-- Email content -->
    <div x-show="selectedEmailId && !loading" class="flex-1 flex flex-col overflow-hidden">

        <!-- Header con acciones -->
        <div class="border-b border-gray-200 p-4 bg-white sticky top-0 z-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 flex-1 mr-4" x-text="email.subject || '(Sin asunto)'"></h2>

                <!-- Botones de acción -->
                <div class="flex items-center gap-2">
                    <!-- Botón Expandir / Abrir en página completa -->
                    <a :href="`/emails/${email.id}`"
                        class="p-2 hover:bg-purple-50 rounded-lg transition-colors group"
                        title="Abrir en página completa (también puedes hacer doble click)">
                        <svg class="w-5 h-5 text-purple-600 group-hover:text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <!-- Separador -->
                    <div class="h-6 w-px bg-gray-300"></div>

                    <button @click="toggleStar()"
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Destacar">
                        <svg class="w-5 h-5" :class="email.is_starred ? 'text-yellow-500 fill-yellow-500' : 'text-gray-400'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </button>

                    <button @click="archiveEmail()"
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Archivar">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </button>

                    <button @click="deleteEmail()"
                        class="p-2 hover:bg-red-50 rounded-lg transition-colors"
                        title="Eliminar">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Info del remitente -->
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-semibold text-sm" x-text="email.from_name ? email.from_name.charAt(0).toUpperCase() : '?'"></span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900" x-text="email.from_name || email.from_email"></p>
                        <p class="text-sm text-gray-500" x-text="'<' + email.from_email + '>'"></p>
                        <p class="text-xs text-gray-400 mt-1" x-text="formatDate(email.received_at)"></p>
                    </div>
                </div>

                <!-- Categoría badge -->
                <div x-show="email.category">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :style="'background-color: ' + (email.category?.color || '#6b7280') + '20; color: ' + (email.category?.color || '#6b7280')">
                        <span x-text="email.category?.icon"></span>
                        <span class="ml-1" x-text="email.category?.name"></span>
                    </span>
                </div>
            </div>

            <!-- Adjuntos preview -->
            <div x-show="email.has_attachments && email.attachments && email.attachments.length > 0"
                class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-sm font-medium text-gray-700 mb-2">
                    📎 Adjuntos (<span x-text="email.attachments_count"></span>)
                </p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="attachment in email.attachments" :key="attachment.id">
                        <a :href="'/emails/' + email.id + '/attachments/' + attachment.id"
                            class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm border border-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span class="font-medium text-gray-700" x-text="attachment.filename"></span>
                            <span class="text-gray-400 text-xs" x-text="formatFileSize(attachment.size)"></span>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <!-- Cuerpo del email -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <!-- Resumen de Vera (si existe) -->
                <div x-show="email.vera_processed && email.vera_summary"
                    class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-purple-900 mb-1">✨ Resumen de Vera</p>
                            <p class="text-sm text-purple-800" x-text="email.vera_summary"></p>
                        </div>
                    </div>
                </div>

                <!-- Contenido HTML o texto -->
                <div x-html="email.body_html || email.body_text?.replace(/\n/g, '<br>')"
                    class="prose prose-sm max-w-none text-gray-800">
                </div>
            </div>
        </div>

    </div>

</div>
