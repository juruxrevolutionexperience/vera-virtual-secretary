@php
    $veraConfig = auth()->user()->veraConfig ?? null;
    $weekly = $veraConfig?->weekly_suggestions ?? null;

    // No mostrar si no hay datos o si no hay sugerencias
    if (!$weekly || empty($weekly['narrative'])) {
        return;
    }

    $dismissedIds      = $weekly['dismissed_ids'] ?? [];
    $allSuggestions    = $weekly['suggestions'] ?? [];
    $activeSuggestions = array_filter($allSuggestions, fn($s) => !in_array($s['id'] ?? '', $dismissedIds));
    $confirmableInsights = $weekly['confirmable_insights'] ?? [];
@endphp

<div
    x-data="{
        suggestions: {{ json_encode(array_values($activeSuggestions)) }},
        insights: {{ json_encode(array_values($confirmableInsights)) }},
        dismissed: {{ json_encode($dismissedIds) }},
        insightMessage: '',
        dismissSuggestion(id) {
            fetch('{{ route('vera.weekly-suggestion.dismiss') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ suggestion_id: id })
            });
            this.suggestions = this.suggestions.filter(s => s.id !== id);
        },
        confirmInsight(id) {
            fetch('{{ route('vera.insight.confirm') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ insight_id: id })
            }).then(r => r.json()).then(data => {
                this.insightMessage = data.message ?? '';
                this.insights = this.insights.filter(i => i.id !== id);
            });
        },
        denyInsight(id) {
            fetch('{{ route('vera.insight.deny') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ insight_id: id })
            }).then(r => r.json()).then(data => {
                this.insightMessage = data.message ?? '';
                this.insights = this.insights.filter(i => i.id !== id);
            });
        }
    }"
    x-show="suggestions.length > 0 || insights.length > 0"
    class="bg-white border border-purple-200 rounded-lg overflow-hidden"
>
    <!-- Header -->
    <div class="px-5 py-3 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-purple-100 flex items-center gap-2">
        <div class="w-6 h-6 bg-purple-600 rounded-md flex items-center justify-center flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-xs font-semibold text-gray-900">Vera aprendió esta semana</h3>
            <p class="text-xs text-gray-500">{{ $weekly['week_label'] ?? '' }}</p>
        </div>
    </div>

    <!-- Narrative -->
    <div class="px-5 py-3 border-b border-gray-100">
        <p class="text-xs text-gray-700 leading-relaxed">{{ $weekly['narrative'] }}</p>
    </div>

    <!-- Confirmable Insights -->
    <div x-show="insights.length > 0" class="border-b border-gray-100">
        <div class="px-5 py-2 bg-indigo-50 border-b border-indigo-100 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-xs font-semibold text-indigo-700">¿Es esto correcto sobre ti?</span>
        </div>

        <!-- Feedback message -->
        <div x-show="insightMessage" x-text="insightMessage"
             class="mx-5 my-2 text-xs text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-md">
        </div>

        <div class="divide-y divide-indigo-50">
            <template x-for="insight in insights" :key="insight.id">
                <div class="px-5 py-3">
                    <p class="text-xs text-gray-700 mb-2 leading-relaxed" x-text="insight.statement"></p>
                    <div class="flex items-center gap-2">
                        <button
                            @click="confirmInsight(insight.id)"
                            class="text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded-md transition-colors flex items-center gap-1"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Correcto
                        </button>
                        <button
                            @click="denyInsight(insight.id)"
                            class="text-xs text-gray-500 hover:text-gray-700 border border-gray-200 hover:border-gray-300 px-3 py-1 rounded-md transition-colors flex items-center gap-1"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            No exactamente
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Suggestions -->
    <div class="divide-y divide-gray-100" x-show="suggestions.length > 0">
        <template x-for="suggestion in suggestions" :key="suggestion.id">
            <div class="px-5 py-3">
                <p class="text-xs font-medium text-gray-900 mb-0.5" x-text="suggestion.title"></p>
                <p class="text-xs text-gray-500 mb-2" x-text="suggestion.message"></p>
                <div class="flex items-center gap-2">
                    <a
                        :href="suggestion.action_url"
                        class="text-xs font-medium text-purple-600 hover:text-purple-800 bg-purple-50 hover:bg-purple-100 px-3 py-1 rounded-md transition-colors"
                        x-text="suggestion.action_label"
                    ></a>
                    <button
                        @click="dismissSuggestion(suggestion.id)"
                        class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1 hover:bg-gray-100 rounded-md transition-colors"
                    >
                        Ahora no
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
