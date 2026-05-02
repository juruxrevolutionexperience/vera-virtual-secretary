<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configura a Vera - Agenda IA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .step-ring { transition: all 0.3s ease; }
        .day-btn.active { background: #7c3aed; color: white; border-color: #7c3aed; }
    </style>
</head>
<body class="antialiased bg-gradient-to-br from-purple-50 via-white to-indigo-50 min-h-screen"
      x-data="veraOnboarding()" x-init="init()">

<div class="max-w-2xl mx-auto px-4 py-12">

    <!-- Logo -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                <span class="text-white font-bold text-lg">V</span>
            </div>
            <span class="text-xl font-bold text-gray-900">Vera</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Configura tu secretaria de agenda</h1>
        <p class="text-gray-500">Solo 4 pasos para que Vera empiece a gestionar tus reuniones</p>
    </div>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-10">
        <template x-for="(label, idx) in steps" :key="idx">
            <div class="flex items-center">
                <div class="flex flex-col items-center">
                    <div class="step-ring w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2"
                         :class="{
                             'bg-purple-600 border-purple-600 text-white': currentStep > idx + 1,
                             'bg-white border-purple-600 text-purple-600': currentStep === idx + 1,
                             'bg-white border-gray-300 text-gray-400': currentStep < idx + 1
                         }">
                        <template x-if="currentStep > idx + 1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="currentStep <= idx + 1">
                            <span x-text="idx + 1"></span>
                        </template>
                    </div>
                    <span class="text-xs mt-1.5 font-medium"
                          :class="currentStep === idx + 1 ? 'text-purple-600' : 'text-gray-400'"
                          x-text="label"></span>
                </div>
                <div x-show="idx < steps.length - 1"
                     class="w-6 sm:w-16 h-0.5 mb-5 mx-1"
                     :class="currentStep > idx + 1 ? 'bg-purple-600' : 'bg-gray-200'"></div>
            </div>
        </template>
    </div>

    <!-- Error / Success notification -->
    <div x-show="notification.show" x-transition
         :class="notification.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
         class="mb-5 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2">
        <span x-text="notification.message"></span>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- ── PASO 1: Negocio ──────────────────────────────────── -->
        <div x-show="currentStep === 1" x-transition>
            <div class="px-8 pt-8 pb-2">
                <h2 class="text-lg font-bold text-gray-900">Cuéntame sobre tu negocio</h2>
                <p class="text-sm text-gray-500 mt-1">Esta información ayudará a Vera a presentarse correctamente.</p>
            </div>
            <div class="px-8 py-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre del negocio *</label>
                        <input type="text" x-model="form.business_name" placeholder="Ej: Clínica Martínez"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tu nombre *</label>
                        <input type="text" x-model="form.owner_name" placeholder="Ej: Dr. Martínez"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tono de Vera *</label>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach([
                            ['val'=>'formal',      'label'=>'Formal',      'desc'=>'Profesional y distante'],
                            ['val'=>'profesional', 'label'=>'Profesional', 'desc'=>'Cercano pero serio'],
                            ['val'=>'cercano',     'label'=>'Cercano',     'desc'=>'Amigable y cálido'],
                        ] as $tone)
                        <label class="cursor-pointer">
                            <input type="radio" name="tone_radio" value="{{ $tone['val'] }}"
                                   @click="form.tone = '{{ $tone['val'] }}'" class="sr-only">
                            <div class="p-2 sm:p-3 rounded-xl border-2 transition-all"
                                 :class="form.tone === '{{ $tone['val'] }}' ? 'border-purple-600 bg-purple-50' : 'border-gray-200 hover:border-gray-300'">
                                <p class="text-xs sm:text-sm font-semibold text-gray-900 leading-tight">{{ $tone['label'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 leading-tight">{{ $tone['desc'] }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Idioma de Vera *</label>
                        <select x-model="form.language"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="es">Español</option>
                            <option value="en">English</option>
                            <option value="ca">Català</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Zona horaria *</label>
                        <select x-model="form.timezone"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="Europe/Madrid">Europa/Madrid (UTC+1)</option>
                            <option value="Europe/London">Europa/Londres (UTC+0)</option>
                            <option value="America/Mexico_City">México (UTC-6)</option>
                            <option value="America/Bogota">Colombia (UTC-5)</option>
                            <option value="America/Buenos_Aires">Argentina (UTC-3)</option>
                            <option value="America/New_York">New York (UTC-5)</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── PASO 2: Disponibilidad ──────────────────────────── -->
        <div x-show="currentStep === 2" x-transition>
            <div class="px-8 pt-8 pb-2">
                <h2 class="text-lg font-bold text-gray-900">¿Cuándo puedes reunirte?</h2>
                <p class="text-sm text-gray-500 mt-1">Vera solo ofrecerá horarios dentro de tu disponibilidad.</p>
            </div>
            <div class="px-8 py-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Días disponibles</label>
                    <div class="flex gap-2 flex-wrap">
                        <template x-for="day in ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']" :key="day">
                            <button type="button"
                                    @click="toggleDay(day)"
                                    class="day-btn px-3 py-1.5 rounded-lg border border-gray-300 text-sm font-medium transition-all hover:border-purple-400"
                                    :class="isDaySelected(day) ? 'active' : 'text-gray-600'"
                                    x-text="day"></button>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Hora de inicio</label>
                        <input type="time" x-model="workingHoursStart"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Hora de fin</label>
                        <input type="time" x-model="workingHoursEnd"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Duración de reunión</label>
                        <select x-model.number="form.meeting_duration"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="30">30 minutos</option>
                            <option value="45">45 minutos</option>
                            <option value="60">1 hora</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tiempo entre reuniones</label>
                        <select x-model.number="form.buffer_minutes"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="0">Sin descanso</option>
                            <option value="10">10 minutos</option>
                            <option value="15">15 minutos</option>
                            <option value="30">30 minutos</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── PASO 3: Personalidad ────────────────────────────── -->
        <div x-show="currentStep === 3" x-transition>
            <div class="px-8 pt-8 pb-2">
                <h2 class="text-lg font-bold text-gray-900">Personaliza el comportamiento de Vera</h2>
                <p class="text-sm text-gray-500 mt-1">Cuanto más sepa Vera de ti, mejor gestionará tus reuniones.</p>
            </div>
            <div class="px-8 py-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Descripción de tu negocio</label>
                    <textarea x-model="form.business_description" rows="3"
                              placeholder="Ej: Soy abogado especialista en derecho mercantil. Mis clientes suelen pedir consultas iniciales de 30 minutos para valorar su caso..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Vera usará esto para responder de forma contextualizada</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Firma de Vera</label>
                    <input type="text" x-model="form.vera_signature"
                           placeholder="Ej: Vera, secretaria de Dr. Martínez"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Si Vera no sabe la respuesta, dirá:</label>
                    <textarea x-model="form.unknown_response" rows="2"
                              placeholder="Ej: Permíteme consultarlo con el doctor y te confirmo en breve."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Palabras clave que escalan al propietario</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" x-model="triggerInput" @keydown.enter.prevent="addTrigger()"
                               placeholder="Ej: urgente, emergencia..."
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        <button type="button" @click="addTrigger()"
                                class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700">
                            Añadir
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(trigger, idx) in form.escalation_triggers" :key="idx">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
                                <span x-text="trigger"></span>
                                <button type="button" @click="form.escalation_triggers.splice(idx, 1)" class="hover:text-purple-900">×</button>
                            </span>
                        </template>
                    </div>
                    <p x-show="form.escalation_triggers.length === 0" class="mt-1.5 text-xs text-gray-400">
                        Si no añades ninguna, Vera usará las predefinidas: <em>vergüenza, inaceptable, queja, reclamación, llevo esperando, indignado, pésimo, sigo sin respuesta…</em>
                    </p>
                </div>

            </div>
        </div>

        <!-- ── PASO 4: Canales ─────────────────────────────────── -->
        <div x-show="currentStep === 4" x-transition>
            <div class="px-8 pt-8 pb-2">
                <h2 class="text-lg font-bold text-gray-900">Conecta tu cuenta de Google</h2>
                <p class="text-sm text-gray-500 mt-1">Una sola conexión da a Vera acceso a tu agenda y a tu email.</p>
            </div>
            <div class="px-8 py-6 space-y-4">

                @php $googleConnected = $config && $config->hasCalendarConnected(); @endphp

                <!-- Google (Calendar + Gmail) — canal principal activo -->
                <div class="p-5 rounded-xl border-2 {{ $googleConnected ? 'border-green-400 bg-green-50' : 'border-purple-200 bg-purple-50' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <!-- Logo Google -->
                            <div class="w-11 h-11 bg-white rounded-xl shadow-sm flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Conectar con Google
                                    <span class="ml-1.5 text-xs font-normal text-red-500">* requerido</span>
                                </p>
                                @if($googleConnected)
                                    <p class="text-xs text-green-700 font-medium mt-0.5">✓ Google Calendar y Gmail conectados</p>
                                @else
                                    <p class="text-xs text-gray-500 mt-0.5">Vera gestionará tu agenda y leerá solicitudes de reunión por email</p>
                                @endif
                                <!-- Chips de lo que incluye -->
                                <div class="flex gap-1.5 mt-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $googleConnected ? 'bg-green-100 text-green-700' : 'bg-white text-gray-500 border border-gray-200' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Google Calendar
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $googleConnected ? 'bg-green-100 text-green-700' : 'bg-white text-gray-500 border border-gray-200' }}">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                        </svg>
                                        Gmail
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if($googleConnected)
                            <span class="px-4 py-2 bg-green-100 text-green-700 text-sm font-semibold rounded-lg flex-shrink-0">Conectado ✓</span>
                        @else
                            <a href="{{ route('google.calendar.connect') }}"
                               class="flex-shrink-0 px-4 py-2 bg-white border-2 border-purple-300 text-purple-700 text-sm font-semibold rounded-xl hover:bg-purple-50 hover:border-purple-400 transition-colors flex items-center gap-2 shadow-sm">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                Conectar con Google
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Separador -->
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide pt-1">Más canales (próximamente)</p>

                <!-- WhatsApp -->
                <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 opacity-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">WhatsApp Business</p>
                            <p class="text-xs text-gray-400">Disponible próximamente</p>
                        </div>
                    </div>
                    <span class="px-3 py-1.5 bg-gray-100 text-gray-400 text-xs font-medium rounded-lg">Próximamente</span>
                </div>

                <!-- Telegram -->
                <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 opacity-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Telegram</p>
                            <p class="text-xs text-gray-400">Disponible próximamente</p>
                        </div>
                    </div>
                    <span class="px-3 py-1.5 bg-gray-100 text-gray-400 text-xs font-medium rounded-lg">Próximamente</span>
                </div>

                <p class="text-xs text-gray-400 text-center pt-1">
                    Puedes gestionar los canales más adelante desde Ajustes → Canales
                </p>

            </div>
        </div>

        <!-- Buttons -->
        <div class="px-8 pb-8 flex items-center justify-between">
            <button type="button" @click="prevStep()"
                    x-show="currentStep > 1"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                ← Anterior
            </button>
            <div x-show="currentStep === 1" class="flex-1"></div>

            <!-- Pasos 1-3: Siguiente -->
            <button type="button" @click="nextStep()"
                    x-show="currentStep < 4"
                    :disabled="saving"
                    class="ml-auto px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span x-show="!saving">Continuar →</span>
                <span x-show="saving">Guardando...</span>
            </button>

            <!-- Paso 4: Finalizar -->
            <button type="button" @click="finish()"
                    x-show="currentStep === 4"
                    :disabled="saving"
                    class="ml-auto px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span x-show="!saving">Empezar con Vera →</span>
                <span x-show="saving">Finalizando...</span>
            </button>
        </div>
    </div>

</div>

<script>
function veraOnboarding() {
    return {
        currentStep: 1,
        saving: false,
        steps: ['Negocio', 'Horario', 'Personalidad', 'Canales'],
        triggerInput: '',
        workingHoursStart: '09:00',
        workingHoursEnd: '18:00',
        selectedDays: ['Lun','Mar','Mié','Jue','Vie'],
        notification: { show: false, type: 'success', message: '' },
        form: {
            // Step 1
            business_name: '',
            owner_name: '',
            tone: 'profesional',
            language: 'es',
            timezone: 'Europe/Madrid',
            // Step 2
            working_hours: {},
            meeting_duration: 45,
            buffer_minutes: 15,
            // Step 3
            business_description: '',
            vera_signature: '',
            unknown_response: '',
            escalation_triggers: [],
        },

        init() {
            this.currentStep = {{ $startStep ?? 1 }};
            // Mostrar mensajes flash de Laravel (ej: tras el OAuth de Google)
            @if(session('success'))
            this.showNotification('success', '{{ addslashes(session('success')) }}');
            @elseif(session('error'))
            this.showNotification('error', '{{ addslashes(session('error')) }}');
            @endif
            // Pre-fill from existing config if any
            @if($config)
            this.form.business_name = '{{ $config->business_name ?? '' }}';
            this.form.owner_name = '{{ $config->owner_name ?? '' }}';
            this.form.tone = '{{ $config->tone ?? 'profesional' }}';
            this.form.language = '{{ $config->language ?? 'es' }}';
            this.form.timezone = '{{ $config->timezone ?? 'Europe/Madrid' }}';
            this.form.meeting_duration = {{ $config->meeting_duration ?? 45 }};
            this.form.buffer_minutes = {{ $config->buffer_minutes ?? 15 }};
            this.form.vera_signature = '{{ $config->vera_signature ?? '' }}';
            this.form.unknown_response = '{{ addslashes($config->unknown_response ?? '') }}';
            this.form.escalation_triggers = @json($config->escalation_triggers ?? []);
            @endif
        },

        toggleDay(day) {
            const idx = this.selectedDays.indexOf(day);
            if (idx >= 0) {
                this.selectedDays.splice(idx, 1);
            } else {
                this.selectedDays.push(day);
            }
        },

        isDaySelected(day) {
            return this.selectedDays.includes(day);
        },

        addTrigger() {
            const t = this.triggerInput.trim();
            if (t && !this.form.escalation_triggers.includes(t)) {
                this.form.escalation_triggers.push(t);
            }
            this.triggerInput = '';
        },

        buildWorkingHours() {
            const dayMap = {
                'Lun': 'monday', 'Mar': 'tuesday', 'Mié': 'wednesday',
                'Jue': 'thursday', 'Vie': 'friday', 'Sáb': 'saturday', 'Dom': 'sunday'
            };
            const hours = {};
            ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'].forEach(d => {
                hours[dayMap[d]] = this.isDaySelected(d)
                    ? { enabled: true, start: this.workingHoursStart, end: this.workingHoursEnd }
                    : { enabled: false };
            });
            return hours;
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => this.notification.show = false, 4000);
        },

        async saveStep(step) {
            const payload = { step };

            if (step === 1) {
                if (!this.form.business_name || !this.form.owner_name) {
                    this.showNotification('error', 'El nombre del negocio y tu nombre son obligatorios.');
                    return false;
                }
                Object.assign(payload, {
                    business_name: this.form.business_name,
                    owner_name: this.form.owner_name,
                    tone: this.form.tone,
                    language: this.form.language,
                    timezone: this.form.timezone,
                });
            } else if (step === 2) {
                payload.working_hours = this.buildWorkingHours();
                payload.meeting_duration = this.form.meeting_duration;
                payload.buffer_minutes = this.form.buffer_minutes;
            } else if (step === 3) {
                payload.unknown_response = this.form.unknown_response;
                payload.escalation_triggers = this.form.escalation_triggers;
                payload.vera_signature = this.form.vera_signature;
                payload.business_description = this.form.business_description;
            }

            try {
                const resp = await fetch('{{ route('vera.onboarding.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await resp.json();

                if (!resp.ok || !data.success) {
                    const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error al guardar');
                    this.showNotification('error', errorMsg);
                    return false;
                }

                return true;
            } catch (e) {
                this.showNotification('error', 'Error de conexión. Inténtalo de nuevo.');
                return false;
            }
        },

        async nextStep() {
            this.saving = true;
            const ok = await this.saveStep(this.currentStep);
            this.saving = false;
            if (ok) this.currentStep++;
        },

        prevStep() {
            if (this.currentStep > 1) this.currentStep--;
        },

        async finish() {
            this.saving = true;
            try {
                const resp = await fetch('{{ route('vera.onboarding.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ step: 4 }),
                });
                const data = await resp.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.showNotification('error', 'Error al finalizar. Inténtalo de nuevo.');
                }
            } catch (e) {
                this.showNotification('error', 'Error de conexión.');
            }
            this.saving = false;
        }
    };
}
</script>
</body>
</html>
