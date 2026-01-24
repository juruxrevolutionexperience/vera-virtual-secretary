<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Papelera - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    @include('emails.partials.sidebar')

    <!-- Main Content -->
    <main class="flex-1 md:ml-56">
        <!-- Top Bar -->
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6">
            <div class="flex items-center gap-3 flex-1">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 class="text-sm font-semibold text-gray-900">🗑️ Papelera</h1>
                <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                <span class="hidden sm:inline-block text-xs text-gray-500">{{ $emails->total() }} emails eliminados</span>
            </div>

            <div class="flex items-center gap-2">
                @if($stats['total'] > 0)
                    <form method="POST" action="{{ route('email-workflow.empty-trash') }}"
                          onsubmit="return confirm('⚠️ ¿Estás seguro? Esto eliminará PERMANENTEMENTE todos los emails de la papelera. Esta acción NO se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                            Vaciar Papelera
                        </button>
                    </form>
                @endif

                <a href="{{ route('emails.index') }}"
                   class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Volver a Inbox
                </a>
            </div>
        </header>

        <!-- Email List -->
        <div class="p-6">
            @if($emails->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    @foreach($emails as $email)
                        <div class="border-b border-gray-100 p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-gray-900">{{ $email->from_name ?? $email->from_email }}</span>
                                        <span class="text-xs text-gray-500">{{ $email->deleted_at_custom?->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 mb-1">{{ $email->subject ?? '(Sin asunto)' }}</p>
                                    <p class="text-xs text-gray-500">{{ Str::limit($email->excerpt, 100) }}</p>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <!-- Restaurar -->
                                    <form method="POST" action="{{ route('email-workflow.restore-from-trash', $email->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-md hover:bg-green-100"
                                                title="Restaurar email">
                                            ♻️ Restaurar
                                        </button>
                                    </form>

                                    <!-- Ver -->
                                    <a href="{{ route('emails.show', $email->id) }}"
                                       class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                        Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                <div class="mt-4">
                    {{ $emails->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">La papelera está vacía</h3>
                    <p class="text-sm text-gray-500">No hay emails eliminados.</p>
                </div>
            @endif
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('hidden');
    overlay.classList.toggle('hidden');
}

// Componente Alpine.js para carpetas
document.addEventListener('alpine:init', () => {
    Alpine.data('foldersTree', () => ({
        folders: [],
        loading: true,
        selectedFolderId: null,
        expandedFolders: {},
        contextMenuOpen: false,
        contextMenuFolderId: null,
        contextMenuPosition: { x: 0, y: 0 },

        init() {
            this.loadFolders();
        },

        async loadFolders() {
            try {
                const response = await fetch('/email-folders/tree', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) throw new Error('Error al cargar carpetas');
                const data = await response.json();
                this.folders = data.folders || [];
            } catch (error) {
                console.error('Error:', error);
                this.folders = [];
            } finally {
                this.loading = false;
            }
        },

        async selectFolder(folderId) {
            window.location.href = `/emails/trash?folder=${folderId}`;
        },

        toggleFolder(folderId) {
            this.expandedFolders[folderId] = !this.expandedFolders[folderId];
        },

        isFolderExpanded(folderId) {
            return this.expandedFolders[folderId] || false;
        },

        renderFolderRecursive(folder, depth) {
            const hasChildren = folder.children && folder.children.length > 0;
            const isExpanded = this.isFolderExpanded(folder.id);
            const isSelected = this.selectedFolderId === folder.id;
            const indentClass = depth > 0 ? `ml-${depth * 4}` : '';

            let html = `
                <div class="${indentClass}">
                    <div @click="selectFolder(${folder.id})"
                        class="flex items-center gap-2 px-2 py-1.5 rounded-md mb-1 cursor-pointer group relative ${isSelected ? 'bg-purple-50 text-purple-900' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'}">
            `;

            if (hasChildren) {
                html += `
                    <button @click.stop="toggleFolder(${folder.id})" class="flex-shrink-0">
                        <svg class="w-3 h-3 transition-transform ${isExpanded ? 'rotate-90' : ''}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                `;
            } else {
                html += `<span class="w-3"></span>`;
            }

            html += `
                <span style="color: ${folder.color || '#6B7280'}" class="text-sm flex-shrink-0">${this.getFolderIconEmoji(folder.icon)}</span>
                <span class="flex-1 truncate text-sm">${folder.name}</span>
                <span class="text-xs text-gray-400 font-medium">${folder.emails_count || 0}</span>
            `;

            if (!folder.is_system_folder) {
                html += `
                    <button @click.stop="openFolderMenu(${folder.id}, $event)" class="opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                    </button>
                `;
            }

            html += `</div>`;

            if (hasChildren && isExpanded) {
                for (const child of folder.children) {
                    html += this.renderFolderRecursive(child, depth + 1);
                }
            }

            html += `</div>`;
            return html;
        },

        getFolderIconEmoji(icon) {
            const emojis = { folder: '📁', star: '⭐', briefcase: '💼', user: '👤', archive: '📦', heart: '❤️', flag: '🚩', check: '✅', clock: '⏰', document: '📄' };
            return emojis[icon] || '📁';
        },

        openCreateFolderModal() {
            window.location.href = '/emails';
        },

        openFolderMenu(folderId, event) {
            event.stopPropagation();
            if (this.contextMenuFolderId === folderId && this.contextMenuOpen) {
                this.closeContextMenu();
                return;
            }
            this.contextMenuFolderId = folderId;
            this.contextMenuOpen = true;
            const rect = event.target.closest('button').getBoundingClientRect();
            this.contextMenuPosition = { x: rect.right - 150, y: rect.bottom + 5 };
        },

        closeContextMenu() {
            this.contextMenuOpen = false;
            this.contextMenuFolderId = null;
        },

        async editFolder(folderId) {
            window.location.href = '/emails';
        },

        async deleteFolder(folderId) {
            this.closeContextMenu();
            const folder = this.findFolderById(folderId, this.folders);
            if (!folder) return;
            const confirmed = confirm(`¿Eliminar la carpeta "${folder.name}"?\n\nLos emails NO se eliminarán, solo se quitarán de esta carpeta.`);
            if (!confirmed) return;
            try {
                const response = await fetch(`/email-folders/${folderId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reassign_emails: false })
                });
                if (!response.ok) throw new Error('Error al eliminar carpeta');
                await this.loadFolders();
                alert('Carpeta eliminada correctamente');
            } catch (error) {
                console.error('❌ Error:', error);
                alert('Error al eliminar la carpeta');
            }
        },

        findFolderById(folderId, folders) {
            for (const folder of folders) {
                if (folder.id === folderId) return folder;
                if (folder.children && folder.children.length > 0) {
                    const found = this.findFolderById(folderId, folder.children);
                    if (found) return found;
                }
            }
            return null;
        }
    }));
});
</script>

<!-- Command Palette Global (Ctrl+K) -->
@include('components.command-palette')

<!-- Scripts adicionales -->
@stack('scripts')

</body>
</html>
