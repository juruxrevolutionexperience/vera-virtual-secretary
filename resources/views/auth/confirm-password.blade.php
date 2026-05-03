<x-guest-layout>
    <!-- Title -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white mb-2">Confirma tu contraseña</h2>
        <p class="text-sm text-gray-400">Esta es una zona segura. Confirma tu contraseña para continuar.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm text-gray-300 font-medium" />
            <x-text-input id="password"
                class="input-dark block mt-1.5 w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500"
                type="password"
                name="password"
                required
                autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full px-5 py-3 rounded-xl font-medium text-sm text-center text-white">
                Confirmar
            </button>
        </div>
    </form>
</x-guest-layout>
