<div class="flex flex-col h-full">
    @if($emails->count() > 0)
        <div class="flex-1 flex flex-col items-center justify-center text-center px-8">
            <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Selecciona un email</h3>
            <p class="text-sm text-gray-500 max-w-sm">
                Haz clic en cualquier email de la lista para ver su contenido completo aquí.
            </p>
            <p class="text-xs text-gray-400 mt-4">
                💡 <strong>Tip:</strong> También puedes usar las teclas ↑ ↓ para navegar
            </p>
        </div>
    @else
        <div class="flex-1 flex flex-col items-center justify-center text-center px-8">
            <div
                class="w-20 h-20 bg-gradient-to-br from-purple-100 to-purple-200 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">¡Todo listo!</h3>
            <p class="text-sm text-gray-500 max-w-sm mb-6">
                No tienes emails en esta vista. Vera está trabajando para mantener tu bandeja organizada.
            </p>

            <!-- Quick Actions -->
            <div class="flex flex-col gap-2 w-full max-w-xs">
                <a href="{{ route('emails.index') }}"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md transition-colors">
                    Ver todos los emails
                </a>
                <button
                    class="px-4 py-2 border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md transition-colors">
                    Configurar Vera
                </button>
            </div>
        </div>
    @endif
</div>
