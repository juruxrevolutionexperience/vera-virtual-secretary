<div class="flex flex-col h-full">
    <!-- List Header with Select All -->
    <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-3 bg-gray-50">
        <input type="checkbox" @click="selectAllEmails()"
            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer">
        <span class="text-xs text-gray-500 font-medium">Seleccionar todos</span>
    </div>

    <!-- Email List -->
    <div id="emails-container" class="flex-1 overflow-y-auto">
        @forelse($emails as $email)
            <div id="email-{{ $email->id }}"
                class="email-item border-b border-gray-100 cursor-pointer relative
                  density-comfortable
                            {{ !$email->is_read ? 'unread' : '' }}
                            {{ $email->priority === 'high' ? 'priority-high' : '' }}
                            {{ $email->priority === 'urgent' ? 'priority-urgent' : '' }}"
                onclick="handleEmailClick({{ $email->id }}, event)"
                ondblclick="openEmailFullPage({{ $email->id }})"
                data-email-id="{{ $email->id }}"
                data-email-url="{{ route('emails.show', $email) }}">

                <div class="flex items-start gap-3">
                    <!-- Checkbox -->
                    <input type="checkbox" value="{{ $email->id }}"
                        class="email-checkbox mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
                        @click.stop="toggleEmailSelection({{ $email->id }})"
                        :checked="selectedEmails.includes({{ $email->id }})">

                    <!-- Email Content -->
                    <div class="flex-1 min-w-0">
                        <!-- From & Time -->
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span
                                    class="text-sm {{ !$email->is_read ? 'font-semibold text-gray-900' : 'font-medium text-gray-700' }} truncate">
                                    {{ $email->from_name ?? $email->from_email }}
                                </span>

                                @php
                                    $categoryColors = [
                                        'primary' => '#6366f1',
                                        'promotions' => '#f59e0b',
                                        'social' => '#10b981',
                                        'updates' => '#3b82f6',
                                        'forums' => '#8b5cf6',
                                        'spam' => '#ef4444',
                                        'trash' => '#6b7280',
                                    ];
                                    $categoryIcons = [
                                        'primary' => '📧',
                                        'promotions' => '🏷️',
                                        'social' => '👥',
                                        'updates' => '🔔',
                                        'forums' => '💬',
                                        'spam' => '⚠️',
                                        'trash' => '🗑️',
                                    ];
                                    $categoryColor = $categoryColors[$email->category] ?? '#6b7280';
                                    $categoryIcon = $categoryIcons[$email->category] ?? '📧';
                                @endphp

                                <!-- Category Badge -->
                                @if($email->category)
                                    <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                                        style="background-color: {{ $categoryColor }}20; color: {{ $categoryColor }}">
                                        {{ $categoryIcon }} {{ ucfirst($email->category) }}
                                    </span>
                                @endif
                            </div>

                            <span class="text-xs text-gray-500 ml-2 flex-shrink-0">
                                {{ formatEmailDate($email->received_at) }}
                            </span>
                        </div>

                        <!-- Subject -->
                        <div class="flex items-center gap-2 mb-1">
                            <p
                                class="text-sm {{ !$email->is_read ? 'font-medium text-gray-900' : 'text-gray-600' }} truncate flex-1">
                                {{ $email->subject }}
                            </p>

                            <!-- Quick Actions -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click.stop="performAction({{ $email->id }}, 'toggle-star')"
                                    class="p-1 hover:bg-gray-200 rounded transition-colors"
                                    title="{{ $email->is_starred ? 'Quitar estrella' : 'Destacar' }}">
                                    <svg class="w-3.5 h-3.5 {{ $email->is_starred ? 'text-yellow-500 fill-yellow-500' : 'text-gray-500' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Excerpt & Tags -->
                        <div class="flex items-center gap-2">
                            <p class="text-xs text-gray-500 truncate flex-1">
                                {{ $email->excerpt }}
                            </p>
                        </div>

                        <!-- Meta Information -->
                        <div class="flex items-center gap-3 mt-2">
                            <!-- Attachments -->
                            @if($email->has_attachments)
                                <div class="flex items-center gap-1 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <span class="text-[10px]">{{ $email->attachments_count }}</span>
                                </div>
                            @endif

                            <!-- Priority Badge -->
                            @if(in_array($email->priority, ['high', 'urgent']))
                                <span
                                    class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                                            {{ $email->priority === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                    @if($email->priority === 'urgent')
                                        ⚠️ Urgente
                                    @else
                                        🔔 Alta
                                    @endif
                                </span>
                            @endif

                            <!-- Vera Processed -->
                            @if($email->vera_processed)
                                <div class="flex items-center gap-1 text-purple-600" title="Procesado por Vera">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-[10px] font-medium">Vera</span>
                                </div>
                            @endif

                            <!-- Vera Tags -->
                            @if($email->vera_tags && count($email->vera_tags) > 0)
                                @foreach(array_slice($email->vera_tags, 0, 2) as $tag)
                                    <span
                                        class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600">
                                        #{{ $tag }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Starred Indicator (always visible if starred) -->
                    @if($email->is_starred)
                        <button @click.stop="performAction({{ $email->id }}, 'toggle-star')" class="flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-yellow-500 fill-yellow-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-64 text-center px-4">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No hay emails</h3>
                <p class="text-sm text-gray-500">
                    @if(request()->has('search'))
                        No encontramos emails que coincidan con tu búsqueda.
                    @else
                        Tu bandeja de entrada está vacía.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($emails->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    Mostrando {{ $emails->firstItem() }} - {{ $emails->lastItem() }} de {{ $emails->total() }}
                </div>
                <div class="flex gap-1">
                    @if($emails->onFirstPage())
                        <button disabled class="p-1.5 rounded text-gray-300 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    @else
                        <a href="{{ $emails->previousPageUrl() }}"
                            class="p-1.5 rounded text-gray-600 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    @if($emails->hasMorePages())
                        <a href="{{ $emails->nextPageUrl() }}"
                            class="p-1.5 rounded text-gray-600 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <button disabled class="p-1.5 rounded text-gray-300 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const density = localStorage.getItem('email-density') || 'comfortable';
        document.querySelectorAll('.email-item').forEach(item => {
            item.classList.add(`density-${density}`);
        });
    });
</script>