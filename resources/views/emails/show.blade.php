<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $email->subject }} - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }

        .email-body {
            line-height: 1.6;
        }

        .email-body img {
            max-width: 100%;
            height: auto;
        }

        .email-body a {
            color: #3b82f6;
            text-decoration: underline;
        }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('emails.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 md:ml-56">
            <!-- Top Bar -->
            <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Botón Volver -->
                        <a href="{{ route('emails.index') }}"
                           class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver a emails
                        </a>

                        <!-- Acciones -->
                        <div class="flex items-center space-x-2">
                            <!-- Destacar -->
                            <button onclick="toggleStar({{ $email->id }})"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                    title="{{ $email->is_starred ? 'Quitar destacado' : 'Destacar' }}">
                                <svg class="w-5 h-5 {{ $email->is_starred ? 'text-yellow-500 fill-current' : 'text-gray-400' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>

                            <!-- Archivar -->
                            <button onclick="archiveEmail({{ $email->id }})"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                    title="Archivar">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                            </button>

                            <!-- Eliminar -->
                            <button onclick="deleteEmail({{ $email->id }})"
                                    class="p-2 rounded-lg hover:bg-red-50 transition-colors"
                                    title="Eliminar">
                                <svg class="w-5 h-5 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Email Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
                    <!-- Asunto -->
                    <h1 class="text-2xl font-semibold text-gray-900 mb-4">
                        {{ $email->subject }}
                    </h1>

                    <!-- Info del remitente -->
                    <div class="flex items-start justify-between border-b border-gray-200 pb-4 mb-4">
                        <div class="flex items-start space-x-3">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-lg">
                                    {{ strtoupper(substr($email->from_name ?: $email->from_email, 0, 1)) }}
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-gray-900">
                                        {{ $email->from_name ?: $email->from_email }}
                                    </span>
                                    @if($email->category)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($email->category === 'primary') bg-blue-100 text-blue-800
                                            @elseif($email->category === 'promotions') bg-purple-100 text-purple-800
                                            @elseif($email->category === 'social') bg-green-100 text-green-800
                                            @elseif($email->category === 'updates') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($email->category) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span>{{ $email->from_email }}</span>
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    Para: <span class="text-gray-700">{{ $email->to_email }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div class="text-sm text-gray-500 whitespace-nowrap ml-4">
                            {{ formatEmailDate($email->received_at) }}
                        </div>
                    </div>

                    <!-- Contenido del email (AISLADO EN IFRAME) -->
                    <div class="email-body prose max-w-none">
                        @if($email->body_html)
                            <!-- Iframe para aislar el HTML del email y evitar que afecte al resto de la app -->
                            <iframe
                                id="email-iframe"
                                sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                                class="w-full border-0 min-h-[500px]"
                                style="background: white;">
                            </iframe>
                            <script>
                                // Inyectar contenido HTML del email en el iframe de forma segura
                                (function() {
                                    const iframe = document.getElementById('email-iframe');
                                    const emailContent = @json($email->body_html);

                                    iframe.onload = function() {
                                        const doc = iframe.contentDocument || iframe.contentWindow.document;
                                        doc.open();
                                        doc.write(emailContent);
                                        doc.close();

                                        // Auto-resize
                                        setTimeout(() => {
                                            iframe.style.height = (doc.documentElement.scrollHeight + 20) + 'px';
                                        }, 200);
                                    };

                                    // Trigger onload
                                    iframe.src = 'about:blank';
                                })();
                            </script>
                        @elseif($email->body_text)
                            <div class="whitespace-pre-wrap">{{ $email->body_text }}</div>
                        @else
                            <p class="text-gray-500 italic">Este email no tiene contenido.</p>
                        @endif
                    </div>

                    <!-- Adjuntos (si existen) -->
                    @if($email->attachments && $email->attachments->count() > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                                Adjuntos ({{ $email->attachments->count() }})
                            </h3>
                            <div class="space-y-2">
                                @foreach($email->attachments as $attachment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $attachment->filename }}</p>
                                                <p class="text-xs text-gray-500">{{ number_format($attachment->size / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('attachments.download', $attachment) }}"
                                           class="text-sm text-indigo-600 hover:text-indigo-800">
                                            Descargar
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Acciones de Vera (si existen) -->
                @if($email->veraActions && $email->veraActions->count() > 0)
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg border border-indigo-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Acciones de Vera
                        </h3>
                        <div class="space-y-3">
                            @foreach($email->veraActions as $action)
                                <div class="bg-white rounded-lg p-4 border border-indigo-100">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $action->action_type }}</p>
                                            <p class="text-sm text-gray-600 mt-1">{{ $action->description }}</p>
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            {{ formatEmailDate($action->created_at) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        // ==========================================
        // COMPONENTE ALPINE.JS: ÁRBOL DE CARPETAS
        // ==========================================
        document.addEventListener('alpine:init', () => {
            Alpine.data('foldersTree', () => ({
                folders: [],
                loading: true,
                selectedFolderId: null,
                expandedFolders: {},

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
                    window.location.href = `/emails?folder=${folderId}`;
                },

                toggleFolder(folderId) {
                    this.expandedFolders[folderId] = !this.expandedFolders[folderId];
                },

                isFolderExpanded(folderId) {
                    return this.expandedFolders[folderId] || false;
                },

                getFolderIconEmoji(icon) {
                    const emojis = {
                        folder: '📁',
                        star: '⭐',
                        briefcase: '💼',
                        user: '👤',
                        archive: '📦',
                        heart: '❤️',
                        flag: '🚩',
                        check: '✅',
                        clock: '⏰',
                        document: '📄'
                    };
                    return emojis[icon] || '📁';
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

                    // Chevron si tiene hijos
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

                    // Ícono y nombre
                    html += `
                        <span style="color: ${folder.color || '#6B7280'}" class="text-sm flex-shrink-0">${this.getFolderIconEmoji(folder.icon)}</span>
                        <span class="flex-1 truncate text-sm">${folder.name}</span>
                        <span class="text-xs text-gray-400 font-medium">${folder.emails_count || 0}</span>
                    `;

                    html += `</div>`;

                    // Renderizar hijos recursivamente
                    if (hasChildren && isExpanded) {
                        for (const child of folder.children) {
                            html += this.renderFolderRecursive(child, depth + 1);
                        }
                    }

                    html += `</div>`;

                    return html;
                },

                openCreateFolderModal() {
                    window.dispatchEvent(new CustomEvent('open-folder-modal'));
                },

                openFolderMenu(folderId) {
                    console.log('Abrir menú para carpeta:', folderId);
                }
            }));
        });

        function toggleStar(emailId) {
            fetch(`/emails/${emailId}/toggle-star`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }

        function archiveEmail(emailId) {
            if (confirm('¿Archivar este email?')) {
                fetch(`/emails/${emailId}/archive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("emails.index") }}';
                    }
                });
            }
        }

        function deleteEmail(emailId) {
            if (confirm('¿Eliminar este email? Se moverá a la papelera.')) {
                fetch(`/emails/${emailId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("emails.index") }}';
                    }
                });
            }
        }
    </script>

    <!-- Portal para dropdowns (fuera del flujo con overflow) -->
    <div id="dropdowns-portal" class="fixed" style="z-index: 99999;"></div>

</body>

</html>
