<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin · Feedbacks — Vera</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Feedbacks</h1>
                    <p class="text-sm text-gray-500">Reportes enviados por usuarios desde el panel.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver al panel</a>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 py-6">
            {{-- Contadores por status --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                @foreach($statuses as $slug => $label)
                    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ $counts[$slug] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Filtros --}}
            <form method="GET" action="{{ route('admin.feedbacks.index') }}" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="rounded-md border-gray-300 text-sm">
                        <option value="">Todos</option>
                        @foreach($statuses as $slug => $label)
                            <option value="{{ $slug }}" @selected(request('status') === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="category" class="rounded-md border-gray-300 text-sm">
                        <option value="">Todas</option>
                        @foreach($categories as $slug => $label)
                            <option value="{{ $slug }}" @selected(request('category') === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-gray-900 text-white text-sm px-4 py-2 rounded-md hover:bg-gray-700">Filtrar</button>
                @if(request()->hasAny(['status', 'category']))
                    <a href="{{ route('admin.feedbacks.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Limpiar</a>
                @endif
            </form>

            {{-- Tabla --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                @if($feedbacks->count() === 0)
                    <p class="p-8 text-center text-gray-500">No hay reportes que mostrar.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Fecha</th>
                                <th class="px-4 py-2">Usuario</th>
                                <th class="px-4 py-2">Categoría</th>
                                <th class="px-4 py-2">Mensaje</th>
                                <th class="px-4 py-2">Adjuntos</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($feedbacks as $fb)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $fb->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $fb->user->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ $fb->user->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded">{{ $fb->categoryLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-3 max-w-md">
                                        <p class="text-gray-700 line-clamp-2">{{ $fb->message }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ $fb->attachments->count() ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.feedbacks.show', $fb) }}" class="inline-block bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded hover:bg-indigo-100">
                                            {{ $fb->statusLabel() }} →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="mt-4">
                {{ $feedbacks->links() }}
            </div>
        </main>
    </div>
</body>
</html>
