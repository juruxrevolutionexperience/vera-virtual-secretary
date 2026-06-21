@extends('layouts.app')

@section('title', 'Detalle del Contacto')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header con botón de regreso -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('contacts.index') }}"
               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="ml-2 text-sm font-medium text-gray-700">Volver a Contactos</span>
            </a>
        </div>

        <!-- Info del Contacto -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <!-- Avatar -->
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center text-white text-3xl font-bold flex-shrink-0">
                        {{ strtoupper(substr($contact->name ?? $contact->email, 0, 2)) }}
                    </div>

                    <!-- Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">{{ $contact->name ?? 'Sin nombre' }}</h1>
                            @if($contact->is_starred)
                                <svg class="w-6 h-6 fill-yellow-400" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endif
                            @if($contact->priority === 'vip')
                                <span class="px-3 py-1 text-sm font-bold bg-yellow-100 text-yellow-800 rounded-full">VIP</span>
                            @endif
                            @if($contact->is_blocked)
                                <span class="px-3 py-1 text-sm font-bold bg-red-100 text-red-800 rounded-full">Bloqueado</span>
                            @endif
                        </div>

                        <p class="text-lg text-gray-600 mb-1">{{ $contact->email }}</p>
                        @if($contact->company)
                            <p class="text-sm text-gray-500">🏢 {{ $contact->company }}</p>
                        @endif
                        @if($contact->phone)
                            <p class="text-sm text-gray-500">📱 {{ $contact->phone }}</p>
                        @endif
                        @if($contact->notes)
                            <p class="text-sm text-gray-600 mt-2 p-3 bg-gray-50 rounded-lg">{{ $contact->notes }}</p>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <button onclick="window.print()"
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Imprimir
                    </button>
                    <a href="{{ route('contacts.index') }}"
                       class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors">
                        Editar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Emails Recibidos</p>
                    <p class="text-3xl font-bold text-green-600">{{ $contact->emails_received }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Emails Enviados</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $contact->emails_sent }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Interacciones</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $contact->emails_received + $contact->emails_sent }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Último Contacto</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ $contact->last_contact_at ? $contact->last_contact_at->diffForHumans() : 'Nunca' }}
                    </p>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Citas -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Historial de Citas ({{ $appointments->count() }})
        </h2>

        @if($appointments->isEmpty())
            <p class="text-sm text-gray-500">Este contacto todavía no tiene citas registradas.</p>
        @else
            <div class="space-y-3">
                @foreach($appointments as $appt)
                    @php
                        $estado = $appt->status ?? 'pending';
                        $badge = match($estado) {
                            'confirmed' => 'bg-green-100 text-green-700',
                            'cancelled', 'canceled' => 'bg-red-100 text-red-700',
                            'completed' => 'bg-gray-100 text-gray-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                        $estadoLabel = match($estado) {
                            'confirmed' => 'Confirmada',
                            'cancelled', 'canceled' => 'Cancelada',
                            'completed' => 'Completada',
                            'pending' => 'Pendiente',
                            default => ucfirst($estado),
                        };
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ optional($appt->scheduled_at)->format('d/m/Y · H:i') ?? 'Sin fecha' }}</p>
                            @if($appt->duration_minutes)
                                <p class="text-xs text-gray-500">{{ $appt->duration_minutes }} min</p>
                            @endif
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $badge }}">{{ $estadoLabel }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Historial de Emails -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Historial de Emails ({{ $emails->count() }})
        </h2>

        @if($emails->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay emails</h3>
                <p class="mt-1 text-sm text-gray-500">Aún no hay historial de comunicación con este contacto</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($emails as $email)
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <!-- Dirección del email -->
                                <div class="flex items-center gap-2 mb-2">
                                    @if($email->from_email === $contact->email)
                                        <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                            ← RECIBIDO
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                                            → ENVIADO
                                        </span>
                                    @endif

                                    @if($email->is_starred)
                                        <svg class="w-4 h-4 fill-yellow-400" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endif

                                    @if($email->priority === 'urgent')
                                        <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">URGENTE</span>
                                    @elseif($email->priority === 'high')
                                        <span class="px-2 py-1 text-xs font-semibold bg-orange-100 text-orange-800 rounded-full">ALTA</span>
                                    @endif

                                    @if($email->has_attachments)
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    @endif
                                </div>

                                <!-- Subject -->
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    {{ $email->subject ?? '(Sin asunto)' }}
                                </h3>

                                <!-- Snippet -->
                                @if($email->snippet)
                                    <p class="text-sm text-gray-600 mb-2">{{ Str::limit($email->snippet, 200) }}</p>
                                @endif

                                <!-- From/To -->
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <span>
                                        De: {{ $email->from_name ?? $email->from_email }}
                                    </span>
                                    @if($email->to_name || $email->to_email)
                                        <span>
                                            Para: {{ $email->to_name ?? $email->to_email }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Fecha y acciones -->
                            <div class="flex flex-col items-end gap-2 ml-4">
                                <span class="text-sm text-gray-500">
                                    {{ $email->received_at ? $email->received_at->format('d/m/Y H:i') : '' }}
                                </span>
                                <a href="{{ route('emails.show', $email->id) }}"
                                   class="px-3 py-1 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                                    Ver Email
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
