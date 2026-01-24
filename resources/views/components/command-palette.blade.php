{{-- Command Palette - Acceso rápido universal (Ctrl+K) --}}
<div x-data="commandPalette()"
     x-show="open"
     x-cloak
     @keydown.escape.window="open = false"
     @keydown.meta.k.window.prevent="toggle()"
     @keydown.ctrl.k.window.prevent="toggle()"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    {{-- Overlay --}}
    <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity"
         @click="open = false"></div>

    {{-- Modal --}}
    <div class="flex min-h-full items-start justify-center p-4 pt-[10vh]">
        <div class="relative w-full max-w-2xl transform rounded-xl bg-white shadow-2xl ring-1 ring-black/5 transition-all"
             @click.away="open = false">

            {{-- Input de búsqueda --}}
            <div class="relative">
                <svg class="pointer-events-none absolute left-4 top-4 h-5 w-5 text-gray-400"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>

                <input type="text"
                       x-ref="searchInput"
                       x-model="searchQuery"
                       @input="handleSearch()"
                       @keydown.down.prevent="selectNext()"
                       @keydown.up.prevent="selectPrevious()"
                       @keydown.enter.prevent="executeSelected()"
                       class="w-full border-0 bg-transparent py-4 pl-12 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 text-base"
                       placeholder="Buscar emails, aplicar filtros, navegar..."
                       autocomplete="off">

                <div class="absolute right-4 top-4 flex items-center gap-2">
                    <span x-show="searching" class="text-xs text-gray-400">Buscando...</span>
                    <kbd class="hidden sm:inline-flex items-center rounded border border-gray-200 px-2 py-0.5 text-xs font-sans text-gray-500">
                        ESC
                    </kbd>
                </div>
            </div>

            {{-- Resultados --}}
            <div class="max-h-[60vh] overflow-y-auto border-t border-gray-100 py-2">

                {{-- Sin resultados --}}
                <div x-show="filteredCommands.length === 0 && searchResults.length === 0 && !searching"
                     class="px-4 py-8 text-center text-sm text-gray-500">
                    <span x-show="searchQuery.length > 0">
                        No se encontraron resultados para "<span x-text="searchQuery"></span>"
                    </span>
                    <span x-show="searchQuery.length === 0">
                        Escribe para buscar emails o comandos...
                    </span>
                </div>

                {{-- Resultados de búsqueda de emails --}}
                <div x-show="searchResults.length > 0" class="mb-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center justify-between">
                        <span>📧 Emails encontrados</span>
                        <span x-text="searchResults.length + ' resultado' + (searchResults.length !== 1 ? 's' : '')"></span>
                    </div>

                    <template x-for="email in searchResults" :key="email.id">
                        <a :href="`/emails/${email.id}`"
                           @mouseenter="selectedIndex = -1"
                           class="block px-4 py-3 hover:bg-gray-50 transition-colors border-l-2 border-transparent hover:border-indigo-500">
                            <div class="flex items-start gap-3">
                                <div :class="email.is_read ? 'bg-gray-100' : 'bg-indigo-100'"
                                     class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-medium">
                                    <span x-text="email.from.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-medium text-sm truncate" x-text="email.from"></span>
                                        <span class="text-xs text-gray-400 flex-shrink-0" x-text="email.received_at"></span>
                                    </div>
                                    <div :class="email.is_read ? 'text-gray-600' : 'text-gray-900 font-medium'"
                                         class="text-sm truncate" x-text="email.subject"></div>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- Historial reciente --}}
                <div x-show="recentCommands.length > 0 && searchQuery.length === 0" class="mb-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        ⏱️ Recientes
                    </div>
                    <template x-for="cmd in recentCommands.slice(0, 5)" :key="cmd.id + '-recent'">
                        <button @click="executeCommand(cmd)"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 transition-colors flex items-center gap-3 text-sm">
                            <span x-text="cmd.icon"></span>
                            <span x-text="cmd.label" class="text-gray-600"></span>
                        </button>
                    </template>
                </div>

                {{-- Lista de comandos --}}
                <template x-for="(section, sectionName) in groupedCommands" :key="sectionName">
                    <div x-show="section.length > 0" class="mb-2">
                        {{-- Título de sección --}}
                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide"
                             x-text="sectionName"></div>

                        {{-- Comandos de la sección --}}
                        <template x-for="(command, index) in section" :key="command.id">
                            <button @click="executeCommand(command)"
                                    @mouseenter="selectedIndex = getGlobalIndex(command)"
                                    :class="{ 'bg-indigo-50 text-indigo-700': isSelected(command) }"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors flex items-center gap-3 group">

                                {{-- Icono --}}
                                <span class="text-xl flex-shrink-0" x-text="command.icon"></span>

                                {{-- Texto --}}
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm" x-text="command.label"></div>
                                    <div x-show="command.description"
                                         class="text-xs text-gray-500 truncate"
                                         x-text="command.description"></div>
                                </div>

                                {{-- Contador --}}
                                <span x-show="command.count !== undefined && command.count !== null"
                                      class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium"
                                      x-text="command.count"></span>

                                {{-- Shortcut (opcional) --}}
                                <kbd x-show="command.shortcut"
                                     class="hidden group-hover:inline-flex items-center rounded border border-gray-200 px-2 py-0.5 text-xs font-sans text-gray-500"
                                     x-text="command.shortcut"></kbd>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Acciones contextuales (si estás viendo un email) --}}
                <div x-show="contextActions.length > 0 && searchQuery.length === 0" class="mb-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        🎯 Acciones en este email
                    </div>
                    <template x-for="action in contextActions" :key="action.id">
                        <button @click="executeCommand(action)"
                                class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors flex items-center gap-3">
                            <span class="text-xl" x-text="action.icon"></span>
                            <span class="text-sm font-medium" x-text="action.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Footer con ayuda --}}
            <div class="border-t border-gray-100 px-4 py-3 bg-gray-50/50 flex items-center justify-between text-xs text-gray-500 rounded-b-xl">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 bg-white rounded border border-gray-200">↑</kbd>
                        <kbd class="px-1.5 py-0.5 bg-white rounded border border-gray-200">↓</kbd>
                        Navegar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 bg-white rounded border border-gray-200">↵</kbd>
                        Seleccionar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 bg-white rounded border border-gray-200">ESC</kbd>
                        Cerrar
                    </span>
                </div>
                <div class="hidden sm:block text-gray-400">
                    <kbd class="px-1.5 py-0.5 bg-white rounded border border-gray-200">Ctrl K</kbd> para abrir
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Script de Alpine.js --}}
@push('scripts')
<script>
function commandPalette() {
    return {
        open: false,
        searchQuery: '',
        selectedIndex: 0,
        commands: [],
        filteredCommands: [],
        searchResults: [],
        searching: false,
        searchTimeout: null,
        stats: {},
        recentCommands: [],
        contextActions: [],

        init() {
            // Definir todos los comandos disponibles
            this.commands = this.getCommands();
            this.filteredCommands = this.commands;

            // Cargar historial desde localStorage
            this.loadRecentCommands();

            // Detectar acciones contextuales
            this.detectContextActions();

            // Cargar estadísticas al abrir
            this.$watch('open', async (value) => {
                if (value) {
                    await this.loadStats();
                    this.$nextTick(() => {
                        this.$refs.searchInput.focus();
                        this.searchQuery = '';
                        this.selectedIndex = 0;
                        this.searchResults = [];
                        this.filterCommands();
                    });
                }
            });
        },

        toggle() {
            this.open = !this.open;
        },

        async loadStats() {
            try {
                const response = await fetch('/emails/api/command-palette/stats');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                    this.updateCommandCounts();
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        updateCommandCounts() {
            this.commands = this.commands.map(cmd => {
                const counts = {
                    'filter-today': this.stats.today,
                    'filter-week': this.stats.this_week,
                    'filter-month': this.stats.this_month,
                    'filter-attachments': this.stats.with_attachments,
                    'filter-unread': this.stats.unread,
                    'filter-starred': this.stats.starred,
                    'nav-inbox': this.stats.total,
                    'nav-drafts': this.stats.drafts,
                    'nav-trash': this.stats.trash,
                };

                if (counts[cmd.id] !== undefined) {
                    cmd.count = counts[cmd.id];
                }

                return cmd;
            });
            this.filterCommands();
        },

        handleSearch() {
            clearTimeout(this.searchTimeout);
            this.filterCommands();

            if (this.searchQuery.length >= 2) {
                this.searching = true;
                this.searchTimeout = setTimeout(() => {
                    this.searchEmails();
                }, 300);
            } else {
                this.searchResults = [];
                this.searching = false;
            }
        },

        async searchEmails() {
            try {
                const response = await fetch(`/emails/api/command-palette/search?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                if (data.success) {
                    this.searchResults = data.emails;
                }
            } catch (error) {
                console.error('Error searching emails:', error);
            } finally {
                this.searching = false;
            }
        },

        loadRecentCommands() {
            try {
                const recent = localStorage.getItem('vera_command_palette_recent');
                if (recent) {
                    const recentIds = JSON.parse(recent);
                    this.recentCommands = recentIds
                        .map(id => this.commands.find(cmd => cmd.id === id))
                        .filter(cmd => cmd !== undefined);
                }
            } catch (error) {
                console.error('Error loading recent commands:', error);
            }
        },

        saveRecentCommand(command) {
            try {
                let recent = [];
                const stored = localStorage.getItem('vera_command_palette_recent');
                if (stored) {
                    recent = JSON.parse(stored);
                }

                // Remover si ya existe
                recent = recent.filter(id => id !== command.id);
                // Agregar al inicio
                recent.unshift(command.id);
                // Mantener solo los últimos 10
                recent = recent.slice(0, 10);

                localStorage.setItem('vera_command_palette_recent', JSON.stringify(recent));
                this.loadRecentCommands();
            } catch (error) {
                console.error('Error saving recent command:', error);
            }
        },

        detectContextActions() {
            // Si estamos en una página de email individual
            const match = window.location.pathname.match(/\/emails\/(\d+)$/);
            if (match) {
                const emailId = match[1];
                this.contextActions = [
                    {
                        id: 'ctx-trash',
                        icon: '🗑️',
                        label: 'Mover a papelera',
                        action: () => this.moveToTrash(emailId)
                    },
                    {
                        id: 'ctx-star',
                        icon: '⭐',
                        label: 'Marcar como importante',
                        action: () => this.toggleStar(emailId)
                    },
                    {
                        id: 'ctx-archive',
                        icon: '📦',
                        label: 'Archivar',
                        action: () => this.archiveEmail(emailId)
                    },
                ];
            }
        },

        getCommands() {
            return [
                // BÚSQUEDA DE EMAILS
                {
                    id: 'search-subject',
                    section: 'Buscar Emails',
                    icon: '🔍',
                    label: 'Buscar por asunto',
                    description: 'Buscar emails por su asunto',
                    keywords: ['buscar', 'asunto', 'subject', 'titulo'],
                    action: () => this.promptSearch('subject')
                },
                {
                    id: 'search-from',
                    section: 'Buscar Emails',
                    icon: '👤',
                    label: 'Buscar por remitente',
                    description: 'Buscar emails de una persona específica',
                    keywords: ['buscar', 'remitente', 'from', 'de', 'persona'],
                    action: () => this.promptSearch('from')
                },
                {
                    id: 'search-content',
                    section: 'Buscar Emails',
                    icon: '📄',
                    label: 'Buscar por contenido',
                    description: 'Buscar en el cuerpo del email',
                    keywords: ['buscar', 'contenido', 'body', 'texto'],
                    action: () => this.promptSearch('content')
                },

                // FILTROS DE FECHA
                {
                    id: 'filter-today',
                    section: 'Filtros de Fecha',
                    icon: '📅',
                    label: 'Emails de hoy',
                    description: 'Ver emails recibidos hoy',
                    keywords: ['hoy', 'today', 'fecha', 'reciente'],
                    action: () => this.applyFilterAjax({ date_filter: 'today' })
                },
                {
                    id: 'filter-week',
                    section: 'Filtros de Fecha',
                    icon: '📅',
                    label: 'Emails de esta semana',
                    description: 'Ver emails de los últimos 7 días',
                    keywords: ['semana', 'week', 'fecha', 'reciente'],
                    action: () => this.applyFilterAjax({ date_filter: 'this_week' })
                },
                {
                    id: 'filter-month',
                    section: 'Filtros de Fecha',
                    icon: '📅',
                    label: 'Emails de este mes',
                    description: 'Ver emails del mes actual',
                    keywords: ['mes', 'month', 'fecha'],
                    action: () => this.applyFilterAjax({ date_filter: 'this_month' })
                },

                // OTROS FILTROS
                {
                    id: 'filter-attachments',
                    section: 'Filtros',
                    icon: '📎',
                    label: 'Emails con adjuntos',
                    description: 'Ver solo emails con archivos adjuntos',
                    keywords: ['adjuntos', 'attachments', 'archivos', 'files'],
                    action: () => this.applyFilterAjax({ has_attachments: '1' })
                },
                {
                    id: 'filter-unread',
                    section: 'Filtros',
                    icon: '📬',
                    label: 'Emails sin leer',
                    description: 'Ver solo emails no leídos',
                    keywords: ['sin leer', 'unread', 'nuevos'],
                    action: () => this.applyFilterAjax({ filter: 'unread' })
                },
                {
                    id: 'filter-starred',
                    section: 'Filtros',
                    icon: '⭐',
                    label: 'Emails destacados',
                    description: 'Ver solo emails marcados con estrella',
                    keywords: ['destacados', 'starred', 'favoritos', 'importante'],
                    action: () => this.applyFilterAjax({ filter: 'starred' })
                },

                // NAVEGACIÓN RÁPIDA
                {
                    id: 'nav-inbox',
                    section: 'Navegación',
                    icon: '📥',
                    label: 'Ir a Bandeja de entrada',
                    description: 'Ver todos los emails',
                    keywords: ['inbox', 'bandeja', 'entrada', 'todos'],
                    action: () => window.location.href = '/emails'
                },
                {
                    id: 'nav-drafts',
                    section: 'Navegación',
                    icon: '📋',
                    label: 'Ir a Borradores',
                    description: 'Ver emails en borrador',
                    keywords: ['borradores', 'drafts', 'pendientes'],
                    action: () => window.location.href = '/emails/drafts'
                },
                {
                    id: 'nav-trash',
                    section: 'Navegación',
                    icon: '🗑️',
                    label: 'Ir a Papelera',
                    description: 'Ver emails eliminados',
                    keywords: ['papelera', 'trash', 'eliminados', 'basura'],
                    action: () => window.location.href = '/emails/trash'
                },
                {
                    id: 'nav-dashboard',
                    section: 'Navegación',
                    icon: '🏠',
                    label: 'Ir al Dashboard',
                    description: 'Volver al panel principal',
                    keywords: ['dashboard', 'inicio', 'home', 'principal'],
                    action: () => window.location.href = '/dashboard'
                },
                {
                    id: 'nav-settings',
                    section: 'Navegación',
                    icon: '⚙️',
                    label: 'Ir a Configuración',
                    description: 'Ajustes y preferencias',
                    keywords: ['configuracion', 'settings', 'ajustes', 'preferencias'],
                    action: () => window.location.href = '/settings/usage'
                },
            ];
        },

        filterCommands() {
            if (!this.searchQuery.trim()) {
                this.filteredCommands = this.commands;
                return;
            }

            const query = this.searchQuery.toLowerCase();
            this.filteredCommands = this.commands.filter(cmd => {
                const matchLabel = cmd.label.toLowerCase().includes(query);
                const matchDescription = cmd.description?.toLowerCase().includes(query);
                const matchKeywords = cmd.keywords?.some(kw => kw.toLowerCase().includes(query));
                return matchLabel || matchDescription || matchKeywords;
            });

            this.selectedIndex = 0;
        },

        get groupedCommands() {
            const grouped = {};
            this.filteredCommands.forEach(cmd => {
                if (!grouped[cmd.section]) {
                    grouped[cmd.section] = [];
                }
                grouped[cmd.section].push(cmd);
            });
            return grouped;
        },

        selectNext() {
            if (this.selectedIndex < this.filteredCommands.length - 1) {
                this.selectedIndex++;
            }
        },

        selectPrevious() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },

        isSelected(command) {
            return this.filteredCommands[this.selectedIndex]?.id === command.id;
        },

        getGlobalIndex(command) {
            return this.filteredCommands.findIndex(cmd => cmd.id === command.id);
        },

        executeSelected() {
            const selected = this.filteredCommands[this.selectedIndex];
            if (selected) {
                this.executeCommand(selected);
            }
        },

        executeCommand(command) {
            this.open = false;
            this.saveRecentCommand(command);
            command.action();
        },

        promptSearch(field) {
            const fieldNames = {
                'subject': 'asunto',
                'from': 'remitente',
                'content': 'contenido'
            };

            const term = prompt(`Buscar por ${fieldNames[field]}:`);
            if (term) {
                window.location.href = `/emails?search=${encodeURIComponent(term)}&search_field=${field}`;
            }
        },

        async applyFilterAjax(params) {
            // Redirigir con parámetros
            const queryString = new URLSearchParams(params).toString();
            window.location.href = `/emails?${queryString}`;
        },

        async moveToTrash(emailId) {
            if (confirm('¿Mover este email a la papelera?')) {
                try {
                    const response = await fetch(`/email-workflow/${emailId}/move-to-trash`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        window.location.href = '/emails';
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        },

        async toggleStar(emailId) {
            try {
                const response = await fetch(`/emails/${emailId}/toggle-star`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async archiveEmail(emailId) {
            if (confirm('¿Archivar este email?')) {
                try {
                    const response = await fetch(`/emails/${emailId}/archive`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        window.location.href = '/emails';
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        }
    };
}
</script>
@endpush
