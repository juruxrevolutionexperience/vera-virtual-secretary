<x-guest-layout>
    <!-- Title -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white mb-2">Nueva contraseña</h2>
        <p class="text-sm text-gray-400">Elige una contraseña segura para tu cuenta.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm text-gray-300 font-medium" />
            <x-text-input id="email"
                class="input-dark block mt-1.5 w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                required
                autofocus
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm text-gray-300 font-medium" />
            <x-text-input id="password"
                class="input-dark block mt-1.5 w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Mínimo 8 caracteres" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm text-gray-300 font-medium" />
            <x-text-input id="password_confirmation"
                class="input-dark block mt-1.5 w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Repite tu contraseña" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full px-5 py-3 rounded-xl font-medium text-sm text-center text-white">
                Restablecer contraseña
            </button>
        </div>
    </form>
</x-guest-layout>
