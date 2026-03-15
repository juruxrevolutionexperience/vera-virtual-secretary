<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $email->subject }} - Vera AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }

        .email-body {
            line-height: 1.6;
        }

        .email-body img {
            max-width: 100%;
            height: auto;
        }

        .email-body a {
            color: #3b82f6;
            text-decoration: underline;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased bg-[#fafafa] font-['Inter'] text-[#1f2937]">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('emails.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 md:ml-56">
            <!-- Top Bar -->
            <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Botón Volver -->
                        <a href="{{ route('emails.index') }}"
                           class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver a emails
                        </a>

                        <!-- Acciones -->
                        <div class="flex items-center space-x-2" x-data>
                            <!-- Responder -->
                            <button
                                @click="$dispatch('open-reply-modal', {
                                    emailId: {{ $email->id }},
                                    emailAccountId: {{ $email->email_account_id }},
                                    subject: {{ Js::from('Re: ' . $email->subject) }},
                                    toEmail: {{ Js::from($email->from_email) }},
                                    toName: {{ Js::from($email->from_name ?? '') }},
                                    originalSubject: {{ Js::from($email->subject) }}
                                })"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                Responder
                            </button>

                            <!-- Destacar -->
                            <button onclick="toggleStar({{ $email->id }})"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                    title="{{ $email->is_starred ? 'Quitar destacado' : 'Destacar' }}">
                                <svg class="w-5 h-5 {{ $email->is_starred ? 'text-yellow-500 fill-current' : 'text-gray-400' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>

                            <!-- Archivar -->
                            <button onclick="archiveEmail({{ $email->id }})"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                    title="Archivar">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                            </button>

                            <!-- Eliminar -->
                            <button onclick="deleteEmail({{ $email->id }})"
                                    class="p-2 rounded-lg hover:bg-red-50 transition-colors"
                                    title="Eliminar">
                                <svg class="w-5 h-5 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Email Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
                    <!-- Asunto -->
                    <h1 class="text-2xl font-semibold text-gray-900 mb-4">
                        {{ $email->subject }}
                    </h1>

                    <!-- Info del remitente -->
                    <div class="flex items-start justify-between border-b border-gray-200 pb-4 mb-4">
                        <div class="flex items-start space-x-3">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-lg">
                                    {{ strtoupper(substr($email->from_name ?: $email->from_email, 0, 1)) }}
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-gray-900">
                                        {{ $email->from_name ?: $email->from_email }}
                                    </span>
                                    @if($email->category)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($email->category === 'primary') bg-blue-100 text-blue-800
                                            @elseif($email->category === 'promotions') bg-purple-100 text-purple-800
                                            @elseif($email->category === 'social') bg-green-100 text-green-800
                                            @elseif($email->category === 'updates') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($email->category) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span>{{ $email->from_email }}</span>
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    Para: <span class="text-gray-700">{{ $email->to_email }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div class="text-sm text-gray-500 whitespace-nowrap ml-4">
                            {{ formatEmailDate($email->received_at) }}
                        </div>
                    </div>

                    <!-- Contenido del email (AISLADO EN IFRAME) -->
                    <div class="email-body prose max-w-none">
                        @if($email->body_html)
                            <!-- Iframe para aislar el HTML del email y evitar que afecte al resto de la app -->
                            <iframe
                                id="email-iframe"
                                sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                                class="w-full border-0 min-h-[500px]"
                                style="background: white;">
                            </iframe>
                            <script>
                                // Inyectar contenido HTML del email en el iframe de forma segura
                                (function() {
                                    const iframe = document.getElementById('email-iframe');
                                    const emailContent = @json($email->body_html);

                                    iframe.onload = function() {
                                        const doc = iframe.contentDocument || iframe.contentWindow.document;
                                        doc.open();
                                        doc.write(emailContent);
                                        doc.close();

                                        // Auto-resize
                                        setTimeout(() => {
                                            iframe.style.height = (doc.documentElement.scrollHeight + 20) + 'px';
                                        }, 200);
                                    };

                                    // Trigger onload
                                    iframe.src = 'about:blank';
                                })();
                            </script>
                        @elseif($email->body_text)
                            <div class="whitespace-pre-wrap">{{ $email->body_text }}</div>
                        @else
                            <p class="text-gray-500 italic">Este email no tiene contenido.</p>
                        @endif
                    </div>

                    <!-- Adjuntos (si existen) -->
                    @if($email->attachments && $email->attachments->count() > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                                Adjuntos ({{ $email->attachments->count() }})
                            </h3>
                            <div class="space-y-2">
                                @foreach($email->attachments as $attachment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $attachment->filename }}</p>
                                                <p class="text-xs text-gray-500">{{ number_format($attachment->size / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('attachments.download', $attachment) }}"
                                           class="text-sm text-indigo-600 hover:text-indigo-800">
                                            Descargar
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @php
                    $pendingAction = $email->veraActions
                        ? $email->veraActions->first(fn($a) => $a->action_type === 'replied' && $a->user_feedback === 'pending' && !$a->response_sent)
                        : null;
                    $sentAction = $email->veraActions
                        ? $email->veraActions->first(fn($a) => $a->action_type === 'replied' && $a->response_sent)
                        : null;
                @endphp

                {{-- Bloque: Vera tiene respuesta pendiente de aprobación --}}
                @if($pendingAction)
                    <div x-data="{
                            responseText: @js($pendingAction->generated_response),
                            originalText: @js($pendingAction->generated_response),
                            sending: false,
                            sent: false,
                            dismissed: false,
                            showFeedback: false,
                            feedbackSent: false,
                            feedbackReason: '',
                            feedbackOther: '',
                            showRejectForm: false,
                            rejectReason: '',
                            rejectOther: '',
                            sendFeedback(actionId) {
                                if (!this.feedbackReason) return;
                                fetch('/vera/responses/' + actionId + '/feedback', {
                                    method: 'POST',
                                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                    body: JSON.stringify({reason: this.feedbackReason, custom_text: this.feedbackOther})
                                }).then(() => { this.feedbackSent = true; });
                            },
                            confirmReject(actionId) {
                                if (!this.rejectReason) return;
                                fetch('/vera/responses/' + actionId + '/reject', {
                                    method: 'POST',
                                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                    body: JSON.stringify({reason: this.rejectReason})
                                }).then(() => {
                                    fetch('/vera/responses/' + actionId + '/feedback', {
                                        method: 'POST',
                                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                        body: JSON.stringify({reason: this.rejectReason, custom_text: this.rejectOther})
                                    }).then(() => { this.dismissed = true; });
                                });
                            }
                        }"
                        x-show="!dismissed"
                        class="mb-4 rounded-xl border-2 border-purple-300 bg-white shadow-sm overflow-hidden">

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-5 py-3 bg-purple-600">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span class="text-sm font-semibold text-white">Vera preparó una respuesta</span>
                                <span class="text-xs text-purple-200">
                                    {{ round($pendingAction->confidence_score * 100) }}% confianza
                                </span>
                            </div>
                            <button @click="dismissed = true" class="text-purple-200 hover:text-white transition-colors" title="Cerrar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Respuesta editable --}}
                        <div class="p-5">
                            <p class="text-xs text-gray-500 mb-2 font-medium">Puedes editar el texto antes de enviar:</p>
                            <textarea
                                x-model="responseText"
                                rows="6"
                                class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-lg p-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 resize-none outline-none">
                            </textarea>

                            {{-- Botones --}}
                            <div class="flex items-center gap-3 mt-4">

                                {{-- Aprobar y Enviar --}}
                                <button
                                    @click="
                                        sending = true;
                                        fetch('/vera/responses/{{ $pendingAction->id }}', {
                                            method: 'PATCH',
                                            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                            body: JSON.stringify({generated_response: responseText})
                                        })
                                        .then(() => fetch('/vera/responses/{{ $pendingAction->id }}/approve', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}))
                                        .then(() => fetch('/vera/responses/{{ $pendingAction->id }}/send', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}))
                                        .then(r => r.json())
                                        .then(data => {
                                            sending = false;
                                            if (data.success !== false) {
                                                sent = true;
                                                if (responseText !== originalText) {
                                                    showFeedback = true;
                                                }
                                            } else {
                                                alert('Error al enviar: ' + (data.message || 'Inténtalo de nuevo'));
                                                sending = false;
                                            }
                                        })
                                        .catch(() => { sending = false; alert('Error de red'); })
                                    "
                                    :disabled="sending"
                                    class="flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg transition-colors">
                                    <template x-if="!sending">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                    </template>
                                    <template x-if="sending">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                    </template>
                                    <span x-text="sending ? 'Enviando...' : 'Aprobar y Enviar'"></span>
                                </button>

                                {{-- Descartar --}}
                                <button
                                    @click="showRejectForm = true"
                                    :disabled="sending"
                                    x-show="!showRejectForm"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-gray-50 disabled:opacity-60 text-gray-700 text-sm font-medium rounded-lg border border-gray-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Descartar
                                </button>
                            </div>

                            {{-- Formulario inline de rechazo --}}
                            <div x-show="showRejectForm" x-transition class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm font-semibold text-red-800 mb-3">¿Por qué descartaste esta respuesta?</p>
                                <div class="space-y-2">
                                    <template x-for="opt in [
                                        {value: 'tone', label: 'El tono no era el mío'},
                                        {value: 'length', label: 'Era demasiado larga / corta'},
                                        {value: 'missing_info', label: 'Faltaba información importante'},
                                        {value: 'signature', label: 'La firma estaba mal'},
                                        {value: 'other', label: 'Otro motivo'}
                                    ]">
                                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 hover:text-gray-900">
                                            <input type="radio" :value="opt.value" x-model="rejectReason" class="accent-red-500">
                                            <span x-text="opt.label"></span>
                                        </label>
                                    </template>
                                </div>
                                <div x-show="rejectReason === 'other'" x-transition class="mt-2">
                                    <input type="text" x-model="rejectOther" placeholder="Cuéntanos qué mejorar..."
                                        class="w-full text-sm text-gray-900 bg-white border border-red-300 rounded-lg px-3 py-2 focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none">
                                </div>
                                <div class="flex items-center gap-3 mt-4">
                                    <button
                                        @click="confirmReject({{ $pendingAction->id }})"
                                        :disabled="!rejectReason"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition-colors">
                                        Confirmar rechazo
                                    </button>
                                    <button
                                        @click="showRejectForm = false; rejectReason = ''; rejectOther = ''"
                                        class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-600 text-sm font-medium rounded-lg border border-gray-300 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Confirmación enviado --}}
                        <div x-show="sent" x-transition class="px-5 py-4 bg-green-50 border-t border-green-200">
                            <div class="flex items-center gap-2 mb-0" :class="showFeedback ? 'mb-3' : ''">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-sm font-semibold text-green-700">Respuesta enviada correctamente</span>
                            </div>

                            {{-- Panel de feedback post-envío (solo si editó el borrador) --}}
                            <div x-show="showFeedback && !feedbackSent" x-transition class="mt-3 pt-3 border-t border-green-200">
                                <p class="text-sm font-medium text-gray-700 mb-2">¿Qué mejorarías para las próximas respuestas de Vera?</p>
                                <div class="space-y-1.5">
                                    <template x-for="opt in [
                                        {value: 'tone', label: 'El tono no era el mío'},
                                        {value: 'length', label: 'Era demasiado larga / corta'},
                                        {value: 'missing_info', label: 'Faltaba información importante'},
                                        {value: 'signature', label: 'La firma estaba mal'},
                                        {value: 'other', label: 'Otro'}
                                    ]">
                                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 hover:text-gray-900">
                                            <input type="radio" :value="opt.value" x-model="feedbackReason" class="accent-purple-600">
                                            <span x-text="opt.label"></span>
                                        </label>
                                    </template>
                                </div>
                                <div x-show="feedbackReason === 'other'" x-transition class="mt-2">
                                    <input type="text" x-model="feedbackOther" placeholder="Cuéntanos qué mejorar..."
                                        class="w-full text-sm text-gray-900 bg-white border border-gray-300 rounded-lg px-3 py-2 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <button
                                        @click="sendFeedback({{ $pendingAction->id }})"
                                        :disabled="!feedbackReason"
                                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition-colors">
                                        Enviar feedback
                                    </button>
                                    <button
                                        @click="showFeedback = false"
                                        class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm transition-colors">
                                        Saltar
                                    </button>
                                </div>
                            </div>

                            {{-- Confirmación feedback guardado --}}
                            <div x-show="feedbackSent" x-transition class="mt-3 pt-3 border-t border-green-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <span class="text-sm text-purple-700 font-medium">¡Gracias! Vera aprenderá de este feedback.</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Si ya fue enviada, mostrar aviso compacto --}}
                @if($sentAction)
                    <div class="mb-4 flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg">
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-green-800">Vera ya respondió este email</p>
                            <p class="text-xs text-green-600">{{ formatEmailDate($sentAction->response_sent_at ?? $sentAction->created_at) }}</p>
                        </div>
                    </div>
                @endif

                {{-- Resumen de Vera (si existe) --}}
                @if($email->vera_summary)
                    <div class="mb-4 flex items-start gap-3 px-4 py-3 bg-purple-50 border border-purple-200 rounded-lg">
                        <div class="w-7 h-7 bg-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-purple-900 mb-0.5">Resumen de Vera</p>
                            <p class="text-sm text-purple-800">{{ $email->vera_summary }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        // ==========================================
        // COMPONENTES ALPINE.JS
        // ==========================================
        document.addEventListener('alpine:init', () => {
            // Componente: Reply Composer (Modal de Respuesta)
            Alpine.data('replyComposer', () => ({
                // Estado
                isOpen: false,
                loading: false,
                emailId: null,
                emailAccountId: null,
                toEmail: '',
                toName: '',
                subject: '',
                message: '',
                originalSubject: '',

                // Inicialización
                init() {
                    // Escuchar evento de template seleccionado
                    window.addEventListener('template-selected', (e) => {
                        this.insertTemplate(e.detail);
                    });
                },

                // Abrir composer
                openComposer(data) {
                    console.log('🔵 Modal abriendo con datos:', data);
                    this.emailId = data.emailId;
                    this.emailAccountId = data.emailAccountId;
                    this.toEmail = data.toEmail;
                    this.toName = data.toName || '';
                    this.subject = data.subject;
                    this.originalSubject = data.originalSubject;
                    this.message = '';
                    this.isOpen = true;
                    console.log('✅ Modal abierto:', this.isOpen);
                },

                // Cerrar composer
                closeComposer() {
                    if (this.loading) return;

                    // Confirmar si hay contenido escrito
                    if (this.message.trim()) {
                        if (!confirm('¿Descartar el mensaje?')) {
                            return;
                        }
                    }

                    this.isOpen = false;
                    this.resetForm();
                },

                // Resetear formulario
                resetForm() {
                    this.emailId = null;
                    this.emailAccountId = null;
                    this.toEmail = '';
                    this.toName = '';
                    this.subject = '';
                    this.message = '';
                    this.originalSubject = '';
                    this.loading = false;
                },

                // Enviar respuesta
                async sendReply() {
                    if (!this.message.trim()) {
                        alert('Por favor escribe un mensaje');
                        return;
                    }

                    this.loading = true;

                    try {
                        const response = await fetch(`/emails/${this.emailId}/reply`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                to_email: this.toEmail,
                                subject: this.subject,
                                body: this.message,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Mostrar mensaje de éxito
                            alert('✅ Respuesta enviada exitosamente');

                            // Cerrar modal
                            this.isOpen = false;
                            this.resetForm();

                            // Recargar página o actualizar UI
                            window.location.reload();
                        } else {
                            alert('❌ Error: ' + (data.message || 'No se pudo enviar el email'));
                        }

                    } catch (error) {
                        console.error('Error enviando respuesta:', error);
                        alert('❌ Error al enviar el email. Por favor intenta nuevamente.');
                    } finally {
                        this.loading = false;
                    }
                },

                // Abrir selector de templates
                openTemplateSelector() {
                    window.dispatchEvent(new CustomEvent('open-template-selector', {
                        detail: {
                            emailId: this.emailId,
                            originalSubject: this.originalSubject,
                            toName: this.toName,
                            toEmail: this.toEmail,
                        }
                    }));
                },

                // Insertar template seleccionado
                insertTemplate(data) {
                    if (data.subject) {
                        this.subject = data.subject;
                    }
                    if (data.body) {
                        this.message = data.body;
                    }
                },

                // Abrir modal de programación
                openScheduleModal() {
                    window.dispatchEvent(new CustomEvent('open-schedule-modal', {
                        detail: {
                            emailId: this.emailId,
                            emailAccountId: this.emailAccountId,
                            toEmail: this.toEmail,
                            subject: this.subject,
                            message: this.message,
                        }
                    }));
                },
            }));

            // Componente: Schedule Email Modal (Modal de Programación)
            Alpine.data('scheduleEmailModal', () => ({
                isOpen: false,
                loading: false,
                scheduledDate: '',
                scheduledTime: '',
                minDate: '',
                emailData: null,

                init() {
                    // Establecer fecha mínima (hoy)
                    const today = new Date();
                    this.minDate = today.toISOString().split('T')[0];
                    console.log('✅ Schedule Email Modal inicializado');
                },

                openModal(data) {
                    console.log('🔵 Abriendo modal de programación:', data);
                    this.emailData = data;
                    this.isOpen = true;
                    // Resetear valores
                    this.scheduledDate = '';
                    this.scheduledTime = '';
                },

                closeModal() {
                    this.isOpen = false;
                    this.emailData = null;
                },

                setSchedule(option) {
                    const now = new Date();

                    switch (option) {
                        case '1hour':
                            now.setHours(now.getHours() + 1);
                            break;
                        case '3hours':
                            now.setHours(now.getHours() + 3);
                            break;
                        case 'tomorrow9am':
                            now.setDate(now.getDate() + 1);
                            now.setHours(9, 0, 0);
                            break;
                        case 'nextMonday9am':
                            const daysUntilMonday = (8 - now.getDay()) % 7 || 7;
                            now.setDate(now.getDate() + daysUntilMonday);
                            now.setHours(9, 0, 0);
                            break;
                    }

                    this.scheduledDate = now.toISOString().split('T')[0];
                    this.scheduledTime = now.toTimeString().slice(0, 5);
                },

                getFormattedSchedule() {
                    if (!this.scheduledDate || !this.scheduledTime) return '';

                    const date = new Date(`${this.scheduledDate}T${this.scheduledTime}`);
                    return date.toLocaleString('es-ES', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                async confirmSchedule() {
                    if (!this.scheduledDate || !this.scheduledTime) {
                        alert('Por favor selecciona fecha y hora');
                        return;
                    }

                    this.loading = true;

                    try {
                        const scheduledAt = `${this.scheduledDate} ${this.scheduledTime}:00`;

                        const response = await fetch('/scheduled-emails', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                email_account_id: this.emailData.emailAccountId,
                                original_email_id: this.emailData.emailId,
                                to: this.emailData.toEmail,
                                subject: this.emailData.subject,
                                body: this.emailData.message,
                                scheduled_at: scheduledAt,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('✅ Email programado exitosamente para: ' + this.getFormattedSchedule());
                            this.closeModal();

                            // Cerrar también el composer
                            window.dispatchEvent(new Event('close-reply-modal'));

                            // Recargar página
                            window.location.reload();
                        } else {
                            alert('❌ Error: ' + (data.message || 'No se pudo programar el email'));
                        }

                    } catch (error) {
                        console.error('Error programando email:', error);
                        alert('❌ Error al programar el email');
                    } finally {
                        this.loading = false;
                    }
                },
            }));

            // Componente: Template Selector (Modal de Selección de Templates)
            Alpine.data('templateSelector', () => ({
                // Estado
                isOpen: false,
                loading: false,
                templates: [],
                selectedTemplate: null,
                previewMode: false,
                renderedPreview: null,
                emailId: null,
                originalSubject: '',
                toName: '',
                toEmail: '',

                // Inicialización
                init() {
                    console.log('✅ Template Selector inicializado');
                },

                // Abrir selector
                async openSelector(data) {
                    console.log('🔵 Abriendo selector de templates con datos:', data);
                    this.emailId = data.emailId;
                    this.originalSubject = data.originalSubject;
                    this.toName = data.toName;
                    this.toEmail = data.toEmail;
                    this.isOpen = true;
                    this.previewMode = false;
                    this.selectedTemplate = null;
                    this.renderedPreview = null;

                    // Cargar templates
                    await this.loadTemplates();
                },

                // Cerrar selector
                closeSelector() {
                    this.isOpen = false;
                    this.templates = [];
                    this.selectedTemplate = null;
                    this.previewMode = false;
                    this.renderedPreview = null;
                },

                // Cargar templates activos
                async loadTemplates() {
                    this.loading = true;

                    try {
                        const response = await fetch('/email-templates?active_only=1&type=reply', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.templates = data.templates || [];
                            console.log('✅ Templates cargados:', this.templates.length);
                        } else {
                            console.error('❌ Error cargando templates:', data);
                            this.templates = [];
                        }

                    } catch (error) {
                        console.error('❌ Error en loadTemplates:', error);
                        this.templates = [];
                    } finally {
                        this.loading = false;
                    }
                },

                // Seleccionar template
                selectTemplate(template) {
                    this.selectedTemplate = template;
                    console.log('✅ Template seleccionado:', template.name);
                },

                // Preview de template con datos reales
                async previewTemplate(template) {
                    console.log('🔵 Generando preview para:', template.name);
                    this.selectedTemplate = template;
                    this.previewMode = true;
                    this.loading = true;

                    try {
                        const response = await fetch(`/email-templates/${template.id}/render`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                data: {
                                    nombre: this.toName || 'Usuario',
                                    fecha: new Date().toLocaleDateString('es-ES', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }),
                                    asunto_original: this.originalSubject,
                                    remitente: '{{ auth()->user()->name ?? "Yo" }}',
                                }
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.renderedPreview = data.rendered;
                            console.log('✅ Preview generado:', this.renderedPreview);
                        } else {
                            alert('❌ Error generando preview: ' + (data.message || 'Error desconocido'));
                            this.previewMode = false;
                        }

                    } catch (error) {
                        console.error('❌ Error en previewTemplate:', error);
                        alert('❌ Error al generar preview');
                        this.previewMode = false;
                    } finally {
                        this.loading = false;
                    }
                },

                // Usar template (insertar en composer)
                async useTemplate(template) {
                    console.log('🔵 Usando template:', template.name);

                    // Si ya hay preview renderizado, usar ese
                    if (this.renderedPreview) {
                        window.dispatchEvent(new CustomEvent('template-selected', {
                            detail: {
                                subject: this.renderedPreview.subject,
                                body: this.renderedPreview.body,
                            }
                        }));

                        this.closeSelector();
                        return;
                    }

                    // Si no hay preview, renderizar primero
                    this.loading = true;

                    try {
                        const response = await fetch(`/email-templates/${template.id}/render`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                data: {
                                    nombre: this.toName || 'Usuario',
                                    fecha: new Date().toLocaleDateString('es-ES', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }),
                                    asunto_original: this.originalSubject,
                                    remitente: '{{ auth()->user()->name ?? "Yo" }}',
                                }
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Disparar evento para insertar en composer
                            window.dispatchEvent(new CustomEvent('template-selected', {
                                detail: {
                                    subject: data.rendered.subject,
                                    body: data.rendered.body,
                                }
                            }));

                            console.log('✅ Template insertado en composer');
                            this.closeSelector();
                        } else {
                            alert('❌ Error usando template: ' + (data.message || 'Error desconocido'));
                        }

                    } catch (error) {
                        console.error('❌ Error en useTemplate:', error);
                        alert('❌ Error al usar template');
                    } finally {
                        this.loading = false;
                    }
                },
            }));

            // Componente: Árbol de Carpetas
            Alpine.data('foldersTree', () => ({
                folders: [],
                loading: true,
                selectedFolderId: null,
                expandedFolders: {},

                init() {
                    this.loadFolders();
                },

                async loadFolders() {
                    try {
                        const response = await fetch('/email-folders/tree', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error('Error al cargar carpetas');

                        const data = await response.json();
                        this.folders = data.folders || [];
                    } catch (error) {
                        console.error('Error:', error);
                        this.folders = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async selectFolder(folderId) {
                    window.location.href = `/emails?folder=${folderId}`;
                },

                toggleFolder(folderId) {
                    this.expandedFolders[folderId] = !this.expandedFolders[folderId];
                },

                isFolderExpanded(folderId) {
                    return this.expandedFolders[folderId] || false;
                },

                getFolderIconEmoji(icon) {
                    const emojis = {
                        folder: '📁',
                        star: '⭐',
                        briefcase: '💼',
                        user: '👤',
                        archive: '📦',
                        heart: '❤️',
                        flag: '🚩',
                        check: '✅',
                        clock: '⏰',
                        document: '📄'
                    };
                    return emojis[icon] || '📁';
                },

                renderFolderRecursive(folder, depth) {
                    const hasChildren = folder.children && folder.children.length > 0;
                    const isExpanded = this.isFolderExpanded(folder.id);
                    const isSelected = this.selectedFolderId === folder.id;
                    const indentClass = depth > 0 ? `ml-${depth * 4}` : '';

                    let html = `
                        <div class="${indentClass}">
                            <div @click="selectFolder(${folder.id})"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-md mb-1 cursor-pointer group relative ${isSelected ? 'bg-purple-50 text-purple-900' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'}">
                    `;

                    // Chevron si tiene hijos
                    if (hasChildren) {
                        html += `
                            <button @click.stop="toggleFolder(${folder.id})" class="flex-shrink-0">
                                <svg class="w-3 h-3 transition-transform ${isExpanded ? 'rotate-90' : ''}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        `;
                    } else {
                        html += `<span class="w-3"></span>`;
                    }

                    // Ícono y nombre
                    html += `
                        <span style="color: ${folder.color || '#6B7280'}" class="text-sm flex-shrink-0">${this.getFolderIconEmoji(folder.icon)}</span>
                        <span class="flex-1 truncate text-sm">${folder.name}</span>
                        <span class="text-xs text-gray-400 font-medium">${folder.emails_count || 0}</span>
                    `;

                    html += `</div>`;

                    // Renderizar hijos recursivamente
                    if (hasChildren && isExpanded) {
                        for (const child of folder.children) {
                            html += this.renderFolderRecursive(child, depth + 1);
                        }
                    }

                    html += `</div>`;

                    return html;
                },

                openCreateFolderModal() {
                    window.dispatchEvent(new CustomEvent('open-folder-modal'));
                },

                openFolderMenu(folderId) {
                    console.log('Abrir menú para carpeta:', folderId);
                }
            }));
        });

        function toggleStar(emailId) {
            fetch(`/emails/${emailId}/toggle-star`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }

        function archiveEmail(emailId) {
            if (confirm('¿Archivar este email?')) {
                fetch(`/emails/${emailId}/archive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("emails.index") }}';
                    }
                });
            }
        }

        function deleteEmail(emailId) {
            if (confirm('¿Eliminar este email? Se moverá a la papelera.')) {
                fetch(`/emails/${emailId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("emails.index") }}';
                    }
                });
            }
        }
    </script>

    <!-- Portal para dropdowns (fuera del flujo con overflow) -->
    <div id="dropdowns-portal" class="fixed" style="z-index: 99999;"></div>

    <!-- Command Palette Global (Ctrl+K) -->
    @include('components.command-palette')

    <!-- Modal de Respuesta de Email -->
    @include('components.email-reply-modal')

    <!-- Modal Selector de Templates -->
    @include('components.template-selector-modal')

    <!-- Modal Programar Envío -->
    @include('components.schedule-email-modal')

    <!-- Scripts adicionales -->
    @stack('scripts')

</body>

</html>
