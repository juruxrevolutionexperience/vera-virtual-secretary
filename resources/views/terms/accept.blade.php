<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y Condiciones · Vera</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-2xl">

        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Vera</h1>
            <p class="text-gray-500 text-sm mt-1">por Jurux</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

            <!-- Cabecera -->
            <div class="bg-violet-600 px-8 py-6">
                <h2 class="text-xl font-semibold text-white">Antes de empezar</h2>
                <p class="text-violet-200 text-sm mt-1">Lee y acepta los Términos y Condiciones para usar Vera</p>
            </div>

            <!-- Texto de términos -->
            <div class="px-8 py-6 max-h-96 overflow-y-auto border-b border-gray-100 text-sm text-gray-600 space-y-4 leading-relaxed">

                <p><strong class="text-gray-900">1. Qué es Vera</strong><br>
                Vera es una herramienta de gestión administrativa que automatiza la comunicación con tus clientes (citas, recordatorios, respuestas). No es un servicio sanitario ni médico.</p>

                <p><strong class="text-gray-900">2. Lo que Vera no hace</strong><br>
                Vera no da consejos médicos, clínicos ni de salud. Si un cliente hace una pregunta de ese tipo, Vera le redirige al profesional. Eres tú, como profesional, quien tiene la responsabilidad clínica.</p>

                <p><strong class="text-gray-900">3. Tu responsabilidad sobre el contenido</strong><br>
                Eres responsable de la exactitud de la información que cargas en Vera: precios, horarios, servicios. Si Vera comunica algo incorrecto porque tú lo introdujiste mal, la responsabilidad es tuya.</p>

                <p><strong class="text-gray-900">4. Disponibilidad del servicio</strong><br>
                Vera procura mantenerse disponible de forma continua, pero no garantiza disponibilidad ininterrumpida. Tampoco somos responsables de caídas de terceros como WhatsApp, Telegram o Google.</p>

                <p><strong class="text-gray-900">5. Limitación de responsabilidad</strong><br>
                Nuestra responsabilidad máxima frente a ti se limita a lo que hayas pagado en los últimos 3 meses. No respondemos por daños indirectos ni pérdida de negocio.</p>

                <p><strong class="text-gray-900">6. Protección de datos</strong><br>
                Tratamos los datos de tus clientes como encargados del tratamiento, siguiendo tus instrucciones y en cumplimiento del RGPD y la LOPDGDD. Tú eres el responsable del tratamiento.</p>

                <p><strong class="text-gray-900">7. Modificaciones</strong><br>
                Podemos actualizar estos Términos con 15 días de preaviso. Si los cambios son significativos, te pediremos que los aceptes de nuevo.</p>

                <p class="text-xs text-gray-400">Versión {{ $version }} · Jurux</p>

            </div>

            <!-- Formulario de aceptación -->
            <form method="POST" action="{{ route('terms.accept.store') }}" class="px-8 py-6">
                @csrf

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <label class="flex items-start gap-3 cursor-pointer group">
                    <input
                        type="checkbox"
                        name="accept_terms"
                        value="1"
                        class="mt-0.5 w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500"
                        {{ old('accept_terms') ? 'checked' : '' }}
                    >
                    <span class="text-sm text-gray-700">
                        He leído y acepto los
                        <strong>Términos y Condiciones de uso de Vera</strong>
                        (versión {{ $version }})
                    </span>
                </label>

                <button
                    type="submit"
                    class="mt-6 w-full bg-violet-600 hover:bg-violet-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors"
                >
                    Aceptar y continuar
                </button>
            </form>

        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            Al aceptar, quedará registrado tu consentimiento con fecha, hora y dirección IP, conforme al RGPD.
        </p>

    </div>

</body>
</html>
