<x-guest-layout>
    <!-- Title -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white mb-2">Bienvenido de nuevo</h2>
        <p class="text-sm text-gray-400">Inicia sesión en tu cuenta</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm text-gray-300 font-medium" />
            <x-text-input id="email"
                class="input-dark block mt-1.5 w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm text-gray-300 font-medium" />
            <div class="relative mt-1.5">
                <x-text-input id="password"
                    class="input-dark block w-full px-3 py-2.5 rounded-lg text-sm text-white placeholder:text-gray-500 pr-10"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password" />
                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-white transition-colors">
                    <svg id="eye-show" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-hide" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <script>
        function togglePassword(){var i=document.getElementById('password'),s=document.getElementById('eye-show'),h=document.getElementById('eye-hide');if(i.type==='password'){i.type='text';s.classList.add('hidden');h.classList.remove('hidden');}else{i.type='password';s.classList.remove('hidden');h.classList.add('hidden');}}
        </script>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-600 bg-gray-700 text-[#667eea] shadow-sm focus:ring-[#667eea] focus:ring-offset-gray-800"
                    name="remember">
                <span class="ms-2 text-xs text-gray-400">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs text-[#667eea] hover:text-[#764ba2] transition-colors"
                   href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="space-y-3 pt-2">
            <button type="submit" class="btn-primary w-full px-5 py-3 rounded-xl font-medium text-sm text-center text-white">
                {{ __('Log in') }}
            </button>

            <!-- Register Link -->
            <p class="text-center text-gray-400 text-xs">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="text-[#667eea] hover:text-[#764ba2] font-medium transition-colors">
                    Regístrate gratis
                </a>
            </p>
        </div>
    </form>

    <!-- Divider -->
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-white/10"></div>
        </div>
        <div class="relative flex justify-center text-xs">
            <span class="px-3 bg-[#0d0d0d] text-gray-500">O continúa con</span>
        </div>
    </div>

    <!-- OAuth Buttons -->
    <a href="{{ route('auth.google') }}" class="oauth-button flex items-center justify-center w-full px-4 py-2.5 rounded-lg font-medium text-xs">
        <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google
    </a>
</x-guest-layout>
