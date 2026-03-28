@auth
@if(auth()->user()->isDev())
<div x-data="{ open: false }"
     class="fixed bottom-4 right-4 z-[9999] font-['Inter']">

    {{-- Botón flotante --}}
    <div class="flex items-center gap-2 mb-2 justify-end">
        <div x-show="!open"
             class="bg-amber-500 text-white text-xs font-semibold px-3 py-1.5 rounded-full shadow-lg opacity-80">
            DEV
        </div>
        <button @click="open = !open"
            class="w-10 h-10 bg-gray-900 text-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-700 transition-colors">
            <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Panel desplegable --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-gray-900 text-white rounded-2xl shadow-2xl w-64 overflow-hidden">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-white">Dev Panel</p>
                <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                {{ auth()->user()->plan_type === 'pro' ? 'bg-purple-500' :
                   (auth()->user()->plan_type === 'team' ? 'bg-emerald-500' : 'bg-blue-500') }} text-white">
                {{ ucfirst(auth()->user()->plan_type ?? 'basic') }}
            </span>
        </div>

        {{-- Plan switcher rápido --}}
        <div class="px-4 py-3 border-b border-gray-700">
            <p class="text-xs text-gray-400 mb-2">Cambiar plan</p>
            <form method="POST" action="{{ route('dev.switch-plan') }}" class="flex gap-1.5">
                @csrf
                @foreach(['basic' => 'Básica', 'pro' => 'Pro', 'team' => 'Equipo'] as $val => $label)
                    <button type="submit" name="plan" value="{{ $val }}"
                        class="flex-1 py-1.5 text-xs font-medium rounded-lg transition-colors
                            {{ auth()->user()->plan_type === $val
                                ? 'bg-white text-gray-900'
                                : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </form>
        </div>

        {{-- Links rápidos --}}
        <div class="px-4 py-3 space-y-1">
            <p class="text-xs text-gray-400 mb-2">Accesos directos</p>

            <a href="{{ route('dev.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-xs text-gray-200 group">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                Panel Dev completo
            </a>

            <a href="{{ route('dev.tokens') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-xs text-gray-200">
                <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Token Analytics
            </a>

            <a href="{{ route('vera.activity.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-xs text-gray-200">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Actividad Vera
            </a>

            <a href="/horizon" target="_blank"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-xs text-gray-200">
                <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg>
                Horizon (colas)
            </a>

            {{-- Reset onboarding --}}
            <form method="POST" action="{{ route('dev.reset-onboarding') }}"
                  onsubmit="return confirm('¿Resetear el onboarding?')">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-xs text-gray-200 text-left">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reset onboarding
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endauth
