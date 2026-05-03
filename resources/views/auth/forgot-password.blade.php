<x-guest-layout>
    <!-- Title -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white mb-2">Recuperar contraseña</h2>
        <p class="text-sm text-gray-400">Introduce tu email y te enviamos un enlace para restablecerla.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm text-gray-300 font-medium" />
            <x-text-input id="email"
                class="input-dark block mt-1.5 w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="tu@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full px-5 py-3 rounded-xl font-medium text-sm text-center text-white">
                Enviar enlace de recuperación
            </button>
        </div>
    </form>

    <div class="text-center mt-6">
        <a href="{{ route('login') }}" class="text-xs text-[#667eea] hover:text-[#764ba2] transition-colors">
            ← Volver al inicio de sesión
        </a>
    </div>
</x-guest-layout>
