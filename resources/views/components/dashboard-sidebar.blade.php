<aside id="sidebar" class="sidebar w-56 bg-white border-r border-gray-200 fixed top-0 bottom-0 left-0 z-50 flex-col hidden lg:flex">
    <!-- Logo -->
    <div class="h-14 flex-shrink-0 flex items-center justify-between px-4 border-b border-gray-200">
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
    <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-4 pb-20 text-sm">
        <!-- Dashboard Section -->
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('dashboard') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-medium">Inicio</span>
        </a>

        <!-- Separator -->
        <div class="h-px bg-gray-200 my-3"></div>

        <!-- Vera Agenda Section -->
        <div class="mb-4">
            <div class="px-2 mb-1">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Vera Agenda</p>
            </div>

            <a href="{{ route('vera.dashboard') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.dashboard') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="flex-1">Bandeja</span>
            </a>

            <a href="{{ route('contacts.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('contacts.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="flex-1">Contactos</span>
            </a>

            <a href="{{ route('vera.chat.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.chat.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="flex-1">Chat Vera</span>
            </a>

            <a href="{{ route('vera.calendar.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.calendar.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="flex-1">Calendar</span>
                @php
                    try {
                        if (!isset($todayCount) && auth()->check()) {
                            $cacheKey = 'calendar_today_count_' . auth()->id();
                            $todayCount = \Illuminate\Support\Facades\Cache::remember(
                                $cacheKey, 300,
                                fn() => (new \App\Services\GoogleCalendarService())->getTodayEventCount(auth()->user())
                            );
                        } else {
                            $todayCount = $todayCount ?? 0;
                        }
                    } catch (\Exception) { $todayCount = 0; }
                @endphp
                @if($todayCount > 0)
                <span class="text-xs bg-purple-600 text-white px-1.5 py-0.5 rounded-full font-medium leading-none">{{ $todayCount }}</span>
                @endif
            </a>

            <a href="{{ route('vera.activity.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.activity.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="flex-1">Actividad</span>
            </a>

            <a href="{{ route('vera.analytics.index') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.analytics.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="flex-1">Analytics</span>
            </a>

            <a href="{{ route('vera.channels.telegram') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.channels.*') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
                <span class="flex-1">Canales</span>
                @php
                    try {
                        $telegramConnected = isset($telegramConnected)
                            ? $telegramConnected
                            : (auth()->check()
                                ? \App\Models\VeraConfig::where('user_id', auth()->id())->value('telegram_bot_token') !== null
                                : false);
                    } catch (\Exception) { $telegramConnected = false; }
                @endphp
                @if($telegramConnected)
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                @endif
            </a>

            <a href="{{ route('vera.channels.whatsapp') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.channels.whatsapp') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/>
                </svg>
                <span class="flex-1">WhatsApp</span>
                @php
                    try {
                        $waConnected = auth()->check()
                            ? \App\Models\VeraConfig::where('user_id', auth()->id())->value('whatsapp_phone_number_id') !== null
                            : false;
                    } catch (\Exception) { $waConnected = false; }
                @endphp
                @if($waConnected)
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                @endif
            </a>

            <a href="{{ route('vera.config') }}"
                class="flex items-center gap-2 px-2 py-1.5 {{ request()->routeIs('vera.config') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} rounded-md mb-1 group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="flex-1">Configuración</span>
            </a>
        </div>

        <!-- Separator -->
        <div class="h-px bg-gray-200 my-3"></div>

        <!-- Reportar a Vera -->
        <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-feedback')); if (window.innerWidth < 1024) toggleSidebar();"
            class="w-full flex items-center gap-2 px-2 py-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md mb-1">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
            <span class="flex-1 text-left font-medium">Reportar</span>
        </button>

    </nav>

    <!-- Footer - User Menu -->
    <div class="absolute bottom-0 left-0 right-0 px-3 py-3 border-t border-gray-200 bg-white relative" x-data="{ userMenuOpen: false }">
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
                    <span class="ml-auto text-xs text-gray-400">⌘+Ctrl+,</span>
                </a>

                <!-- Idioma -->
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                    </svg>
                    <span>Idioma</span>
                    <svg class="w-3 h-3 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <!-- Obtener ayuda -->
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Obtener ayuda</span>
                    <svg class="w-3 h-3 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <div class="h-px bg-gray-200 my-1"></div>

                <!-- Mejorar plan -->
                <a href="{{ route('billing') }}"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Mejorar plan</span>
                </a>

                <!-- Descargar Vera para Windows -->
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Descargar Vera para Windows...</span>
                </a>

                <!-- Más información -->
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Más información</span>
                    <svg class="w-3 h-3 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                @if(in_array(auth()->user()->email, config('app.super_admins', []), true))
                    <div class="h-px bg-gray-200 my-1"></div>

                    <!-- Admin: Feedbacks (solo super admins) -->
                    <a href="{{ route('admin.feedbacks.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Admin: Feedbacks</span>
                        <span class="ml-auto text-[10px] font-semibold tracking-wide text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">DEV</span>
                    </a>
                @endif

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

{{-- Botón hamburger — solo visible en móvil y tablet (< lg) --}}
<button id="mobile-menu-btn" onclick="toggleSidebar()"
    class="lg:hidden fixed top-3 left-3 z-40 p-2 bg-white border border-gray-200 rounded-md shadow-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

{{-- Overlay oscuro al abrir sidebar en móvil/tablet --}}
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const isOpen  = !sidebar.classList.contains('hidden');

    if (isOpen) {
        sidebar.classList.add('hidden');
        sidebar.classList.remove('flex');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('flex');
        overlay.classList.remove('hidden');
    }
}
</script>

{{-- Dev Toolbar flotante (solo visible para usuarios is_dev) --}}
@include('components.dev-toolbar')

{{-- Widget "Reportar a Vera" (botón flotante visible para todos los users auth) --}}
@include('components.feedback-widget')
