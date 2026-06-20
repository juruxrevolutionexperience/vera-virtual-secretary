<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuración Vera</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .day-btn.active { background: #7c3aed; color: white; border-color: #7c3aed; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased bg-[#fafafa] text-gray-900">

<div class="flex min-h-screen overflow-x-hidden" x-data="veraConfig()" x-init="init()">
    @include('components.dashboard-sidebar')

    <main class="flex-1 min-w-0 lg:ml-56">

        {{-- Header móvil sticky --}}
        <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center px-4 lg:hidden">
            <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded hover:bg-gray-100 mr-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-sm font-semibold text-gray-900">Configuración de Vera</span>
        </header>

        <div class="p-4 lg:p-8"><div class="max-w-3xl mx-auto">

        <!-- Header escritorio -->
        <div class="hidden lg:flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configuración de Vera</h1>
                <p class="text-sm text-gray-500 mt-0.5">Ajusta cómo Vera gestiona tu agenda</p>
            </div>
            <a href="{{ route('vera.dashboard') }}"
               class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Panel Vera
            </a>
        </div>


        <form @submit.prevent="saveConfig" class="space-y-6">

            <!-- ── SECCIÓN 1: Identidad del Cerebro ─────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-1">Identidad de Vera</h2>
                <p class="text-sm text-gray-500 mb-5">Cómo se presenta Vera ante tus contactos.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre del negocio</label>
                        <input type="text" name="business_name"
                               value="{{ old('business_name', $config->business_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tu nombre</label>
                        <input type="text" name="owner_name"
                               value="{{ old('owner_name', $config->owner_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email para tus avisos</label>
                    <input type="email" name="notification_email"
                           value="{{ old('notification_email', $config->notification_email) }}"
                           placeholder="tu-correo-personal@ejemplo.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1.5">Aquí recibirás los avisos de Vera (quejas escaladas, resúmenes...). Si lo dejas vacío, llegan al correo de tu cuenta. Pon uno distinto al que gestiona Vera para no mezclarlos.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tono</label>
                        <select name="tone"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="formal" {{ $config->tone === 'formal' ? 'selected' : '' }}>Formal</option>
                            <option value="profesional" {{ $config->tone === 'profesional' ? 'selected' : '' }}>Profesional</option>
                            <option value="cercano" {{ $config->tone === 'cercano' ? 'selected' : '' }}>Cercano</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Idioma</label>
                        <select name="language"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="es" {{ ($config->language ?? 'es') === 'es' ? 'selected' : '' }}>Español</option>
                            <option value="en" {{ ($config->language ?? 'es') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="ca" {{ ($config->language ?? 'es') === 'ca' ? 'selected' : '' }}>Català</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Zona horaria</label>
                        @php $tzActual = $config->timezone ?? 'Europe/Madrid'; @endphp
                        <select name="timezone"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="Europe/Madrid" {{ $tzActual === 'Europe/Madrid' ? 'selected' : '' }}>España (peninsular) — Europe/Madrid</option>
                            <option value="Atlantic/Canary" {{ $tzActual === 'Atlantic/Canary' ? 'selected' : '' }}>Canarias — Atlantic/Canary</option>
                            <option value="Europe/Lisbon" {{ $tzActual === 'Europe/Lisbon' ? 'selected' : '' }}>Portugal — Europe/Lisbon</option>
                            <option value="America/Mexico_City" {{ $tzActual === 'America/Mexico_City' ? 'selected' : '' }}>México — America/Mexico_City</option>
                            <option value="America/Argentina/Buenos_Aires" {{ $tzActual === 'America/Argentina/Buenos_Aires' ? 'selected' : '' }}>Argentina — Buenos Aires</option>
                            <option value="America/Bogota" {{ $tzActual === 'America/Bogota' ? 'selected' : '' }}>Colombia — America/Bogota</option>
                            <option value="UTC" {{ $tzActual === 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1.5">Las citas y los horarios se gestionan en esta zona.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Dirección del negocio <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <input type="text" name="business_address"
                               value="{{ old('business_address', $config->business_address) }}"
                               placeholder="Calle Ejemplo 12, Málaga"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1.5">Si la rellenas, Vera la dirá cuando pregunten dónde estáis. Déjala vacía si prefieres gestionar la ubicación desde tus FAQ.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Enlace de Google Maps <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <input type="url" name="maps_url"
                               value="{{ old('maps_url', $config->maps_url) }}"
                               placeholder="https://maps.app.goo.gl/..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1.5">Cuando el cliente pregunte cómo llegar, Vera añade un botón “📍 Cómo llegar” con este enlace.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Modo</label>
                        <select name="mode" x-model="currentMode"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="supervised">Supervisado</option>
                            <option value="autonomous">Autónomo</option>
                        </select>
                    </div>
                </div>

                <div class="p-3 rounded-lg text-xs"
                     :class="currentMode === 'autonomous' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                    <span x-show="currentMode === 'supervised'">Modo supervisado: Vera propone horarios y espera tu confirmación antes de confirmar con el contacto.</span>
                    <span x-show="currentMode === 'autonomous'">Modo autónomo: Vera confirma reuniones directamente sin requerir tu aprobación.</span>
                </div>
            </div>

            <!-- ── SECCIÓN 2: Horario ─────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-1">Disponibilidad</h2>
                <p class="text-sm text-gray-500 mb-5">Vera solo ofrecerá reuniones dentro de este horario.</p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Días disponibles</label>
                    <div class="flex gap-2 flex-wrap">
                        <template x-for="day in allDays" :key="day.key">
                            <button type="button" @click="toggleDay(day.key)"
                                    class="day-btn px-3 py-1.5 rounded-lg border border-gray-300 text-sm font-medium transition-all hover:border-purple-400"
                                    :class="isDayEnabled(day.key) ? 'active' : 'text-gray-600'"
                                    x-text="day.label"></button>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Hora de inicio</label>
                        <input type="time" x-model="workStart"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Hora de fin</label>
                        <input type="time" x-model="workEnd"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Duración de reunión</label>
                        <select name="meeting_duration"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="30" {{ $config->meeting_duration == 30 ? 'selected' : '' }}>30 minutos</option>
                            <option value="45" {{ $config->meeting_duration == 45 ? 'selected' : '' }}>45 minutos</option>
                            <option value="60" {{ $config->meeting_duration == 60 ? 'selected' : '' }}>1 hora</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tiempo entre reuniones</label>
                        <select name="buffer_minutes"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <option value="0" {{ $config->buffer_minutes == 0 ? 'selected' : '' }}>Sin descanso</option>
                            <option value="10" {{ $config->buffer_minutes == 10 ? 'selected' : '' }}>10 minutos</option>
                            <option value="15" {{ $config->buffer_minutes == 15 ? 'selected' : '' }}>15 minutos</option>
                            <option value="30" {{ $config->buffer_minutes == 30 ? 'selected' : '' }}>30 minutos</option>
                        </select>
                    </div>
                </div>

                <!-- Confirmación de reservas (web de reservas) -->
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="auto_confirm_bookings" value="0">
                        <input type="checkbox" name="auto_confirm_bookings" value="1"
                               {{ $config->auto_confirm_bookings ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-700">Confirmar reservas automáticamente</span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                Si está activado, las citas que reserven tus clientes desde el enlace de Vera se confirman al instante.
                                Si lo dejas desactivado, recibirás un aviso y deberás confirmarlas o rechazarlas tú desde el panel.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- ── SECCIÓN 3: Personalidad ────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-1">Comportamiento de Vera</h2>
                <p class="text-sm text-gray-500 mb-5">Cómo responde Vera ante situaciones específicas.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Firma de Vera</label>
                        <input type="text" name="vera_signature"
                               value="{{ old('vera_signature', $config->vera_signature) }}"
                               placeholder="Ej: Vera, secretaria de Dr. Martínez"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Respuesta cuando no sabe la respuesta</label>
                        <textarea name="unknown_response" rows="2"
                                  placeholder="Ej: Permíteme consultarlo y te confirmo en breve."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none">{{ old('unknown_response', $config->unknown_response) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Palabras que escalan al propietario</label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" x-model="triggerInput" @keydown.enter.prevent="addTrigger()"
                                   placeholder="Ej: urgente, emergencia..."
                                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            <button type="button" @click="addTrigger()"
                                    class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700">
                                Añadir
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(trigger, idx) in triggers" :key="idx">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
                                    <span x-text="trigger"></span>
                                    <button type="button" @click="triggers.splice(idx, 1)" class="hover:text-purple-900">×</button>
                                </span>
                            </template>
                        </div>
                        <p x-show="triggers.length === 0" class="mt-1.5 text-xs text-gray-400">
                            Si no añades ninguna, Vera usará las predefinidas: <em>vergüenza, inaceptable, queja, reclamación, llevo esperando, indignado, pésimo, sigo sin respuesta…</em>
                        </p>
                        <input type="hidden" name="escalation_triggers" :value="JSON.stringify(triggers)">
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 4: Sobre el negocio (RAG) ─────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-1">Cuéntale a Vera quién eres</h2>
                <p class="text-sm text-gray-500 mb-5">Esta información mejora la calidad de las respuestas de Vera.</p>

                <textarea name="business_description" rows="5"
                          placeholder="Ej: Soy abogado especialista en derecho mercantil. Atiendo principalmente a empresas medianas que necesitan asesoramiento en contratos y fusiones. Las consultas iniciales duran 30 minutos y son gratuitas..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none">{{ old('business_description', $businessDescription) }}</textarea>
                <p class="text-xs text-gray-400 mt-1.5">Vera usará esto para contextualizar sus respuestas en el RAG</p>
            </div>

            <!-- ── SECCIÓN 4b: Servicios y precios ─────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-base font-bold text-gray-900">Servicios y precios</h2>
                    <button type="button" @click="addService()"
                            class="text-xs font-medium text-purple-600 hover:text-purple-700 px-2.5 py-1 rounded-md hover:bg-purple-50">+ Añadir servicio</button>
                </div>
                <p class="text-sm text-gray-500 mb-5">Vera responderá con esta info cuando un cliente pregunte qué ofreces o cuánto cuesta.</p>

                <div class="space-y-4">
                    <template x-for="(service, idx) in services" :key="idx">
                        <div class="border border-gray-200 rounded-lg p-4 relative">
                            <button type="button" @click="removeService(idx)"
                                    class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-lg leading-none">×</button>
                            <div class="grid grid-cols-3 gap-3 mb-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del servicio</label>
                                    <input type="text" x-model="service.name" placeholder="Ej: Desarrollo web a medida"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Precio</label>
                                    <input type="text" x-model="service.price" placeholder="Ej: desde 1.500€"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                                <textarea x-model="service.description" rows="2" placeholder="Breve descripción de qué incluye"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none"></textarea>
                            </div>
                        </div>
                    </template>
                    <p x-show="services.length === 0" class="text-xs text-gray-400">Aún no has añadido servicios. Pulsa "+ Añadir servicio".</p>
                </div>
            </div>

            <!-- ── SECCIÓN 4c: Preguntas frecuentes (FAQs) ─────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-base font-bold text-gray-900">Preguntas frecuentes</h2>
                    <button type="button" @click="addFaq()"
                            class="text-xs font-medium text-purple-600 hover:text-purple-700 px-2.5 py-1 rounded-md hover:bg-purple-50">+ Añadir pregunta</button>
                </div>
                <p class="text-sm text-gray-500 mb-5">Dudas habituales de tus clientes y cómo quieres que Vera las responda.</p>

                <div class="space-y-4">
                    <template x-for="(faq, idx) in faqs" :key="idx">
                        <div class="border border-gray-200 rounded-lg p-4 relative">
                            <button type="button" @click="removeFaq(idx)"
                                    class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-lg leading-none">×</button>
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pregunta</label>
                                <input type="text" x-model="faq.question" placeholder="Ej: ¿Hacéis mantenimiento?"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Respuesta</label>
                                <textarea x-model="faq.answer" rows="2" placeholder="Ej: Sí, ofrecemos planes de mantenimiento desde 50€/mes."
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none"></textarea>
                            </div>
                        </div>
                    </template>
                    <p x-show="faqs.length === 0" class="text-xs text-gray-400">Aún no has añadido preguntas. Pulsa "+ Añadir pregunta".</p>
                </div>
            </div>

            <!-- ── SECCIÓN 5: Google Calendar ─────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-1">Google Calendar</h2>
                <p class="text-sm text-gray-500 mb-5">Vera necesita acceso a tu calendario para crear eventos.</p>

                @if($config->hasCalendarConnected())
                <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl border border-green-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-green-900">Conectado</p>
                            <p class="text-xs text-green-600">Vera puede crear y gestionar tus eventos</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="disconnectGoogle()"
                            class="px-3 py-1.5 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                        Desconectar
                    </button>
                </div>
                @else
                <div class="flex items-center justify-between p-4 bg-amber-50 rounded-xl border border-amber-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-amber-900">Sin conectar</p>
                            <p class="text-xs text-amber-600">Vera no podrá crear eventos hasta conectar el calendario</p>
                        </div>
                    </div>
                    <a href="{{ route('google.calendar.connect') }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Conectar Google
                    </a>
                </div>
                @endif
            </div>

            <!-- Save Button -->
            <div class="flex justify-end pb-8">
                <button type="button" @click="saveConfig" :disabled="saving"
                        class="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl shadow-sm transition-colors disabled:opacity-60">
                    <span x-show="!saving">Guardar configuración</span>
                    <span x-show="saving">Guardando...</span>
                </button>
            </div>

        </form>
        </div>{{-- /.max-w-3xl --}}
        </div>{{-- /p-4 lg:p-8 --}}
    </main>

    <!-- Toast fijo — visible siempre, sin importar el scroll -->
    <div x-show="notification.show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         :class="notification.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-lg text-white text-sm font-medium">
        <svg x-show="notification.type === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg x-show="notification.type === 'error'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span x-text="notification.message"></span>
    </div>
</div>

@php
$defaultWorkingHours = ['monday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'], 'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'], 'wednesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'], 'thursday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'], 'friday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'], 'saturday' => ['enabled' => false], 'sunday' => ['enabled' => false]];
$workingHoursJson = $config->working_hours ?? $defaultWorkingHours;
@endphp
<script>
function veraConfig() {
    return {
        saving: false,
        currentMode: '{{ $config->mode ?? 'supervised' }}',
        triggerInput: '',
        workStart: '09:00',
        workEnd: '18:00',
        triggers: @json($config->escalation_triggers ?? []),
        services: @json($services ?? []),
        faqs: @json($faqs ?? []),
        allDays: [
            { key: 'monday', label: 'Lun' },
            { key: 'tuesday', label: 'Mar' },
            { key: 'wednesday', label: 'Mié' },
            { key: 'thursday', label: 'Jue' },
            { key: 'friday', label: 'Vie' },
            { key: 'saturday', label: 'Sáb' },
            { key: 'sunday', label: 'Dom' },
        ],
        workingHours: @json($workingHoursJson),
        notification: { show: false, type: 'success', message: '' },

        init() {
            // Extract start/end from first enabled day
            const days = Object.values(this.workingHours);
            const first = days.find(d => d.enabled && d.start);
            if (first) {
                this.workStart = first.start;
                this.workEnd = first.end;
            }
        },

        isDayEnabled(key) {
            return this.workingHours[key]?.enabled ?? false;
        },

        toggleDay(key) {
            if (!this.workingHours[key]) {
                this.workingHours[key] = { enabled: false };
            }
            this.workingHours[key].enabled = !this.workingHours[key].enabled;
            if (this.workingHours[key].enabled) {
                this.workingHours[key].start = this.workStart;
                this.workingHours[key].end = this.workEnd;
            }
        },

        addTrigger() {
            const t = this.triggerInput.trim();
            if (t && !this.triggers.includes(t)) this.triggers.push(t);
            this.triggerInput = '';
        },

        addService() {
            this.services.push({ name: '', price: '', description: '' });
        },
        removeService(idx) {
            this.services.splice(idx, 1);
        },
        addFaq() {
            this.faqs.push({ question: '', answer: '' });
        },
        removeFaq(idx) {
            this.faqs.splice(idx, 1);
        },

        buildWorkingHours() {
            const wh = {};
            this.allDays.forEach(d => {
                wh[d.key] = this.workingHours[d.key]?.enabled
                    ? { enabled: true, start: this.workStart, end: this.workEnd }
                    : { enabled: false };
            });
            return wh;
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => this.notification.show = false, 4000);
        },

        async disconnectGoogle() {
            if (!confirm('¿Desconectar Google? Vera dejará de acceder a tu calendario y email.')) return;
            const res = await fetch('{{ route('google.calendar.disconnect') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            if (res.ok) window.location.reload();
            else this.showNotification('error', 'Error al desconectar. Inténtalo de nuevo.');
        },

        async saveConfig() {
            this.saving = true;

            const g = name => document.querySelector(`[name="${name}"]`)?.value ?? '';

            const payload = {
                business_name:        g('business_name'),
                owner_name:           g('owner_name'),
                notification_email:   g('notification_email'),
                tone:                 g('tone'),
                language:             g('language'),
                timezone:             g('timezone'),
                business_address:     g('business_address'),
                maps_url:             g('maps_url'),
                mode:                 this.currentMode,
                meeting_duration:     parseInt(g('meeting_duration')),
                buffer_minutes:       parseInt(g('buffer_minutes')),
                vera_signature:       g('vera_signature'),
                unknown_response:     g('unknown_response'),
                business_description: g('business_description'),
                working_hours:        this.buildWorkingHours(),
                escalation_triggers:  this.triggers,
                auto_confirm_bookings: document.querySelector('[name="auto_confirm_bookings"][type="checkbox"]')?.checked ?? false,
                services:             this.services.filter(s => (s.name || '').trim() !== ''),
                faqs:                 this.faqs.filter(f => (f.question || '').trim() !== '' && (f.answer || '').trim() !== ''),
            };

            try {
                const resp = await fetch('{{ route('vera.config.update') }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await resp.json();

                if (resp.ok && data.success) {
                    this.showNotification('success', data.message || 'Configuración guardada.');
                } else {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error al guardar');
                    this.showNotification('error', msg);
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
