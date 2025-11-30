<!-- Sidebar Component -->
<aside
    x-data="{
        open: false,
        virtualSecretaryOpen: false,
        currentRoute: '{{ request()->routeIs('virtual-secretary.*') ? 'virtual-secretary' : (request()->routeIs('dashboard') ? 'dashboard' : '') }}'
    }"
    @mouseenter="open = true"
    @mouseleave="open = false; virtualSecretaryOpen = false"
    :class="open ? 'w-64' : 'w-20'"
    class="sidebar fixed left-0 top-0 h-screen glass-effect border-r border-white/10 transition-all duration-300 ease-in-out z-40 overflow-hidden"
>
    <!-- Logo Section -->
    <div class="flex items-center justify-center h-20 border-b border-white/10">
        <div class="flex items-center space-x-3 px-4">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center glow-violet flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span x-show="open" x-transition class="text-lg font-bold gradient-text-violet whitespace-nowrap">Virtual Employee</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="mt-8 px-3">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
           class="sidebar-item group {{ request()->routeIs('dashboard') && !request()->routeIs('virtual-secretary.*') ? 'active' : '' }}"
           x-data="{ tooltip: false }"
           @mouseenter="if(!open) tooltip = true"
           @mouseleave="tooltip = false">
            <div class="flex items-center space-x-3 relative">
                <div class="sidebar-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <span x-show="open" x-transition class="sidebar-text">Dashboard</span>

                <!-- Tooltip cuando está cerrado -->
                <div x-show="tooltip && !open"
                     x-transition
                     class="absolute left-full ml-6 px-3 py-2 bg-slate-800 text-white text-sm rounded-lg whitespace-nowrap z-50">
                    Dashboard
                </div>
            </div>
        </a>

        <!-- Virtual Secretary (con submenú) -->
        <div class="relative">
            <button @click="virtualSecretaryOpen = !virtualSecretaryOpen"
                    class="sidebar-item group w-full {{ request()->routeIs('virtual-secretary.*') ? 'active' : '' }}"
                    x-data="{ tooltip: false }"
                    @mouseenter="if(!open) tooltip = true"
                    @mouseleave="tooltip = false">
                <div class="flex items-center justify-between relative">
                    <div class="flex items-center space-x-3">
                        <div class="sidebar-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span x-show="open" x-transition class="sidebar-text">Virtual Secretary</span>
                    </div>
                    <svg x-show="open"
                         :class="virtualSecretaryOpen ? 'rotate-180' : ''"
                         class="w-4 h-4 transition-transform duration-200"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>

                    <!-- Tooltip cuando está cerrado -->
                    <div x-show="tooltip && !open"
                         x-transition
                         class="absolute left-full ml-6 px-3 py-2 bg-slate-800 text-white text-sm rounded-lg whitespace-nowrap z-50">
                        Virtual Secretary
                    </div>
                </div>
            </button>

            <!-- Submenú de Virtual Secretary -->
            <div x-show="virtualSecretaryOpen && open"
                 x-transition
                 class="ml-4 mt-2 space-y-1 border-l-2 border-violet-500/30 pl-4">

                <a href="{{ route('virtual-secretary.dashboard') }}"
                   class="sidebar-subitem {{ request()->routeIs('virtual-secretary.dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('virtual-secretary.calendar') }}"
                   class="sidebar-subitem {{ request()->routeIs('virtual-secretary.calendar') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Calendario</span>
                </a>

                <a href="{{ route('virtual-secretary.emails') }}"
                   class="sidebar-subitem {{ request()->routeIs('virtual-secretary.emails') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Emails</span>
                </a>

                <a href="{{ route('virtual-secretary.calls') }}"
                   class="sidebar-subitem {{ request()->routeIs('virtual-secretary.calls') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>Llamadas</span>
                </a>
            </div>
        </div>

        <!-- Settings -->
        <a href="{{ route('profile.edit') }}"
           class="sidebar-item group {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
           x-data="{ tooltip: false }"
           @mouseenter="if(!open) tooltip = true"
           @mouseleave="tooltip = false">
            <div class="flex items-center space-x-3 relative">
                <div class="sidebar-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span x-show="open" x-transition class="sidebar-text">Settings</span>

                <!-- Tooltip -->
                <div x-show="tooltip && !open"
                     x-transition
                     class="absolute left-full ml-6 px-3 py-2 bg-slate-800 text-white text-sm rounded-lg whitespace-nowrap z-50">
                    Settings
                </div>
            </div>
        </a>

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="sidebar-item group w-full"
                    x-data="{ tooltip: false }"
                    @mouseenter="if(!open) tooltip = true"
                    @mouseleave="tooltip = false">
                <div class="flex items-center space-x-3 relative">
                    <div class="sidebar-icon text-red-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <span x-show="open" x-transition class="sidebar-text text-red-400">Cerrar Sesión</span>

                    <!-- Tooltip -->
                    <div x-show="tooltip && !open"
                         x-transition
                         class="absolute left-full ml-6 px-3 py-2 bg-slate-800 text-white text-sm rounded-lg whitespace-nowrap z-50">
                        Cerrar Sesión
                    </div>
                </div>
            </button>
        </form>
    </nav>
</aside>

<style>
/* Estilos del Sidebar */
.sidebar-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    margin-bottom: 8px;
    border-radius: 12px;
    color: #9ca3af;
    transition: all 0.2s ease;
    cursor: pointer;
    text-align: left;
}

.sidebar-item:hover {
    background: rgba(139, 92, 246, 0.1);
    color: #fff;
}

.sidebar-item.active {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(124, 58, 237, 0.2));
    color: #fff;
    border-left: 3px solid #8b5cf6;
}

.sidebar-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
}

.sidebar-text {
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

.sidebar-subitem {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 4px;
    border-radius: 8px;
    color: #9ca3af;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    gap: 8px;
}

.sidebar-subitem:hover {
    background: rgba(139, 92, 246, 0.1);
    color: #fff;
}

.sidebar-subitem.active {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
}
</style>
