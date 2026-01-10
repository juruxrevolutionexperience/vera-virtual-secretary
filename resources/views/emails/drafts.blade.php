<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Borradores - Vera AI</title>

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

                <h1 class="text-sm font-semibold text-gray-900">📝 Borradores</h1>
                <span class="hidden sm:inline-block text-xs text-gray-400">·</span>
                <span class="hidden sm:inline-block text-xs text-gray-500">{{ $emails->total() }} borradores</span>
            </div>

            <div class="flex items-center gap-2">
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
                                        <span class="text-xs text-gray-500">{{ $email->updated_at->diffForHumans() }}</span>
                                        <span class="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-700 rounded-full">Borrador</span>
                                    </div>
                                    <p class="text-sm text-gray-700 mb-1">{{ $email->subject ?? '(Sin asunto)' }}</p>
                                    <p class="text-xs text-gray-500">{{ Str::limit($email->excerpt, 100) }}</p>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <!-- Editar/Ver -->
                                    <a href="{{ route('emails.show', $email->id) }}"
                                       class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100">
                                        ✏️ Editar
                                    </a>

                                    <!-- Eliminar -->
                                    <form method="POST" action="{{ route('emails.destroy', $email->id) }}"
                                          onsubmit="return confirm('¿Eliminar este borrador?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-md hover:bg-red-100">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
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
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">No hay borradores</h3>
                    <p class="text-sm text-gray-500">Todos tus emails en progreso aparecerán aquí.</p>
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
</script>

</body>
</html>
