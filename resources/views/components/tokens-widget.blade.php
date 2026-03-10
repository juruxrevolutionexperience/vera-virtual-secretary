@php
    $user        = auth()->user();
    $isUnlimited = $user->planEmailLimit() === null;
    $used        = $user->monthlyEmailsUsed();
    $limit       = $user->planEmailLimit() ?? 0;
    $remaining   = $user->emailsRemaining();
    $percent     = $isUnlimited ? 0 : min(100, $limit > 0 ? round(($used / $limit) * 100, 1) : 0);

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

    $planLabel = match($user->plan_type) {
        'free_trial' => 'Free Trial',
        'starter'    => 'Starter',
        'pro'        => 'Pro',
        'enterprise' => 'Enterprise',
        default      => 'Basic',
    };
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-5 hover-lift">
    {{-- Cabecera --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <span class="text-sm font-semibold text-gray-900">Plan mensual</span>
        </div>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full
            {{ $user->plan_type === 'pro'        ? 'bg-blue-50 text-blue-600'   :
              ($user->plan_type === 'enterprise'  ? 'bg-purple-50 text-purple-600' : 'bg-gray-100 text-gray-500') }}">
            {{ $planLabel }}
        </span>
    </div>

    @if($isUnlimited)
        {{-- Enterprise / ilimitado --}}
        <p class="text-3xl font-semibold text-gray-900 mb-1">{{ number_format($used) }}</p>
        <p class="text-sm text-gray-500 mb-3">emails procesados · <span class="text-purple-600 font-medium">Ilimitado</span></p>
        <div class="w-full bg-gray-100 rounded-full h-1.5">
            <div class="bg-purple-400 h-1.5 rounded-full w-full"></div>
        </div>
    @else
        {{-- Planes con límite --}}
        <p class="text-3xl font-semibold text-gray-900 mb-1">{{ $used }}</p>
        <p class="text-sm text-gray-500 mb-3">
            de {{ $limit }} emails este mes
        </p>

        {{-- Barra de progreso --}}
        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500"
                 style="width: {{ $percent }}%"></div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs {{ $textColor }} font-medium">{{ $percent }}% usado</span>
            @if($remaining !== null && $remaining < ($limit * 0.2))
                <span class="text-xs text-red-500 font-medium">
                    ⚠ {{ $remaining }} restantes
                </span>
            @else
                <span class="text-xs text-gray-400">
                    {{ $remaining }} disponibles
                </span>
            @endif
        </div>

        @if($percent >= 90)
            <div class="mt-3 p-2 bg-red-50 rounded-md">
                <p class="text-xs text-red-600 font-medium">Límite casi alcanzado. Considera actualizar tu plan.</p>
            </div>
        @elseif($percent >= 70)
            <div class="mt-3 p-2 bg-yellow-50 rounded-md">
                <p class="text-xs text-yellow-700 font-medium">Llevas el {{ $percent }}% del plan consumido.</p>
            </div>
        @endif
    @endif

    {{-- Link a historial --}}
    <a href="{{ route('vera.tokens') }}"
       class="mt-4 flex items-center justify-center gap-1 text-xs text-purple-600 hover:text-purple-700 font-medium pt-3 border-t border-gray-100">
        Ver uso detallado
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
