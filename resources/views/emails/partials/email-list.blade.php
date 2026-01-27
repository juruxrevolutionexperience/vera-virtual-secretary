<div class="flex flex-col h-full">
    <!-- List Header with Select All -->
    <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-3 bg-gray-50">
        <input type="checkbox" @click="selectAllEmails()"
            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer">
        <span class="text-xs text-gray-500 font-medium">Seleccionar todos</span>

        <!-- Bulk Actions Bar (aparece cuando hay selección) -->
        <div x-show="selectedEmails.length > 0" x-transition class="flex-1 flex items-center justify-end gap-2">
            <span class="text-xs text-gray-600 font-medium" x-text="`${selectedEmails.length} seleccionado(s)`"></span>

            <!-- Establecer Due Date masivo -->
            <div class="relative" x-data="{ bulkDueDateOpen: false }">
                <button @click.stop="bulkDueDateOpen = !bulkDueDateOpen"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-md transition-colors gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Fecha límite
                </button>

                <!-- Dropdown -->
                <div x-show="bulkDueDateOpen" @click.away="bulkDueDateOpen = false"
                    x-transition
                    class="absolute right-0 top-full mt-2 w-56 bg-white rounded-lg shadow-2xl border border-gray-200 z-50">
                    <div class="py-1">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 border-b">Establecer fecha límite</div>
                        <button @click="bulkSetDueDate('today'); bulkDueDateOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2">
                            <span>📅</span> Hoy
                        </button>
                        <button @click="bulkSetDueDate('tomorrow'); bulkDueDateOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2">
                            <span>📅</span> Mañana
                        </button>
                        <button @click="bulkSetDueDate('3days'); bulkDueDateOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2">
                            <span>📅</span> En 3 días
                        </button>
                        <button @click="bulkSetDueDate('nextweek'); bulkDueDateOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2">
                            <span>📅</span> Próxima semana
                        </button>
                    </div>
                </div>
            </div>

            <!-- Snooze masivo -->
            <div class="relative" x-data="{ bulkSnoozeOpen: false }">
                <button @click.stop="bulkSnoozeOpen = !bulkSnoozeOpen"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-md transition-colors gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Posponer
                </button>

                <!-- Dropdown -->
                <div x-show="bulkSnoozeOpen" @click.away="bulkSnoozeOpen = false"
                    x-transition
                    class="absolute right-0 top-full mt-2 w-56 bg-white rounded-lg shadow-2xl border border-gray-200 z-50">
                    <div class="py-1">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 border-b">Posponer emails</div>
                        <button @click="bulkSnooze('1hour'); bulkSnoozeOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2">
                            <span>⏰</span> 1 hora
                        </button>
                        <button @click="bulkSnooze('3hours'); bulkSnoozeOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2">
                            <span>⏰</span> 3 horas
                        </button>
                        <button @click="bulkSnooze('tomorrow9am'); bulkSnoozeOpen = false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2">
                            <span>⏰</span> Mañana 9:00 AM
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Filtros por Estado - Inline con Scroll Horizontal -->
    <div class="px-4 py-3 border-b border-gray-200 bg-white">
        <div class="flex items-center gap-2 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent pb-1"
            style="scrollbar-width: thin;">

            <!-- Botón: Todos -->
            <button @click="filterByStatus('')" :class="currentStatusFilter === '' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white text-gray-600           
  border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">📋</span>
                <span>Todos</span>
                <span class="ml-1.5 bg-gray-100 px-1.5 rounded-full text-[10px]" x-text="getTotalEmails()"></span>
            </button>

            <!-- Botón: Sin leer -->
            <button @click="filterByStatus('unread')" :class="currentStatusFilter === 'unread' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white text-gray-600     
  border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">📬</span>
                <span>Sin leer</span>
                <span class="ml-1.5 bg-blue-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('unread')"></span>
            </button>

            <!-- Botón: Pendiente validación -->
            <button @click="filterByStatus('pending_validation')" :class="currentStatusFilter === 'pending_validation' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white       
  text-gray-600 border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">⏳</span>
                <span>Pendientes</span>
                <span class="ml-1.5 bg-yellow-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('pending_validation')"></span>
            </button>

            <!-- Botón: Requiere respuesta -->
            <button @click="filterByStatus('pending_response')" :class="currentStatusFilter === 'pending_response' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white         
  text-gray-600 border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">✉️</span>
                <span>Responder</span>
                <span class="ml-1.5 bg-orange-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('pending_response')"></span>
            </button>

            <!-- Botón: En curso -->
            <button @click="filterByStatus('in_progress')" :class="currentStatusFilter === 'in_progress' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white text-gray-600
   border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">🔄</span>
                <span>En curso</span>
                <span class="ml-1.5 bg-purple-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('in_progress')"></span>
            </button>

            <!-- Botón: En revisión -->
            <button @click="filterByStatus('in_review')" :class="currentStatusFilter === 'in_review' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white text-gray-600  
  border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">👁️</span>
                <span>Revisión</span>
                <span class="ml-1.5 bg-indigo-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('in_review')"></span>
            </button>

            <!-- Botón: Completado -->
            <button @click="filterByStatus('completed')" :class="currentStatusFilter === 'completed' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white text-gray-600  
  border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">✔️</span>
                <span>Completado</span>
                <span class="ml-1.5 bg-emerald-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('completed')"></span>
            </button>

            <!-- Botón: Sin acción necesaria -->
            <button @click="filterByStatus('no_action_needed')" :class="currentStatusFilter === 'no_action_needed' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-white         
  text-gray-600 border-gray-300 hover:bg-gray-50'" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-all whitespace-nowrap       
  flex-shrink-0">
                <span class="mr-1.5">➖</span>
                <span>Sin acción</span>
                <span class="ml-1.5 bg-slate-100 px-1.5 rounded-full text-[10px]"
                    x-text="getStatusCount('no_action_needed')"></span>
            </button>

        </div>
    </div>


    <!-- Email List -->
    <div id="emails-container" class="flex-1 overflow-y-auto"
        x-data="{ closeAllDropdowns: () => { window.dispatchEvent(new Event('close-dropdowns')); } }"
        @scroll="closeAllDropdowns()">
        @forelse($emails as $email)
                    <div id="email-{{ $email->id }}" class="email-item group border-b border-gray-100 cursor-pointer relative overflow-hidden
                                                          density-comfortable
                                                                    {{ !$email->is_read ? 'unread' : '' }}
                                                                    {{ $email->priority === 'high' ? 'priority-high' : '' }}
                                                                    {{ $email->priority === 'urgent' ? 'priority-urgent' : '' }}"
                        style="max-width: 100%;" x-data="{ workflowOpen: false, folderMenuOpen: false }"
                        @close-dropdowns.window="workflowOpen = false; folderMenuOpen = false"
                        onclick="window.location.href='{{ route('emails.show', $email) }}'" data-email-id="{{ $email->id }}"
                        data-email-url="{{ route('emails.show', $email) }}">

                        <div class="flex items-start gap-3">
                            <!-- Checkbox -->
                            <input type="checkbox" value="{{ $email->id }}"
                                class="email-checkbox mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
                                @click.stop="toggleEmailSelection({{ $email->id }})"
                                :checked="selectedEmails.includes({{ $email->id }})">

                            <!-- Email Content -->
                            <div class="flex-1 min-w-0">
                                <!-- From & Time -->
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span
                                            class="text-sm {{ !$email->is_read ? 'font-semibold text-gray-900' : 'font-medium text-gray-700' }} truncate">
                                            {{ $email->from_name ?? $email->from_email }}
                                        </span>

                                        @php
                                            $categoryColors = [
                                                'primary' => '#6366f1',
                                                'promotions' => '#f59e0b',
                                                'social' => '#10b981',
                                                'updates' => '#3b82f6',
                                                'forums' => '#8b5cf6',
                                                'spam' => '#ef4444',
                                                'trash' => '#6b7280',
                                            ];
                                            $categoryIcons = [
                                                'primary' => '📧',
                                                'promotions' => '🏷️',
                                                'social' => '👥',
                                                'updates' => '🔔',
                                                'forums' => '💬',
                                                'spam' => '⚠️',
                                                'trash' => '🗑️',
                                            ];
                                            $categoryColor = $categoryColors[$email->category] ?? '#6b7280';
                                            $categoryIcon = $categoryIcons[$email->category] ?? '📧';
                                        @endphp

                                        <!-- Category Badge -->
                                        @if($email->category)
                                            <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                                                style="background-color: {{ $categoryColor }}20; color: {{ $categoryColor }}">
                                                {{ $categoryIcon }} {{ ucfirst($email->category) }}
                                            </span>
                                        @endif

                                        <!-- Vera Response Badge -->
                                        @php
                                            $hasVeraResponse = $email->veraActions()
                                                ->where('action_type', 'replied')
                                                ->where('user_feedback', 'pending')
                                                ->exists();
                                        @endphp
                                        @if($hasVeraResponse)
                                            <a href="{{ route('vera.responses') }}"
                                                class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-purple-100 text-purple-700 hover:bg-purple-200 transition"
                                                title="Vera generó una respuesta para este email" onclick="event.stopPropagation()">
                                                🤖 Vera Respondió
                                            </a>
                                        @endif
                                    </div>

                                    <span class="text-xs text-gray-500 ml-2 flex-shrink-0">
                                        {{ formatEmailDate($email->received_at) }}
                                    </span>
                                </div>

                                <!-- Subject -->
                                <div class="flex items-center gap-2 mb-1">
                                    <p
                                        class="text-sm {{ !$email->is_read ? 'font-medium text-gray-900' : 'text-gray-600' }} truncate flex-1">
                                        {{ $email->subject }}
                                    </p>

                                    <!-- Workflow Status Badge -->
                                    @php
                                        $workflowColors = [
                                            'unread' => 'bg-blue-100 text-blue-700',
                                            'pending_validation' => 'bg-yellow-100 text-yellow-700',
                                            'pending_response' => 'bg-orange-100 text-orange-700',
                                            'in_progress' => 'bg-purple-100 text-purple-700',
                                            'in_review' => 'bg-indigo-100 text-indigo-700',
                                            'approved' => 'bg-green-100 text-green-700',
                                            'completed' => 'bg-emerald-100 text-emerald-700',
                                            'archived' => 'bg-gray-100 text-gray-600',
                                            'no_action_needed' => 'bg-slate-100 text-slate-600',
                                        ];
                                        $workflowLabels = [
                                            'unread' => '📬 Sin leer',
                                            'pending_validation' => '⏳ Pendiente',
                                            'pending_response' => '✉️ Responder',
                                            'in_progress' => '🔄 En curso',
                                            'in_review' => '👁️ Revisión',
                                            'approved' => '✅ Aprobado',
                                            'completed' => '✔️ Completado',
                                            'archived' => '📦 Archivado',
                                            'no_action_needed' => '➖ Sin acción',
                                        ];
                                    @endphp

                                    <div class="relative mr-20" x-data="{ dropdownStyle: {} }" x-ref="workflowContainer">
                                        <button
                                            @click.stop="workflowOpen = !workflowOpen; if(workflowOpen) { const rect = $el.getBoundingClientRect(); dropdownStyle = { top: rect.bottom + 4 + 'px', left: rect.left + 'px' }; }"
                                            x-ref="workflowButton"
                                            class="inline-flex items-center text-xs px-3 py-1.5 rounded-full font-medium {{ $workflowColors[$email->workflow_status] ?? 'bg-gray-100 text-gray-600' }} hover:opacity-80 transition-opacity">
                                            {{ $workflowLabels[$email->workflow_status] ?? ucfirst($email->workflow_status) }}
                                            <svg class="w-2.5 h-2.5 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown de workflow usando TELEPORT al portal -->
                                        <template x-teleport="#dropdowns-portal">
                                            <div x-show="workflowOpen" @click.away="workflowOpen = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                class="fixed w-48 bg-white rounded-lg shadow-2xl border border-gray-200"
                                                :style="{ top: dropdownStyle.top, left: dropdownStyle.left, zIndex: 99999 }"
                                                x-cloak>
                                                <div class="py-1">
                                                    <button
                                                        @click.stop="changeWorkflowStatus({{ $email->id }}, 'pending_validation'); workflowOpen = false"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                                        ⏳ Pendiente validación
                                                    </button>
                                                    <button
                                                        @click.stop="changeWorkflowStatus({{ $email->id }}, 'pending_response'); workflowOpen = false"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                                        ✉️ Requiere respuesta
                                                    </button>
                                                    <button
                                                        @click.stop="changeWorkflowStatus({{ $email->id }}, 'in_progress'); workflowOpen = false"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                                        🔄 En curso
                                                    </button>
                                                    <button
                                                        @click.stop="changeWorkflowStatus({{ $email->id }}, 'in_review'); workflowOpen = false"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                        👁️ En revisión
                                                    </button>
                                                    <button
                                                        @click.stop="changeWorkflowStatus({{ $email->id }}, 'completed'); workflowOpen = false"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                        ✔️ Completado
                                                    </button>
                                                    <div class="border-t border-gray-200 my-1"></div>
                                                    <button
                                                        @click.stop="changeWorkflowStatus({{ $email->id }}, 'no_action_needed'); workflowOpen = false"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                                        ➖ Sin acción necesaria
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Quick Actions -->
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity mr-20">
                                        <!-- Due Date -->
                                        <div class="relative" x-data="{ dueDateOpen: false, dueDateDropdownStyle: {} }" x-ref="dueDateContainer">
                                            <button @click.stop="dueDateOpen = !dueDateOpen; if(dueDateOpen) { const rect = $el.getBoundingClientRect(); dueDateDropdownStyle = { top: rect.bottom + 4 + 'px', right: (window.innerWidth - rect.right) + 'px' }; }" x-ref="dueDateButton"
                                                class="p-1 hover:bg-gray-200 rounded transition-colors" title="Establecer fecha límite">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </button>

                                            <!-- Dropdown de Due Date usando TELEPORT -->
                                            <template x-teleport="#dropdowns-portal">
                                                <div x-show="dueDateOpen" @click.away="dueDateOpen = false"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    class="fixed w-56 bg-white rounded-lg shadow-2xl border border-gray-200 due-date-dropdown"
                                                    data-email-id="{{ $email->id }}"
                                                    :style="{ top: dueDateDropdownStyle.top, right: dueDateDropdownStyle.right, zIndex: 99999 }"
                                                    x-cloak>
                                                    <div class="py-1">
                                                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 border-b">Establecer fecha límite</div>

                                                        <!-- Opciones rápidas -->
                                                        <button @click.stop="setDueDate({{ $email->id }}, 'today'); dueDateOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-purple-600">📅</span>
                                                            <span class="font-medium">Hoy</span>
                                                        </button>
                                                        <button @click.stop="setDueDate({{ $email->id }}, 'tomorrow'); dueDateOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-blue-600">📅</span>
                                                            <span class="font-medium">Mañana</span>
                                                        </button>
                                                        <button @click.stop="setDueDate({{ $email->id }}, '3days'); dueDateOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-green-600">📅</span>
                                                            <span class="font-medium">En 3 días</span>
                                                        </button>
                                                        <button @click.stop="setDueDate({{ $email->id }}, 'nextweek'); dueDateOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-orange-600">📅</span>
                                                            <span class="font-medium">Próxima semana</span>
                                                        </button>

                                                        <div class="border-t border-gray-200 my-1 due-date-separator"></div>

                                                        <!-- Selector personalizado -->
                                                        <div class="px-3 py-2">
                                                            <label class="text-xs text-gray-500 font-medium block mb-1">Fecha personalizada</label>
                                                            <input type="date"
                                                                @change="setDueDateCustom({{ $email->id }}, $event.target.value); dueDateOpen = false"
                                                                :min="new Date().toISOString().split('T')[0]"
                                                                class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                                                        </div>

                                                        @if($email->due_date)
                                                        <div class="remove-due-date-btn">
                                                            <div class="border-t border-gray-200 my-1"></div>
                                                            <button @click.stop="removeDueDate({{ $email->id }}); dueDateOpen = false"
                                                                class="w-full text-left px-3 py-2 text-xs hover:bg-red-50 flex items-center gap-2 text-red-600 transition-colors">
                                                                <span>❌</span>
                                                                <span class="font-medium">Quitar fecha límite</span>
                                                            </button>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Mover a carpeta -->
                                        <div class="relative" x-data="{ folderDropdownStyle: {} }" x-ref="folderContainer">
                                            <button @click.stop="folderMenuOpen = !folderMenuOpen; if(folderMenuOpen) { const rect = $el.getBoundingClientRect(); folderDropdownStyle =
               { top: rect.bottom + 4 + 'px', right: (window.innerWidth - rect.right) + 'px' }; }" x-ref="folderButton"
                                                class="p-1 hover:bg-gray-200 rounded transition-colors" title="Mover a carpeta">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            </button>

                                            <!-- Dropdown de carpetas usando TELEPORT al portal -->
                                            <template x-teleport="#dropdowns-portal">
                                                <div x-show="folderMenuOpen" @click.away="folderMenuOpen = false" x-transition
                                                    class="fixed w-64 bg-white rounded-lg shadow-2xl border border-gray-200 max-h-72 overflow-y-auto"
                                                    :style="{ top: folderDropdownStyle.top, right: folderDropdownStyle.right, zIndex: 99999 }"
                                                    x-cloak>
                                                    <div class="py-1" x-data="{                                                                                                    
                              folders: [],                                                                                                               
                              flatFolders: [],                                                                                                           

                              // Mapeo de nombres de iconos a emojis                                                                                     
                              iconMap: {                                                                                                                 
                                  'folder': '📁',                                                                                                        
                                  'star': '⭐',                                                                                                          
                                  'briefcase': '💼',                                                                                                     
                                  'user': '👤',                                                                                                          
                                  'archive': '🗄️',                                                                                                       
                                  'inbox': '📥',                                                                                                         
                                  'sent': '📤',                                                                                                          
                                  'trash': '🗑️',                                                                                                         
                                  'flag': '🚩',                                                                                                          
                                  'check': '✅',                                                                                                         
                                  'heart': '❤️',                                                                                                         
                                  'bookmark': '🔖',                                                                                                      
                                  'calendar': '📅',                                                                                                      
                                  'document': '📄',                                                                                                      
                                  'file': '📋',                                                                                                          
                                  'chart': '📊',                                                                                                         
                                  'gift': '🎁',                                                                                                          
                                  'home': '🏠',                                                                                                          
                                  'lock': '🔒',                                                                                                          
                                  'bell': '🔔'                                                                                                           
                              },                                                                                                                         

                              // Convertir nombre de icono a emoji                                                                                       
                              getIcon(iconName) {                                                                                                        
                                  // Si ya es un emoji (más de 1 byte), devolverlo tal cual                                                              
                                  if (iconName && iconName.length > 1 && /\p{Emoji}/u.test(iconName)) {                                                  
                                      return iconName;                                                                                                   
                                  }                                                                                                                      
                                  // Si es un nombre de icono, buscar en el mapa                                                                         
                                  return this.iconMap[iconName] || '📁';                                                                                 
                              },                                                                                                                         

                              // Función recursiva para aplanar el árbol de carpetas                                                                     
                              flattenFolders(folders, level = 0) {                                                                                       
                                  let result = [];                                                                                                       
                                  folders.forEach(folder => {                                                                                            
                                      // Agregar carpeta actual con su nivel de indentación                                                              
                                      result.push({                                                                                                      
                                          id: folder.id,                                                                                                 
                                          name: folder.name,                                                                                             
                                          icon: this.getIcon(folder.icon),                                                                               
                                          color: folder.color || '#6b7280',                                                                              
                                          level: level,                                                                                                  
                                          // Indentación más visual                                                                                      
                                          indentStyle: level > 0 ? `padding-left: ${level * 20}px;` : '',                                                
                                          showLine: level > 0                                                                                            
                                      });                                                                                                                

                                      // Si tiene subcarpetas, procesarlas recursivamente                                                                
                                      if (folder.children && folder.children.length > 0) {                                                               
                                          result = result.concat(this.flattenFolders(folder.children, level + 1));                                       
                                      }                                                                                                                  
                                  });                                                                                                                    
                                  return result;                                                                                                         
                              }                                                                                                                          
                          }" x-init="                                                                                                                    
                              fetch('/email-folders/tree')                                                                                               
                                  .then(r => r.json())                                                                                                   
                                  .then(data => {                                                                                                        
                                      folders = data.folders || [];                                                                                      
                                      flatFolders = flattenFolders(folders);                                                                             
                                  })                                                                                                                     
                          ">
                                                        <template x-for="folder in flatFolders" :key="folder.id">
                                                            <button
                                                                @click.stop="moveEmailToFolder({{ $email->id }}, folder.id); folderMenuOpen = false"
                                                                class="w-full text-left px-3 py-2 text-sm hover:bg-purple-50 flex items-center gap-2 transition-colors border-l-2  
              border-transparent hover:border-purple-400" :style="folder.indentStyle">
                                                                <!-- Línea de jerarquía para subcarpetas -->
                                                                <template x-if="folder.showLine">
                                                                    <span class="text-gray-300 text-xs font-mono">└─</span>
                                                                </template>
                                                                <!-- Icono emoji -->
                                                                <span x-text="folder.icon" class="text-base flex-shrink-0"></span>
                                                                <!-- Nombre de la carpeta -->
                                                                <span x-text="folder.name"
                                                                    class="truncate font-medium text-gray-700"></span>
                                                            </button>
                                                        </template>
                                                        <template x-if="flatFolders.length === 0">
                                                            <p class="px-3 py-2 text-xs text-gray-400 text-center">No hay carpetas
                                                                disponibles</p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Snooze -->
                                        <div class="relative" x-data="{ snoozeOpen: false, snoozeDropdownStyle: {} }" x-ref="snoozeContainer">
                                            <button @click.stop="snoozeOpen = !snoozeOpen; if(snoozeOpen) { const rect = $el.getBoundingClientRect(); snoozeDropdownStyle = { top: rect.bottom + 4 + 'px', right: (window.innerWidth - rect.right) + 'px' }; }" x-ref="snoozeButton"
                                                class="p-1 hover:bg-gray-200 rounded transition-colors" title="Posponer">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>

                                            <!-- Dropdown de Snooze usando TELEPORT -->
                                            <template x-teleport="#dropdowns-portal">
                                                <div x-show="snoozeOpen" @click.away="snoozeOpen = false"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    class="fixed w-56 bg-white rounded-lg shadow-2xl border border-gray-200 snooze-dropdown"
                                                    data-email-id="{{ $email->id }}"
                                                    :style="{ top: snoozeDropdownStyle.top, right: snoozeDropdownStyle.right, zIndex: 99999 }"
                                                    x-cloak>
                                                    <div class="py-1">
                                                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 border-b">Posponer email</div>

                                                        <!-- Opciones rápidas -->
                                                        <button @click.stop="snoozeEmail({{ $email->id }}, '1hour'); snoozeOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-blue-600">⏰</span>
                                                            <span class="font-medium">1 hora</span>
                                                        </button>
                                                        <button @click.stop="snoozeEmail({{ $email->id }}, '3hours'); snoozeOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-blue-600">⏰</span>
                                                            <span class="font-medium">3 horas</span>
                                                        </button>
                                                        <button @click.stop="snoozeEmail({{ $email->id }}, 'tomorrow9am'); snoozeOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-indigo-600">⏰</span>
                                                            <span class="font-medium">Mañana 9:00 AM</span>
                                                        </button>
                                                        <button @click.stop="snoozeEmail({{ $email->id }}, '3days'); snoozeOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-purple-600">⏰</span>
                                                            <span class="font-medium">En 3 días</span>
                                                        </button>
                                                        <button @click.stop="snoozeEmail({{ $email->id }}, '1week'); snoozeOpen = false"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 flex items-center gap-2 transition-colors">
                                                            <span class="text-orange-600">⏰</span>
                                                            <span class="font-medium">En 1 semana</span>
                                                        </button>

                                                        <div class="border-t border-gray-200 my-1 snooze-separator"></div>

                                                        <!-- Selector personalizado -->
                                                        <div class="px-3 py-2">
                                                            <label class="text-xs text-gray-500 font-medium block mb-1">Fecha y hora personalizada</label>
                                                            <input type="datetime-local"
                                                                @change="snoozeEmailCustom({{ $email->id }}, $event.target.value); snoozeOpen = false"
                                                                :min="new Date().toISOString().slice(0, 16)"
                                                                class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                                        </div>

                                                        @if($email->isSnoozed())
                                                        <div class="wakeup-btn">
                                                            <div class="border-t border-gray-200 my-1"></div>
                                                            <button @click.stop="wakeupEmail({{ $email->id }}); snoozeOpen = false"
                                                                class="w-full text-left px-3 py-2 text-xs hover:bg-red-50 flex items-center gap-2 text-red-600 transition-colors">
                                                                <span>❌</span>
                                                                <span class="font-medium">Despertar ahora</span>
                                                            </button>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Estrella -->
                                        <button @click.stop="performAction({{ $email->id }}, 'toggle-star')"
                                            class="p-1 hover:bg-gray-200 rounded transition-colors"
                                            title="{{ $email->is_starred ? 'Quitar estrella' : 'Destacar' }}">
                                            <svg class="w-3.5 h-3.5 {{ $email->is_starred ? 'text-yellow-500 fill-yellow-500' : 'text-gray-500' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Excerpt & Tags -->
                                <div class="flex items-center gap-2">
                                    <p class="text-xs text-gray-500 truncate flex-1">
                                        {{ Str::limit(strip_tags($email->excerpt), 80) }}
                                    </p>
                                </div>

                                <!-- Meta Information -->
                                <div class="flex items-center gap-3 mt-2">
                                    <!-- Attachments -->
                                    @if($email->has_attachments)
                                        <div class="flex items-center gap-1 text-gray-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            <span class="text-[10px]">{{ $email->attachments_count }}</span>
                                        </div>
                                    @endif

                                    <!-- Priority Badge -->
                                    @if(in_array($email->priority, ['high', 'urgent']))
                                        <span
                                            class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                                                                                                    {{ $email->priority === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                            @if($email->priority === 'urgent')
                                                ⚠️ Urgente
                                            @else
                                                🔔 Alta
                                            @endif
                                        </span>
                                    @endif

                                    <!-- Due Date & Snooze Badges Container -->
                                    <span class="email-badges inline-flex items-center gap-2">
                                        <!-- Due Date Badges -->
                                        @if($email->isOverdue())
                                            <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-red-100 text-red-700 animate-pulse" title="Venció: {{ formatDueDateFull($email->due_date) }}">
                                                🚨 Vencido: {{ formatDueDateBadge($email->due_date) }}
                                            </span>
                                        @elseif($email->due_date && $email->due_date > now() && $email->due_date <= now()->addHours(24))
                                            <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-yellow-100 text-yellow-700" title="Vence: {{ formatDueDateFull($email->due_date) }}">
                                                ⏰ Vence: {{ formatDueDateBadge($email->due_date) }}
                                            </span>
                                        @elseif($email->due_date)
                                            <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700" title="Vence: {{ formatDueDateFull($email->due_date) }}">
                                                📅 {{ formatDueDateBadge($email->due_date) }}
                                            </span>
                                        @endif

                                        <!-- Snooze Badge -->
                                        @if($email->isSnoozed())
                                            <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-blue-100 text-blue-700" title="Pospuesto hasta: {{ formatDueDateFull($email->snoozed_until) }}">
                                                💤 Hasta: {{ formatDueDateBadge($email->snoozed_until) }}
                                            </span>
                                        @endif
                                    </span>

                                    <!-- Vera Processed -->
                                    @if($email->vera_processed)
                                        <div class="flex items-center gap-1 text-purple-600" title="Procesado por Vera">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-[10px] font-medium">Vera</span>
                                        </div>
                                    @endif

                                    <!-- Vera Tags -->
                                    @if($email->vera_tags && count($email->vera_tags) > 0)
                                        @foreach(array_slice($email->vera_tags, 0, 2) as $tag)
                                            <span
                                                class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600">
                                                #{{ $tag }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <!-- Starred Indicator (always visible if starred) -->
                            @if($email->is_starred)
                                <button @click.stop="performAction({{ $email->id }}, 'toggle-star')" class="flex-shrink-0 mt-1">
                                    <svg class="w-4 h-4 text-yellow-500 fill-yellow-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
        @empty
            <div class="flex flex-col items-center justify-center h-64 text-center px-4">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No hay emails</h3>
                <p class="text-sm text-gray-500">
                    @if(request()->has('search'))
                        No encontramos emails que coincidan con tu búsqueda.
                    @else
                        Tu bandeja de entrada está vacía.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($emails->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    Mostrando {{ $emails->firstItem() }} - {{ $emails->lastItem() }} de {{ $emails->total() }}
                </div>
                <div class="flex gap-1">
                    @if($emails->onFirstPage())
                        <button disabled class="p-1.5 rounded text-gray-300 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    @else
                        <a href="{{ $emails->previousPageUrl() }}"
                            class="p-1.5 rounded text-gray-600 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    @if($emails->hasMorePages())
                        <a href="{{ $emails->nextPageUrl() }}"
                            class="p-1.5 rounded text-gray-600 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <button disabled class="p-1.5 rounded text-gray-300 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const density = localStorage.getItem('email-density') || 'comfortable';
        document.querySelectorAll('.email-item').forEach(item => {
            item.classList.add(`density-${density}`);
        });
    });
</script>