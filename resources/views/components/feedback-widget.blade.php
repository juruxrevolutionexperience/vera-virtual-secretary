{{-- Widget flotante "Reportar a Vera" — botón + modal con paste de imágenes --}}
@auth
<div x-data="feedbackWidget()" x-cloak>
    {{-- Botón flotante --}}
    <button
        type="button"
        @click="open = true"
        class="fixed bottom-5 right-5 z-40 flex items-center gap-2 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium pl-3 pr-4 py-2.5 shadow-lg ring-1 ring-black/10 transition"
        title="Reportar un problema o sugerencia"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
        </svg>
        Reportar
    </button>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition.opacity
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
        @click.self="open = false"
    >
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Reportar a Vera</h3>
                        <p class="text-sm text-gray-500">Cuéntanos qué viste o qué mejorarías. Puedes pegar capturas con Ctrl+V.</p>
                    </div>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select x-model="category" required class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">— Selecciona —</option>
                        <option value="respuesta_mala">Respuesta mala</option>
                        <option value="respuesta_buena">Respuesta buena</option>
                        <option value="bug_web">Bug del panel web</option>
                        <option value="bug_calendar">Bug de calendario</option>
                        <option value="sugerencia">Sugerencia</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje</label>
                    <textarea
                        x-model="message"
                        required
                        rows="4"
                        maxlength="5000"
                        @paste="handlePaste($event)"
                        placeholder="Describe lo que viste. Pega imágenes aquí con Ctrl+V."
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imágenes <span class="text-gray-400 font-normal">(opcional, máx 5)</span></label>
                    <div
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleDrop($event)"
                        :class="dragOver ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300'"
                        class="border-2 border-dashed rounded-lg p-4 text-center transition cursor-pointer"
                        @click="$refs.fileInput.click()"
                    >
                        <p class="text-sm text-gray-500">Arrastra imágenes, pulsa para elegir, o pega con Ctrl+V en el mensaje.</p>
                        <input
                            type="file"
                            x-ref="fileInput"
                            class="hidden"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            multiple
                            @change="handleFiles($event.target.files)"
                        >
                    </div>

                    <template x-if="attachments.length > 0">
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <template x-for="(att, idx) in attachments" :key="idx">
                                <div class="relative group">
                                    <img :src="att.preview" class="w-full h-20 object-cover rounded border border-gray-200">
                                    <button type="button" @click="removeAttachment(idx)" class="absolute top-1 right-1 bg-black/60 hover:bg-black/80 text-white rounded-full p-0.5">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <template x-if="error">
                    <div class="text-sm text-red-600 bg-red-50 rounded p-3" x-text="error"></div>
                </template>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">Cancelar</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-lg">
                        <span x-show="!submitting">Enviar</span>
                        <span x-show="submitting">Enviando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast confirmación --}}
    <div
        x-show="showToast"
        x-transition
        class="fixed bottom-20 right-5 z-50 bg-green-600 text-white text-sm px-4 py-2 rounded-lg shadow-lg"
    >
        Gracias, tu reporte se envió correctamente.
    </div>
</div>

<script>
function feedbackWidget() {
    return {
        open: false,
        category: '',
        message: '',
        attachments: [], // { file, preview }
        dragOver: false,
        submitting: false,
        showToast: false,
        error: '',
        maxFiles: 5,
        maxSize: 5 * 1024 * 1024, // 5 MB

        addFile(file) {
            if (this.attachments.length >= this.maxFiles) {
                this.error = 'Máximo ' + this.maxFiles + ' imágenes.';
                return;
            }
            if (!file.type.startsWith('image/')) {
                this.error = 'Solo se aceptan imágenes.';
                return;
            }
            if (file.size > this.maxSize) {
                this.error = 'Imagen demasiado grande (máx 5MB).';
                return;
            }
            this.error = '';
            const reader = new FileReader();
            reader.onload = (e) => {
                this.attachments.push({ file, preview: e.target.result });
            };
            reader.readAsDataURL(file);
        },

        handleFiles(files) {
            Array.from(files).forEach(f => this.addFile(f));
        },

        handleDrop(e) {
            this.dragOver = false;
            if (e.dataTransfer.files.length) {
                this.handleFiles(e.dataTransfer.files);
            }
        },

        handlePaste(e) {
            const items = e.clipboardData?.items || [];
            for (const item of items) {
                if (item.type.startsWith('image/')) {
                    e.preventDefault();
                    const blob = item.getAsFile();
                    if (blob) this.addFile(blob);
                }
            }
        },

        removeAttachment(idx) {
            this.attachments.splice(idx, 1);
        },

        async submit() {
            if (!this.category || !this.message.trim()) return;
            this.submitting = true;
            this.error = '';

            const fd = new FormData();
            fd.append('category', this.category);
            fd.append('message', this.message);
            fd.append('context_url', window.location.href);

            const convoMatch = window.location.pathname.match(/conversation\/(\d+)/);
            if (convoMatch) fd.append('conversation_id', convoMatch[1]);

            this.attachments.forEach((att, i) => {
                fd.append('attachments[]', att.file);
            });

            try {
                const res = await fetch('{{ route('feedback.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || 'Error al enviar el reporte.');
                }

                this.open = false;
                this.category = '';
                this.message = '';
                this.attachments = [];
                this.showToast = true;
                setTimeout(() => { this.showToast = false; }, 3000);
            } catch (err) {
                this.error = err.message;
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endauth
