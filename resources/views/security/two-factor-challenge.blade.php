<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="flex justify-center mb-4">
            <div class="w-14 h-14 bg-purple-600/20 rounded-2xl flex items-center justify-center">
                <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>
        <h2 class="text-2xl font-semibold text-white mb-2">Verificación en dos pasos</h2>
        <p class="text-sm text-gray-400">Introduce el código de 6 dígitos de tu aplicación de autenticación</p>
    </div>

    <div x-data="{ useRecovery: false }">

        {{-- Formulario código OTP --}}
        <form method="POST" action="{{ route('2fa.verify') }}" x-show="!useRecovery" class="space-y-5">
            @csrf

            <div>
                <label for="code" class="block text-sm text-gray-300 font-medium mb-1.5">Código de autenticación</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    autocomplete="one-time-code"
                    autofocus
                    placeholder="000000"
                    class="block w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-center text-2xl tracking-[0.5em] placeholder:text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition"
                    value="{{ old('code') }}"
                />
                @error('code')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full px-5 py-3 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600 transition-all">
                Verificar código
            </button>

            <button type="button" @click="useRecovery = true"
                class="w-full text-center text-xs text-gray-400 hover:text-purple-400 transition-colors">
                Usar código de recuperación
            </button>
        </form>

        {{-- Formulario recovery code --}}
        <form method="POST" action="{{ route('2fa.verify') }}" x-show="useRecovery" class="space-y-5" x-cloak>
            @csrf

            <div>
                <label for="recovery_code" class="block text-sm text-gray-300 font-medium mb-1.5">Código de recuperación</label>
                <input
                    id="recovery_code"
                    name="recovery_code"
                    type="text"
                    autocomplete="off"
                    placeholder="XXXXXXXXXX"
                    class="block w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-center text-base tracking-widest placeholder:text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition uppercase"
                />
                @error('recovery_code')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full px-5 py-3 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600 transition-all">
                Usar código de recuperación
            </button>

            <button type="button" @click="useRecovery = false"
                class="w-full text-center text-xs text-gray-400 hover:text-purple-400 transition-colors">
                Volver al código de autenticación
            </button>
        </form>

    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">
            Volver al inicio de sesión
        </a>
    </div>
</x-guest-layout>
