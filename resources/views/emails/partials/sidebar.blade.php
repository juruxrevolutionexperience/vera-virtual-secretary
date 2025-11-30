<aside id="sidebar" class="sidebar w-56 bg-white border-r border-gray-200 fixed h-full z-50 flex flex-col">
    <!-- Logo -->
    <div class="h-14 flex items-center justify-between px-4 border-b border-gray-200">
        <a href="/" class="flex items-center gap-2 group">
            <div
                class="w-6 h-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-md flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-white font-bold text-sm">V</span>
            </div>
            <span class="text-gray-900 font-semibold text-sm">Vera</span>
        </a>
        <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm">
        <!-- Back to Dashboard -->
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-2 px-2 py-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Volver al Dashboard</span>
        </a>

        <div class="h-px bg-gray-200 my-3"></div>

        <!-- Compose Button -->
        <button
            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md mb-4 font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
            <span>Nuevo Email</span>
        </button>

        <!-- Filters Section -->
        <div class="mb-4">
            <div class="px-2 mb-1">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Filtros</p>
            </div>

            <a href="{{ route('emails.index') }}"
                class="flex items-center justify-between px-2 py-1.5 rounded-md mb-1 {{ !request()->has('filter') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <span>Todos</span>
                </div>
                <span class="text-xs text-gray-400 font-medium">{{ $stats['total'] }}</span>
            </a>

            <a href="{{ route('emails.index', ['filter' => 'unread']) }}"
                class="flex items-center justify-between px-2 py-1.5 rounded-md mb-1 {{ request()->filter === 'unread' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                    </svg>
                    <span>No leídos</span>
                </div>
                @if($stats['unread'] > 0)
                    <span class="text-xs text-white bg-purple-600 px-1.5 py-0.5 rounded-full font-medium">{{ $stats['unread'] }}</span>
                @endif
            </a>

            <a href="{{ route('emails.index', ['filter' => 'starred']) }}"
                class="flex items-center justify-between px-2 py-1.5 rounded-md mb-1 {{ request()->filter === 'starred' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <span>Destacados</span>
                </div>
                @if($stats['starred'] > 0)
                    <span class="text-xs text-gray-400 font-medium">{{ $stats['starred'] }}</span>
                @endif
            </a>

            <a href="{{ route('emails.index', ['filter' => 'urgent']) }}"
                class="flex items-center justify-between px-2 py-1.5 rounded-md {{ request()->filter === 'urgent' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Urgentes</span>
                </div>
                @if($stats['urgent'] > 0)
                    <span class="text-xs text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full font-medium">{{ $stats['urgent'] }}</span>
                @endif
            </a>
        </div>

        <div class="h-px bg-gray-200 my-3"></div>

        <!-- Categories Section -->
        <div class="mb-4">
            <div class="px-2 mb-1">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Categorías</p>
            </div>

            @foreach($categories as $category)
                <a href="{{ route('emails.index', ['category' => $category->id]) }}"
                    class="category-badge flex items-center justify-between px-2 py-1.5 rounded-md mb-1 {{ request()->category == $category->id ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">{{ $category->icon }}</span>
                        <span class="truncate">{{ $category->name }}</span>
                    </div>
                    @if($category->unread_count > 0)
                        <span class="text-xs text-gray-400 font-medium">{{ $category->unread_count }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>

    <!-- Footer - User Menu -->
    <div class="px-3 py-3 border-t border-gray-200 relative" x-data="{ userMenuOpen: false }">
        <!-- User Button -->
        <button @click="userMenuOpen = !userMenuOpen"
            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-gray-50 group relative">
            <div
                class="w-7 h-7 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-semibold text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-xs font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
            </div>
        </button>
    </div>
</aside>
