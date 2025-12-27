<aside id="sidebar" class="sidebar w-56 bg-white border-r border-gray-200 fixed h-full z-50 flex flex-col">
    <!-- Logo -->
    <div class="h-14 flex items-center justify-between px-4 border-b border-gray-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
            <div
                class="w-6 h-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-md flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-white font-bold text-sm">V</span>
            </div>
            <span class="text-gray-900 font-semibold text-sm">Vera</span>
        </a>
        <button onclick="toggleSidebar()"
            class="md:hidden text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm">
        <!-- Dashboard Section -->
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('dashboard') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 tooltip">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-medium">Inicio</span>
            <span class="tooltip-text">Dashboard principal</span>
        </a>

        <!-- Separator -->
        <div class="h-px bg-gray-200 my-3"></div>

        <!-- Gestión Section -->
        <div class="mb-4">
            <div class="px-2 mb-1">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Gestión</p>
            </div>

            <a href="{{ route('emails.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('emails.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group tooltip">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="flex-1">Emails</span>
                <span class="tooltip-text">Gestión de correos</span>
            </a>

            <a href="#"
                class="flex items-center gap-2 px-2 py-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md mb-1 group tooltip">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="flex-1">Calendario</span>
                <span class="tooltip-text">Eventos y reuniones</span>
            </a>

            <a href="#"
                class="flex items-center gap-2 px-2 py-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md group tooltip">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="flex-1">Llamadas</span>
                <span class="tooltip-text">Gestión telefónica</span>
            </a>
        </div>

        <!-- Vera AI Section -->
        <div class="mb-4">
            <div class="px-2 mb-1">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Vera AI</p>
            </div>

            <a href="#"
                class="flex items-center gap-2 px-2 py-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md mb-1 group tooltip">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="flex-1">Chat</span>
                <span class="tooltip-text">Conversa con Vera</span>
            </a>

            <a href="{{ route('vera.activity.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.activity.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group tooltip">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="flex-1">Actividad</span>
                <span class="tooltip-text">Registro de acciones de Vera</span>
            </a>
        </div>

        <!-- Separator -->
        <div class="h-px bg-gray-200 my-3"></div>

    </nav>

    <!-- Footer - User Menu -->
    <div class="px-3 py-3 border-t border-gray-200 relative" x-data="{ userMenuOpen: false }">
        <!-- User Dropdown Menu (appears above button) -->
        <div x-show="userMenuOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2" @click.away="userMenuOpen = false"
            class="absolute bottom-full left-0 right-0 mb-2 mx-3 bg-white rounded-lg shadow-2xl border border-gray-200 overflow-hidden z-50"
            style="display: none;">

            <!-- User Info Header -->
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->email }}</p>
            </div>

            <!-- Menu Items -->
            <div class="py-1">
                <!-- Ajustes -->
                <a href="#" @click.prevent="window.dispatchEvent(new CustomEvent('open-settings-modal'))"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors relative">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Ajustes</span>
                </a>

                <a href="{{ route('billing') }}"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Mejorar plan</span>
                </a>

                <div class="h-px bg-gray-200 my-1"></div>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors text-left">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- User Button -->
        <button @click="userMenuOpen = !userMenuOpen"
            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-gray-50 group relative">
            <div
                class="w-7 h-7 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-semibold text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-xs font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <div class="flex items-center gap-1">
                    <span class="text-[10px] text-gray-500">Plan {{ ucfirst(auth()->user()->plan_type ?? 'basic') }}</span>
                </div>
            </div>
            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="userMenuOpen ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>
    </div>
</aside>
