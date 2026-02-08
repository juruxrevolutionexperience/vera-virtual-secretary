<nav x-data="{ open: false }" class="bg-white shadow-sm border-b border-gray-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center glow-violet">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text-violet">Virtual Employee</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:ms-10 sm:flex sm:items-center">
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-purple-600 transition-colors duration-200 {{ request()->routeIs('dashboard') ? 'text-purple-600 font-semibold' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('billing') }}" class="text-gray-600 hover:text-purple-600 transition-colors duration-200 {{ request()->routeIs('billing') ? 'text-purple-600 font-semibold' : '' }}">
                        Suscripciones
                    </a>
                    <a href="{{ route('security.sessions.index') }}" class="text-gray-600 hover:text-purple-600 transition-colors duration-200 {{ request()->routeIs('security.sessions.*') ? 'text-purple-600 font-semibold' : '' }}">
                        🔒 Seguridad
                    </a>
                </div>
            </div>
@auth
            <!-- User Info & Logout -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                <div class="flex items-center space-x-2 text-gray-700">
                    <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    </div>
                    <span class="font-medium">{{ Auth::user()->name }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg font-medium text-red-600 hover:text-white hover:bg-red-600 border border-red-300 hover:border-red-600 focus:outline-none transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <a href="{{ route('dashboard') }}" class="block px-4 py-3 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 transition-colors duration-200 {{ request()->routeIs('dashboard') ? 'bg-purple-50 text-purple-600 font-semibold' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('billing') }}" class="block px-4 py-3 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 transition-colors duration-200 {{ request()->routeIs('billing') ? 'bg-purple-50 text-purple-600 font-semibold' : '' }}">
                Suscripciones
            </a>
            <a href="{{ route('security.sessions.index') }}" class="block px-4 py-3 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 transition-colors duration-200 {{ request()->routeIs('security.sessions.*') ? 'bg-purple-50 text-purple-600 font-semibold' : '' }}">
                🔒 Seguridad
            </a>
        </div>

        <!-- Responsive User Info -->
        <div class="pt-4 pb-3 border-t border-gray-200 px-4">
            <div class="px-4 mb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-semibold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <div class="font-medium text-base text-gray-900">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <!-- Authentication -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="w-full flex items-center space-x-2 px-4 py-3 rounded-lg text-red-600 hover:text-white hover:bg-red-600 transition-colors duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Cerrar Sesión</span>
                </button>
            </form>
        </div>
        @endauth
    </div>
</nav>
