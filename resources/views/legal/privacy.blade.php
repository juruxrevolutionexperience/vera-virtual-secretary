<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Política de Privacidad - JURUX</title>
    <meta name="description" content="Política de privacidad de JURUX y Vera, tu secretaria virtual">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/landing.css'])
</head>
<body class="antialiased bg-[#0d0d0d] text-white">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="flex items-center gap-1 group">
                    <img src="{{ asset('images/logo.png') }}" alt="JURUX Logo" class="w-12 h-12 group-hover:scale-110 transition-transform">
                    <span class="text-lg font-semibold tracking-wide">JURUX</span>
                </a>
                <a href="/" class="text-sm text-gray-400 hover:text-white transition-colors">
                    ← Volver al inicio
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="relative pt-32 pb-20">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="mb-12">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Política de Privacidad</h1>
                <p class="text-gray-400">Última actualización: {{ date('d/m/Y') }}</p>
            </div>

            <div class="prose prose-invert prose-lg max-w-none">
                <div class="space-y-8">

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">1. Introducción</h2>
                        <p class="text-gray-400 leading-relaxed">
                            En JURUX ("nosotros", "nuestro" o "la empresa"), respetamos tu privacidad y estamos comprometidos con la protección de tus datos personales. Esta Política de Privacidad describe cómo recopilamos, usamos, almacenamos y protegemos tu información cuando utilizas nuestro servicio Vera (la "secretaria virtual" o el "Servicio").
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">2. Información que Recopilamos</h2>
                        <p class="text-gray-400 leading-relaxed mb-4">
                            Recopilamos la siguiente información cuando usas nuestro Servicio:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li><strong class="text-white">Información de cuenta:</strong> Nombre, correo electrónico, contraseña cifrada</li>
                            <li><strong class="text-white">Información de uso:</strong> Emails gestionados, reuniones agendadas, llamadas procesadas</li>
                            <li><strong class="text-white">Información técnica:</strong> Dirección IP, tipo de navegador, sistema operativo</li>
                            <li><strong class="text-white">Cookies:</strong> Utilizamos cookies para mejorar tu experiencia</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">3. Cómo Usamos tu Información</h2>
                        <p class="text-gray-400 leading-relaxed mb-4">
                            Utilizamos tu información para:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li>Proporcionar y mejorar el Servicio de Vera</li>
                            <li>Procesar tus emails, agendar reuniones y gestionar llamadas</li>
                            <li>Enviar notificaciones importantes sobre el servicio</li>
                            <li>Analizar el uso del Servicio para mejorarlo</li>
                            <li>Cumplir con obligaciones legales</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">4. Compartir Información con Terceros</h2>
                        <p class="text-gray-400 leading-relaxed">
                            No vendemos, alquilamos ni compartimos tu información personal con terceros excepto en los siguientes casos:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2 mt-4">
                            <li><strong class="text-white">Proveedores de servicios:</strong> Google (Gmail API), OpenAI (procesamiento IA), Stripe (pagos)</li>
                            <li><strong class="text-white">Cumplimiento legal:</strong> Si la ley lo requiere o para proteger nuestros derechos</li>
                            <li><strong class="text-white">Con tu consentimiento:</strong> Cuando nos des permiso explícito</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">5. Seguridad de los Datos</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Implementamos medidas de seguridad técnicas y organizativas para proteger tu información:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2 mt-4">
                            <li>Cifrado SSL/TLS para todas las transmisiones</li>
                            <li>Contraseñas cifradas con bcrypt</li>
                            <li>Acceso restringido a datos personales</li>
                            <li>Monitoreo regular de vulnerabilidades</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">6. Retención de Datos</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Conservamos tu información personal solo durante el tiempo necesario para cumplir con los propósitos descritos en esta política, a menos que la ley requiera un período de retención más largo.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">7. Tus Derechos</h2>
                        <p class="text-gray-400 leading-relaxed mb-4">
                            Según el GDPR y leyes de privacidad aplicables, tienes los siguientes derechos:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li><strong class="text-white">Acceso:</strong> Solicitar una copia de tus datos personales</li>
                            <li><strong class="text-white">Rectificación:</strong> Corregir datos inexactos o incompletos</li>
                            <li><strong class="text-white">Eliminación:</strong> Solicitar la eliminación de tus datos ("derecho al olvido")</li>
                            <li><strong class="text-white">Portabilidad:</strong> Recibir tus datos en formato estructurado</li>
                            <li><strong class="text-white">Oposición:</strong> Oponerte al procesamiento de tus datos</li>
                        </ul>
                        <p class="text-gray-400 leading-relaxed mt-4">
                            Para ejercer estos derechos, contáctanos en: <a href="mailto:privacy@jurux.com" class="text-[#667eea] hover:underline">privacy@jurux.com</a>
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">8. Cookies</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Utilizamos cookies para mejorar tu experiencia. Puedes configurar tu navegador para rechazar cookies, pero esto puede afectar la funcionalidad del Servicio.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">9. Cambios a esta Política</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Podemos actualizar esta Política de Privacidad ocasionalmente. Te notificaremos sobre cambios importantes publicando la nueva política en esta página y actualizando la fecha de "Última actualización".
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">10. Contacto</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Si tienes preguntas sobre esta Política de Privacidad, contáctanos en:
                        </p>
                        <div class="mt-4 p-6 bg-white/5 rounded-xl border border-white/10">
                            <p class="text-white"><strong>JURUX</strong></p>
                            <p class="text-gray-400">Email: <a href="mailto:privacy@jurux.com" class="text-[#667eea] hover:underline">privacy@jurux.com</a></p>
                            <p class="text-gray-400">Email general: <a href="mailto:support@jurux.com" class="text-[#667eea] hover:underline">support@jurux.com</a></p>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/5 py-8">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm">© {{ date('Y') }} JURUX. Todos los derechos reservados.</p>
                <div class="flex gap-6">
                    <a href="{{ route('privacy') }}" class="text-gray-400 hover:text-white text-sm transition-colors">Privacidad</a>
                    <a href="{{ route('terms') }}" class="text-gray-400 hover:text-white text-sm transition-colors">Términos</a>
                    <a href="/" class="text-gray-400 hover:text-white text-sm transition-colors">Inicio</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
