@php
    $user = auth()->user();
    $weekMeetings = \App\Models\Meeting::forUser($user->id)
        ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->confirmed()
        ->count();
    $pending = \App\Models\Meeting::forUser($user->id)
        ->where('status', 'pending')
        ->count();
    $config = \App\Models\VeraConfig::where('user_id', $user->id)->first();
    $calendarOk = $config && $config->hasCalendarConnected();
@endphp

<div class="bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-5">
    <div class="flex items-center gap-2 mb-4">
        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-900">Vera esta semana</h3>
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-600">Reuniones confirmadas</span>
            <span class="text-sm font-bold text-purple-700">{{ $weekMeetings }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-600">Solicitudes pendientes</span>
            <span class="text-sm font-bold {{ $pending > 0 ? 'text-amber-600' : 'text-gray-500' }}">{{ $pending }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-600">Google Calendar</span>
            <span class="text-xs font-medium {{ $calendarOk ? 'text-green-600' : 'text-red-500' }}">
                {{ $calendarOk ? 'Conectado' : 'Sin conectar' }}
            </span>
        </div>
    </div>

    <a href="{{ route('vera.dashboard') }}"
       class="mt-4 flex items-center justify-center gap-1.5 w-full py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg transition-colors">
        Ver panel Vera →
    </a>
</div>
