<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendar — Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="antialiased bg-[#fafafa] text-gray-900">

<div class="flex min-h-screen" x-data="calendarPage()">
    @include('components.dashboard-sidebar')

    <main class="flex-1 ml-56 p-8">

        {{-- Flash messages --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
             class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="text-green-500 hover:text-green-700 ml-4">✕</button>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl">{{ session('error') }}</div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Calendar</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $from->locale('es')->isoFormat('D MMM') }} — {{ $to->locale('es')->isoFormat('D MMM YYYY') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Filtro semana / mes --}}
                <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white text-sm">
                    <a href="{{ route('vera.calendar.index', ['filter' => 'week']) }}"
                       class="px-4 py-2 {{ $filter === 'week' ? 'bg-purple-600 text-white font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                        Esta semana
                    </a>
                    <a href="{{ route('vera.calendar.index', ['filter' => 'month']) }}"
                       class="px-4 py-2 {{ $filter === 'month' ? 'bg-purple-600 text-white font-medium' : 'text-gray-600 hover:bg-gray-50' }} border-l border-gray-200">
                        Este mes
                    </a>
                </div>
                {{-- Nueva reunión --}}
                <button @click="openCreate()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva reunión
                </button>
            </div>
        </div>

        {{-- Lista de eventos agrupados por día --}}
        @if($grouped->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">Sin reuniones en este período</p>
            <button @click="openCreate()" class="mt-3 text-sm text-purple-600 hover:text-purple-700 font-medium">
                + Crear reunión manual
            </button>
        </div>
        @else
        <div class="space-y-6">
            @foreach($grouped->sortKeys() as $date => $events)
            @php
                $day = \Carbon\Carbon::parse($date);
                $isToday = $day->isToday();
            @endphp
            <div class="bg-white rounded-xl border {{ $isToday ? 'border-purple-200' : 'border-gray-200' }}">
                {{-- Cabecera del día --}}
                <div class="px-5 py-3 border-b {{ $isToday ? 'border-purple-100 bg-purple-50' : 'border-gray-100' }} flex items-center gap-3">
                    <span class="text-sm font-semibold {{ $isToday ? 'text-purple-700' : 'text-gray-700' }}">
                        {{ $day->locale('es')->isoFormat('dddd D [de] MMMM') }}
                    </span>
                    @if($isToday)
                    <span class="text-xs bg-purple-600 text-white px-2 py-0.5 rounded-full font-medium">Hoy</span>
                    @endif
                    <span class="text-xs text-gray-400 ml-auto">{{ count($events) }} reunión{{ count($events) !== 1 ? 'es' : '' }}</span>
                </div>

                {{-- Eventos del día --}}
                <div class="divide-y divide-gray-50">
                    @foreach($events as $event)
                    <div class="px-5 py-4 flex items-center gap-4">
                        {{-- Hora --}}
                        <div class="w-16 flex-shrink-0 text-center">
                            <p class="text-sm font-semibold text-gray-900">{{ $event['start']->format('H:i') }}</p>
                            <p class="text-xs text-gray-400">{{ $event['duration_min'] }} min</p>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $event['title'] }}</p>
                                @if($event['source'] === 'email' || $event['source'] === 'whatsapp' || $event['source'] === 'telegram')
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 flex-shrink-0">
                                    Vera
                                </span>
                                @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 flex-shrink-0">
                                    Manual
                                </span>
                                @endif
                            </div>
                            @if($event['contact_email'])
                            <p class="text-xs text-gray-400 mt-0.5">{{ $event['contact_email'] }}</p>
                            @endif
                        </div>

                        {{-- Meet link --}}
                        @if($event['meet_link'])
                        <a href="{{ $event['meet_link'] }}" target="_blank"
                           class="flex-shrink-0 px-3 py-1.5 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                            Meet
                        </a>
                        @endif

                        {{-- Acciones (solo si tiene meeting en BD) --}}
                        @if($event['meeting_id'])
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="openEdit({{ $event['meeting_id'] }}, '{{ $event['start']->format('Y-m-d\TH:i') }}', '{{ addslashes($event['title']) }}')"
                                    class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="openCancel({{ $event['meeting_id'] }}, '{{ addslashes($event['contact_name'] ?? $event['title']) }}', '{{ $event['contact_email'] ?? '' }}')"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Cancelar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </main>

    {{-- ═══ MODAL CREAR REUNIÓN ═══════════════════════════════════════════════ --}}
    <div x-show="showCreate" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         @click.self="showCreate = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Nueva reunión manual</h3>
                <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form action="{{ route('vera.calendar.store') }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nombre del contacto *</label>
                    <input type="text" name="contact_name" required
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-purple-500"
                           placeholder="Joselete García">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email del contacto</label>
                    <input type="email" name="contact_email"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-purple-500"
                           placeholder="joselete@ejemplo.com">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Fecha y hora *</label>
                        <input type="datetime-local" name="scheduled_at" required
                               min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Duración (min) *</label>
                        <select name="duration_minutes"
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-purple-500">
                            <option value="15">15 min</option>
                            <option value="30">30 min</option>
                            <option value="45" selected>45 min</option>
                            <option value="60">60 min</option>
                            <option value="90">90 min</option>
                            <option value="120">2 horas</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Notas</label>
                    <textarea name="notes" rows="2"
                              class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-purple-500 resize-none"
                              placeholder="Motivo de la reunión..."></textarea>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="send_invite" value="1" class="w-4 h-4 text-purple-600 rounded border-gray-300">
                    <span class="text-xs text-gray-600">Enviar invitación por email al contacto</span>
                </label>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        Crear reunión
                    </button>
                    <button type="button" @click="showCreate = false"
                            class="flex-1 py-2 bg-white text-gray-600 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ MODAL EDITAR REUNIÓN ═══════════════════════════════════════════════ --}}
    <div x-show="showEdit" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         @click.self="showEdit = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm" @click.stop>
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Editar reunión</h3>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-gray-600" x-text="editTitle"></p>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nueva fecha y hora</label>
                    <input type="datetime-local" x-model="editDatetime"
                           :min="'{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}'"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-purple-500">
                </div>
                <div x-show="editError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg" x-text="editError"></div>
                <div class="flex gap-3">
                    <button @click="submitEdit()"
                            :disabled="editLoading"
                            class="flex-1 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors">
                        <span x-show="!editLoading">Guardar</span>
                        <span x-show="editLoading">Guardando...</span>
                    </button>
                    <button @click="showEdit = false"
                            class="flex-1 py-2 bg-white text-gray-600 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ MODAL CANCELAR REUNIÓN ═════════════════════════════════════════════ --}}
    <div x-show="showCancel" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         @click.self="showCancel = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm" @click.stop>
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Cancelar reunión</h3>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-gray-600">
                    ¿Cancelar la reunión con <strong x-text="cancelName"></strong>?
                </p>
                <label x-show="cancelEmail" class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="cancelNotify" class="w-4 h-4 text-purple-600 rounded border-gray-300">
                    <span class="text-xs text-gray-600">Enviar email de cancelación al contacto</span>
                </label>
                <div x-show="cancelError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg" x-text="cancelError"></div>
                <div class="flex gap-3">
                    <button @click="submitCancel()"
                            :disabled="cancelLoading"
                            class="flex-1 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors">
                        <span x-show="!cancelLoading">Sí, cancelar</span>
                        <span x-show="cancelLoading">Cancelando...</span>
                    </button>
                    <button @click="showCancel = false"
                            class="flex-1 py-2 bg-white text-gray-600 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Volver
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast" x-cloak x-transition
         class="fixed bottom-6 right-6 z-50 px-4 py-3 bg-gray-900 text-white text-sm rounded-xl shadow-lg"
         x-text="toast"></div>

</div>

<script>
function calendarPage() {
    return {
        // Crear
        showCreate: false,
        openCreate() { this.showCreate = true; },

        // Editar
        showEdit: false,
        editMeetingId: null,
        editDatetime: '',
        editTitle: '',
        editError: '',
        editLoading: false,
        openEdit(id, datetime, title) {
            this.editMeetingId = id;
            this.editDatetime  = datetime;
            this.editTitle     = title;
            this.editError     = '';
            this.showEdit      = true;
        },
        async submitEdit() {
            this.editLoading = true;
            this.editError   = '';
            try {
                const res = await fetch(`/vera/calendar/events/${this.editMeetingId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ scheduled_at: this.editDatetime }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Error al actualizar');
                this.showEdit = false;
                this.showToast('Reunión actualizada correctamente');
                setTimeout(() => location.reload(), 1200);
            } catch (e) {
                this.editError = e.message;
            } finally {
                this.editLoading = false;
            }
        },

        // Cancelar
        showCancel: false,
        cancelMeetingId: null,
        cancelName: '',
        cancelEmail: '',
        cancelNotify: false,
        cancelError: '',
        cancelLoading: false,
        openCancel(id, name, email) {
            this.cancelMeetingId = id;
            this.cancelName      = name;
            this.cancelEmail     = email;
            this.cancelNotify    = !!email;
            this.cancelError     = '';
            this.showCancel      = true;
        },
        async submitCancel() {
            this.cancelLoading = true;
            this.cancelError   = '';
            try {
                const res = await fetch(`/vera/calendar/events/${this.cancelMeetingId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ notify: this.cancelNotify }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Error al cancelar');
                this.showCancel = false;
                this.showToast('Reunión cancelada');
                setTimeout(() => location.reload(), 1200);
            } catch (e) {
                this.cancelError = e.message;
            } finally {
                this.cancelLoading = false;
            }
        },

        // Toast
        toast: '',
        showToast(msg) {
            this.toast = msg;
            setTimeout(() => this.toast = '', 3000);
        },
    }
}
</script>

</body>
</html>
