<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y Condiciones - JURUX</title>
    <meta name="description" content="Términos y condiciones de uso de JURUX y Vera">
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
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Términos y Condiciones</h1>
                <p class="text-gray-400">Última actualización: {{ date('d/m/Y') }}</p>
            </div>

            <div class="prose prose-invert prose-lg max-w-none">
                <div class="space-y-8">

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">1. Aceptación de los Términos</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Al acceder y utilizar JURUX y el servicio Vera (el "Servicio"), aceptas estar sujeto a estos Términos y Condiciones ("Términos"). Si no estás de acuerdo con alguna parte de estos términos, no debes usar el Servicio.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">2. Descripción del Servicio</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Vera es una secretaria virtual impulsada por inteligencia artificial que proporciona los siguientes servicios:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2 mt-4">
                            <li>Gestión automatizada de correos electrónicos</li>
                            <li>Programación y organización de reuniones</li>
                            <li>Gestión de llamadas telefónicas</li>
                            <li>Asistencia administrativa general</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">3. Registro y Cuenta</h2>
                        <p class="text-gray-400 leading-relaxed mb-4">
                            Para usar el Servicio, debes:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li>Tener al menos 18 años de edad</li>
                            <li>Proporcionar información precisa y completa durante el registro</li>
                            <li>Mantener la seguridad de tu contraseña</li>
                            <li>Notificarnos inmediatamente sobre cualquier uso no autorizado de tu cuenta</li>
                        </ul>
                        <p class="text-gray-400 leading-relaxed mt-4">
                            Eres responsable de todas las actividades que ocurran bajo tu cuenta.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">4. Planes y Pagos</h2>

                        <h3 class="text-xl font-semibold text-white mb-3 mt-6">4.1 Suscripciones</h3>
                        <p class="text-gray-400 leading-relaxed">
                            Ofrecemos diferentes planes de suscripción (Básico, Pro, Enterprise). Los precios y características están disponibles en nuestra página de precios.
                        </p>

                        <h3 class="text-xl font-semibold text-white mb-3 mt-6">4.2 Facturación</h3>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li>Las suscripciones se facturan mensualmente o anualmente según tu elección</li>
                            <li>Los pagos se procesan a través de Stripe de forma segura</li>
                            <li>Los cargos se realizan automáticamente al inicio de cada período de facturación</li>
                            <li>Todos los precios están en USD y no incluyen impuestos aplicables</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-white mb-3 mt-6">4.3 Cancelación y Reembolsos</h3>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li>Puedes cancelar tu suscripción en cualquier momento desde tu panel de control</li>
                            <li>La cancelación será efectiva al final del período de facturación actual</li>
                            <li>No ofrecemos reembolsos por períodos de facturación parcialmente utilizados</li>
                            <li>Ofrecemos un período de prueba gratuito de 14 días para nuevos usuarios</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">5. Uso Aceptable</h2>
                        <p class="text-gray-400 leading-relaxed mb-4">
                            Al usar el Servicio, te comprometes a NO:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2">
                            <li>Utilizar el Servicio para actividades ilegales o no autorizadas</li>
                            <li>Violar derechos de propiedad intelectual de terceros</li>
                            <li>Enviar spam, malware o contenido malicioso</li>
                            <li>Intentar obtener acceso no autorizado al sistema</li>
                            <li>Realizar ingeniería inversa del software</li>
                            <li>Usar el Servicio de manera que pueda dañar, deshabilitar o sobrecargar nuestros servidores</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">6. Propiedad Intelectual</h2>
                        <p class="text-gray-400 leading-relaxed">
                            El Servicio, incluyendo todo el contenido, características, funcionalidad, diseño, código y marca, es propiedad de JURUX y está protegido por leyes de derechos de autor, marcas registradas y otras leyes de propiedad intelectual.
                        </p>
                        <p class="text-gray-400 leading-relaxed mt-4">
                            Tus datos y contenido permanecen de tu propiedad. Nos otorgas una licencia limitada para procesar tus datos con el único propósito de proporcionar el Servicio.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">7. Privacidad y Seguridad</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Tu privacidad es importante para nosotros. El uso de tu información personal está regido por nuestra <a href="{{ route('privacy') }}" class="text-[#667eea] hover:underline">Política de Privacidad</a>.
                        </p>
                        <p class="text-gray-400 leading-relaxed mt-4">
                            Implementamos medidas de seguridad para proteger tus datos, pero no podemos garantizar seguridad absoluta. Usas el Servicio bajo tu propio riesgo.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">8. Limitación de Responsabilidad</h2>
                        <p class="text-gray-400 leading-relaxed">
                            El Servicio se proporciona "tal cual" y "según disponibilidad". JURUX no garantiza que:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2 mt-4">
                            <li>El Servicio estará disponible de forma ininterrumpida o libre de errores</li>
                            <li>Los resultados obtenidos serán precisos o confiables</li>
                            <li>Cualquier error será corregido</li>
                        </ul>
                        <p class="text-gray-400 leading-relaxed mt-4">
                            En ningún caso JURUX será responsable por daños indirectos, incidentales, especiales, consecuentes o punitivos, incluida la pérdida de beneficios, datos o uso.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">9. Garantías y Nivel de Servicio</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Nos esforzamos por mantener una disponibilidad del 99.5% del Servicio, excluyendo mantenimiento programado. En caso de interrupciones prolongadas, podremos ofrecer créditos de servicio según nuestro criterio.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">10. Modificaciones del Servicio</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Nos reservamos el derecho de modificar, suspender o discontinuar el Servicio (o cualquier parte del mismo) en cualquier momento, con o sin previo aviso. No seremos responsables ante ti ni ante terceros por cualquier modificación, suspensión o discontinuación del Servicio.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">11. Terminación</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Podemos suspender o terminar tu acceso al Servicio inmediatamente, sin previo aviso, por cualquier motivo, incluyendo:
                        </p>
                        <ul class="list-disc list-inside text-gray-400 space-y-2 mt-4">
                            <li>Violación de estos Términos</li>
                            <li>Falta de pago</li>
                            <li>Actividad fraudulenta o ilegal</li>
                            <li>Solicitud de las autoridades o por orden judicial</li>
                        </ul>
                        <p class="text-gray-400 leading-relaxed mt-4">
                            Tras la terminación, tu derecho a usar el Servicio cesará inmediatamente.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">12. Cambios a los Términos</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Podemos actualizar estos Términos ocasionalmente. Te notificaremos sobre cambios importantes publicando los nuevos Términos en esta página y actualizando la fecha de "Última actualización". El uso continuado del Servicio después de dichos cambios constituye tu aceptación de los nuevos Términos.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">13. Ley Aplicable y Jurisdicción</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Estos Términos se regirán e interpretarán de acuerdo con las leyes aplicables, sin tener en cuenta sus disposiciones sobre conflictos de leyes.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-bold text-white mb-4">14. Contacto</h2>
                        <p class="text-gray-400 leading-relaxed">
                            Si tienes preguntas sobre estos Términos, contáctanos en:
                        </p>
                        <div class="mt-4 p-6 bg-white/5 rounded-xl border border-white/10">
                            <p class="text-white"><strong>JURUX</strong></p>
                            <p class="text-gray-400">Email: <a href="mailto:legal@jurux.com" class="text-[#667eea] hover:underline">legal@jurux.com</a></p>
                            <p class="text-gray-400">Email general: <a href="mailto:support@jurux.com" class="text-[#667eea] hover:underline">support@jurux.com</a></p>
                        </div>
                    </section>

                    <section class="border-t border-white/10 pt-8 mt-8">
                        <p class="text-gray-400 text-sm">
                            Al usar el Servicio JURUX - Vera, aceptas estos Términos y Condiciones en su totalidad.
                        </p>
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
