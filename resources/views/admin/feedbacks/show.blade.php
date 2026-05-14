<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Feedback #{{ $feedback->id }} — Admin</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Feedback #{{ $feedback->id }}</h1>
                    <p class="text-sm text-gray-500">{{ $feedback->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <a href="{{ route('admin.feedbacks.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver al listado</a>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-6 space-y-4">
            @if(session('status'))
                <div class="bg-green-50 text-green-800 text-sm px-4 py-2 rounded-md">{{ session('status') }}</div>
            @endif

            {{-- Datos --}}
            <div class="bg-white rounded-lg border border-gray-200 p-5 space-y-3">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Usuario</p>
                        <p class="font-medium">{{ $feedback->user->name ?? 'N/A' }}</p>
                        <p class="text-gray-500 text-xs">{{ $feedback->user->email ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Categoría</p>
                        <p class="font-medium">{{ $feedback->categoryLabel() }}</p>
                    </div>
                    @if($feedback->context_url)
                        <div class="col-span-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">URL contexto</p>
                            <a href="{{ $feedback->context_url }}" target="_blank" class="text-sm text-indigo-600 break-all hover:underline">{{ $feedback->context_url }}</a>
                        </div>
                    @endif
                    @if($feedback->conversation_id)
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Conversación</p>
                            <p class="text-sm">#{{ $feedback->conversation_id }}</p>
                        </div>
                    @endif
                </div>

                <hr class="border-gray-100">

                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Mensaje</p>
                    <p class="whitespace-pre-wrap text-gray-800">{{ $feedback->message }}</p>
                </div>

                @if($feedback->attachments->count())
                    <hr class="border-gray-100">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Adjuntos ({{ $feedback->attachments->count() }})</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($feedback->attachments as $att)
                                <a href="{{ $att->url() }}" target="_blank" class="block group">
                                    <img src="{{ $att->url() }}" alt="{{ $att->original_name }}" class="w-full h-40 object-cover rounded border border-gray-200 group-hover:border-indigo-400">
                                    <p class="text-xs text-gray-500 mt-1 truncate">{{ $att->original_name }} · {{ number_format($att->size / 1024, 1) }} KB</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($feedback->metadata))
                    <hr class="border-gray-100">
                    <details class="text-xs text-gray-500">
                        <summary class="cursor-pointer hover:text-gray-700">Metadata</summary>
                        <pre class="mt-2 p-2 bg-gray-50 rounded overflow-x-auto">{{ json_encode($feedback->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                @endif
            </div>

            {{-- Formulario admin --}}
            <form method="POST" action="{{ route('admin.feedbacks.update', $feedback) }}" class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
                @csrf
                @method('PATCH')

                <h2 class="text-base font-semibold">Gestión</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                        @foreach($statuses as $slug => $label)
                            <option value="{{ $slug }}" @selected(old('status', $feedback->status) === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas internas</label>
                    <textarea name="admin_notes" rows="4" class="w-full rounded-md border-gray-300 text-sm" placeholder="Solo visibles para ti. Lo que hiciste, conclusión, etc.">{{ old('admin_notes', $feedback->admin_notes) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md">Guardar</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
