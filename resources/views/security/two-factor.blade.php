@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('security.dashboard') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Autenticación en Dos Factores (2FA)</h1>
            <p class="text-sm text-gray-500 mt-0.5">Añade una capa extra de seguridad a tu cuenta</p>
        </div>
    </div>

    <div x-data="twoFactorApp()" x-init="init()" class="space-y-6">

        {{-- Estado actual --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                         :class="enabled ? 'bg-green-100' : 'bg-gray-100'">
                        <svg class="w-6 h-6" :class="enabled ? 'text-green-600' : 'text-gray-400'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900" x-text="enabled ? '2FA Activado' : '2FA Desactivado'"></p>
                        <p class="text-sm text-gray-500" x-text="enabled ? 'Tu cuenta está protegida con 2FA.' : 'Activa 2FA para mayor seguridad.'"></p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold"
                      :class="enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                      x-text="enabled ? 'Activo' : 'Inactivo'">
                </span>
            </div>
        </div>

        {{-- SECCIÓN: Activar 2FA --}}
        <div x-show="!enabled" class="space-y-4">

            {{-- Paso 1: Generar QR --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-purple-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                    Escanea el código QR
                </h2>
                <p class="text-sm text-gray-600 mb-5">
                    Abre <strong>Google Authenticator</strong> o <strong>Authy</strong> en tu móvil y escanea este código QR.
                </p>

                {{-- Área del QR --}}
                <div class="flex flex-col items-center gap-4">
                    <div class="w-52 h-52 bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl flex items-center justify-center overflow-hidden"
                         x-show="!qrSvg && !loadingQr">
                        <div class="text-center text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <p class="text-xs">Genera el QR</p>
                        </div>
                    </div>
                    <div x-show="loadingQr" class="w-52 h-52 flex items-center justify-center">
                        <svg class="animate-spin w-8 h-8 text-purple-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                    <div x-show="qrSvg" x-html="qrSvg"
                         class="w-52 h-52 flex items-center justify-center bg-white border border-gray-200 rounded-2xl p-2"></div>

                    <button @click="generateQr()"
                            :disabled="loadingQr"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-xl transition-all disabled:opacity-50">
                        <span x-show="!qrSvg">Generar código QR</span>
                        <span x-show="qrSvg">Regenerar QR</span>
                    </button>

                    {{-- Clave manual --}}
                    <div x-show="secretKey" class="w-full bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-gray-500 mb-1">O introduce esta clave manualmente:</p>
                        <p class="font-mono text-sm font-bold text-gray-800 tracking-widest" x-text="secretKey"></p>
                    </div>
                </div>
            </div>

            {{-- Paso 2: Verificar código --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6" x-show="qrSvg">
                <h2 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-purple-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                    Confirma con un código
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Introduce el código de 6 dígitos que muestra tu app de autenticación para confirmar la configuración.
                </p>
                <div class="flex gap-3">
                    <input
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        x-model="otpCode"
                        @keydown.enter="enableTwoFactor()"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-900 text-center text-xl tracking-[0.4em] placeholder:text-gray-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none"
                    />
                    <button @click="enableTwoFactor()"
                            :disabled="loadingEnable || otpCode.length < 6"
                            class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-all disabled:opacity-50">
                        <span x-show="!loadingEnable">Activar 2FA</span>
                        <span x-show="loadingEnable">Verificando...</span>
                    </button>
                </div>
                <p x-show="errorMsg" class="mt-2 text-sm text-red-500" x-text="errorMsg"></p>
            </div>
        </div>

        {{-- SECCIÓN: 2FA activo — Recovery codes + desactivar --}}
        <div x-show="enabled" class="space-y-4">

            {{-- Recovery codes --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Códigos de recuperación</h2>
                    <button @click="showRecoveryCodes = !showRecoveryCodes"
                            class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                        <span x-text="showRecoveryCodes ? 'Ocultar' : 'Ver códigos'"></span>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Guarda estos códigos en un lugar seguro. Los puedes usar para acceder si pierdes tu dispositivo.
                    <strong>Cada código solo se puede usar una vez.</strong>
                </p>

                {{-- Lista de códigos --}}
                <div x-show="showRecoveryCodes" class="grid grid-cols-2 gap-2 mb-4">
                    @if($recoveryCodes)
                        @foreach($recoveryCodes as $code)
                            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 font-mono text-sm text-center text-gray-800 tracking-widest">
                                {{ $code }}
                            </div>
                        @endforeach
                    @else
                        <template x-for="code in recoveryCodes" :key="code">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 font-mono text-sm text-center text-gray-800 tracking-widest"
                                 x-text="code"></div>
                        </template>
                    @endif
                </div>

                {{-- Regenerar --}}
                <div x-show="showRecoveryCodes" class="flex gap-3">
                    <input type="password" placeholder="Contraseña actual para confirmar"
                           x-model="passwordForRegen"
                           class="flex-1 px-3 py-2 rounded-xl border border-gray-300 text-gray-900 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none"
                    />
                    <button @click="regenerateCodes()"
                            :disabled="!passwordForRegen"
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm rounded-xl transition-all disabled:opacity-50">
                        Regenerar
                    </button>
                </div>
            </div>

            {{-- Desactivar 2FA --}}
            <div class="bg-white rounded-2xl border border-red-100 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Desactivar 2FA</h2>
                <p class="text-sm text-gray-500 mb-4">Al desactivar, tu cuenta quedará menos protegida. Se requiere tu contraseña.</p>
                <div class="flex gap-3">
                    <input type="password" placeholder="Contraseña actual"
                           x-model="passwordForDisable"
                           class="flex-1 px-3 py-2.5 rounded-xl border border-gray-300 text-gray-900 text-sm focus:border-red-400 focus:ring-1 focus:ring-red-400 outline-none"
                    />
                    <button @click="disableTwoFactor()"
                            :disabled="!passwordForDisable || loadingDisable"
                            class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-all disabled:opacity-50">
                        <span x-show="!loadingDisable">Desactivar</span>
                        <span x-show="loadingDisable">Procesando...</span>
                    </button>
                </div>
                <p x-show="disableError" class="mt-2 text-sm text-red-500" x-text="disableError"></p>
            </div>
        </div>

        {{-- Mensajes de éxito globales --}}
        <div x-show="successMsg"
             class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span x-text="successMsg"></span>
        </div>

    </div>
</div>

<script>
function twoFactorApp() {
    return {
        enabled: @json($enabled),
        confirmed: @json($confirmed),
        qrSvg: null,
        secretKey: null,
        otpCode: '',
        recoveryCodes: @json($recoveryCodes ?? []),
        showRecoveryCodes: false,
        loadingQr: false,
        loadingEnable: false,
        loadingDisable: false,
        errorMsg: '',
        disableError: '',
        successMsg: '',
        passwordForDisable: '',
        passwordForRegen: '',

        init() {
            // Si ya tiene 2FA y recovery codes, mostrarlos
        },

        async generateQr() {
            this.loadingQr = true;
            this.errorMsg = '';
            try {
                const res = await fetch('{{ route('security.2fa.generate-qr') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                this.qrSvg = data.qr_svg;
                this.secretKey = data.secret_key;
            } catch (e) {
                this.errorMsg = 'Error al generar el QR. Inténtalo de nuevo.';
            } finally {
                this.loadingQr = false;
            }
        },

        async enableTwoFactor() {
            if (this.otpCode.length < 6) return;
            this.loadingEnable = true;
            this.errorMsg = '';
            try {
                const res = await fetch('{{ route('security.2fa.enable') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ code: this.otpCode })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.errorMsg = data.message || 'Código inválido.';
                    return;
                }
                this.enabled = true;
                this.recoveryCodes = data.recovery_codes;
                this.showRecoveryCodes = true;
                this.successMsg = data.message;
                this.qrSvg = null;
                this.secretKey = null;
                this.otpCode = '';
            } catch (e) {
                this.errorMsg = 'Error al activar. Inténtalo de nuevo.';
            } finally {
                this.loadingEnable = false;
            }
        },

        async disableTwoFactor() {
            if (!this.passwordForDisable) return;
            this.loadingDisable = true;
            this.disableError = '';
            try {
                const res = await fetch('{{ route('security.2fa.disable') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ password: this.passwordForDisable })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.disableError = data.message || 'Error al desactivar.';
                    return;
                }
                this.enabled = false;
                this.successMsg = data.message;
                this.passwordForDisable = '';
            } catch (e) {
                this.disableError = 'Error al desactivar. Inténtalo de nuevo.';
            } finally {
                this.loadingDisable = false;
            }
        },

        async regenerateCodes() {
            if (!this.passwordForRegen) return;
            try {
                const res = await fetch('{{ route('security.2fa.regenerate-codes') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ password: this.passwordForRegen })
                });
                const data = await res.json();
                if (!res.ok) {
                    alert(data.message || 'Error al regenerar.');
                    return;
                }
                this.recoveryCodes = data.recovery_codes;
                this.successMsg = data.message;
                this.passwordForRegen = '';
            } catch (e) {
                alert('Error al regenerar.');
            }
        }
    }
}
</script>
@endsection
