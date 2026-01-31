<!-- Modal Schedule Email -->
<div x-data="scheduleEmailModal()"
     x-show="isOpen"
     x-cloak
     @open-schedule-modal.window="openModal($event.detail)"
     class="fixed inset-0 z-[60] overflow-y-auto"
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
         @click="closeModal()">
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
             class="relative bg-white rounded-xl shadow-2xl w-full max-w-md"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Programar Envío
                </h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 space-y-4">

                <!-- Fecha -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha
                    </label>
                    <input type="date"
                           x-model="scheduledDate"
                           :min="minDate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                </div>

                <!-- Hora -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Hora
                    </label>
                    <input type="time"
                           x-model="scheduledTime"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                </div>

                <!-- Quick Options -->
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-2">Opciones rápidas:</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="setSchedule('1hour')"
                                class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                            En 1 hora
                        </button>
                        <button @click="setSchedule('3hours')"
                                class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                            En 3 horas
                        </button>
                        <button @click="setSchedule('tomorrow9am')"
                                class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                            Mañana 9:00 AM
                        </button>
                        <button @click="setSchedule('nextMonday9am')"
                                class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                            Lunes 9:00 AM
                        </button>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-blue-50 rounded-lg p-3" x-show="scheduledDate && scheduledTime">
                    <p class="text-sm text-blue-900">
                        <span class="font-medium">El email se enviará:</span><br>
                        <span x-text="getFormattedSchedule()"></span>
                    </p>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                <button @click="closeModal()"
                        :disabled="loading"
                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                    Cancelar
                </button>
                <button @click="confirmSchedule()"
                        :disabled="loading || !scheduledDate || !scheduledTime"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!loading">Programar Envío</span>
                    <span x-show="loading">Programando...</span>
                </button>
            </div>

        </div>
    </div>
</div>
