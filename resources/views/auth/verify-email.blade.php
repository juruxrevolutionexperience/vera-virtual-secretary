<x-guest-layout>
    <!-- Title -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white mb-2">Verifica tu email</h2>
        <p class="text-sm text-gray-400">Hemos enviado un enlace de verificación a tu dirección de email. Haz clic en él para activar tu cuenta.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 px-4 py-3 rounded-xl bg-white/5 border border-green-500/30 text-green-400 text-sm font-medium">
            Se ha enviado un nuevo enlace de verificación a tu email.
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full px-5 py-3 rounded-xl font-medium text-sm text-center text-white">
                Reenviar email de verificación
            </button>
        </form>

        <div class="text-center">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
