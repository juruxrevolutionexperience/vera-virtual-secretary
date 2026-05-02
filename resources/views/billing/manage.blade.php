<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Plan y facturación — Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-[#fafafa] text-gray-900" style="font-family: 'Inter', sans-serif;">

<div class="flex min-h-screen overflow-x-hidden">
    @include('components.dashboard-sidebar')

    <main class="flex-1 min-w-0 lg:ml-56">

        <!-- Header móvil -->
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center px-4 lg:hidden">
            <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100 mr-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-sm font-semibold text-gray-900">Plan y facturación</span>
        </header>

        <div class="p-4 lg:p-8 max-w-3xl">

            <!-- Header escritorio -->
            <div class="hidden lg:block mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Plan y facturación</h1>
                <p class="text-sm text-gray-500 mt-0.5">Gestiona tu suscripción y método de pago</p>
            </div>

            @if(session('status'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @php
                $user = auth()->user();
                $subscription = $user->subscription('default');
                $priceMap = [
                    env('STRIPE_PRICE_ESSENTIALS')   => ['name' => 'Vera Essentials', 'price' => '29€/mes'],
                    env('STRIPE_PRICE_PROFESSIONAL') => ['name' => 'Vera Professional', 'price' => '59€/mes'],
                    env('STRIPE_PRICE_BUSINESS')     => ['name' => 'Vera Business', 'price' => '149€/mes'],
                ];
                $currentPlan = $priceMap[$subscription?->stripe_price] ?? ['name' => 'Plan activo', 'price' => ''];
            @endphp

            <!-- Plan actual -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Plan actual</h2>

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-lg font-bold text-gray-900">{{ $currentPlan['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $currentPlan['price'] }}</p>
                    </div>
                    @if($subscription && !$subscription->canceled())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full border border-green-200">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 text-xs font-semibold rounded-full border border-amber-200">
                            Cancelado
                        </span>
                    @endif
                </div>

                @if($subscription && $subscription->ends_at)
                    <p class="text-sm text-gray-500 mb-4">
                        Acceso hasta el <strong>{{ $subscription->ends_at->format('d/m/Y') }}</strong>
                    </p>
                @endif

                <div class="flex gap-3 flex-wrap">
                    <a href="{{ route('billing.portal') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        Gestionar en Stripe
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>

                    @if($subscription && $subscription->onGracePeriod())
                        <form method="POST" action="{{ route('billing.resume') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
                                Reanudar suscripción
                            </button>
                        </form>
                    @elseif($subscription && !$subscription->canceled())
                        <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('¿Cancelar la suscripción?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-white border border-gray-200 hover:bg-red-50 hover:border-red-200 hover:text-red-600 text-gray-500 text-sm font-semibold rounded-xl transition-colors">
                                Cancelar suscripción
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Cambiar plan -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Cambiar plan</h2>
                <p class="text-sm text-gray-500 mb-4">Para cambiar de plan, usa el portal de Stripe donde puedes actualizar o degradar tu suscripción de forma inmediata.</p>
                <a href="{{ route('billing.portal') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
                    Ir al portal de facturación
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('hidden');
    sidebar.classList.toggle('flex');
}
</script>

@include('components.dev-toolbar')
@stack('scripts')

</body>
</html>
