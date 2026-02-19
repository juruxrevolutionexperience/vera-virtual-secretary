@php
    $user = auth()->user();
    $isUnlimited = in_array($user->plan_type, ['enterprise', 'pro']);
    $used = $user->monthly_tokens_used ?? 0;
    $limit = $user->monthly_tokens_limit ?? 50000;
    $percent = $isUnlimited ? 0 : min(100, $limit > 0 ? round(($used / $limit) * 100, 1) : 0);
    $available = $isUnlimited ? null : max(0, $limit - $used);

    // Color de la barra según uso
    $barColor = match(true) {
        $percent >= 90 => 'bg-red-500',
        $percent >= 70 => 'bg-yellow-500',
        default        => 'bg-purple-500',
    };

    $textColor = match(true) {
        $percent >= 90 => 'text-red-600',
        $percent >= 70 => 'text-yellow-600',
        default        => 'text-purple-600',
    };

    // Etiqueta del plan
    $planLabel = match($user->plan_type) {
        'starter'    => 'Starter',
        'pro'        => 'Pro',
        'enterprise' => 'Enterprise',
        default      => 'Basic',
    };
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-5 hover-lift">
    {{-- Cabecera --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347a4 4 0 01-5.657 0l-.347-.347z"/>
                </svg>
            </div>
            <span class="text-sm font-semibold text-gray-900">Tokens IA</span>
        </div>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full
            {{ $user->plan_type === 'pro' ? 'bg-blue-50 text-blue-600' :
               ($user->plan_type === 'enterprise' ? 'bg-purple-50 text-purple-600' : 'bg-gray-100 text-gray-500') }}">
            {{ $planLabel }}
        </span>
    </div>

    @if($isUnlimited)
        {{-- Enterprise: ilimitado --}}
        <p class="text-2xl font-semibold text-gray-900 mb-1">{{ number_format($used) }}</p>
        <p class="text-sm text-gray-500 mb-3">tokens usados este mes · <span class="text-purple-600 font-medium">Ilimitado</span></p>
        <div class="w-full bg-gray-100 rounded-full h-1.5">
            <div class="bg-purple-400 h-1.5 rounded-full w-full"></div>
        </div>
    @else
        {{-- Free / Pro: con límite --}}
        <p class="text-2xl font-semibold text-gray-900 mb-1">{{ number_format($used) }}</p>
        <p class="text-sm text-gray-500 mb-3">
            de {{ number_format($limit) }} tokens este mes
        </p>

        {{-- Barra de progreso --}}
        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500"
                 style="width: {{ $percent }}%"></div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs {{ $textColor }} font-medium">{{ $percent }}% usado</span>
            @if($available !== null && $available < ($limit * 0.2))
                <span class="text-xs text-red-500 font-medium">
                    ⚠ {{ number_format($available) }} restantes
                </span>
            @else
                <span class="text-xs text-gray-400">
                    {{ number_format($available) }} disponibles
                </span>
            @endif
        </div>

        @if($percent >= 90)
            <div class="mt-3 p-2 bg-red-50 rounded-md">
                <p class="text-xs text-red-600 font-medium">Límite casi alcanzado. Considera actualizar tu plan.</p>
            </div>
        @endif
    @endif

    {{-- Link a historial --}}
    <a href="{{ route('vera.tokens') }}"
       class="mt-4 flex items-center justify-center gap-1 text-xs text-purple-600 hover:text-purple-700 font-medium pt-3 border-t border-gray-100">
        Ver historial completo
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
