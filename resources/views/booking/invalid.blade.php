<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enlace de reserva</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 flex items-center justify-center px-4">
    @php
        $titulo = match($reason ?? '') {
            'used'    => 'Esta reserva ya está hecha',
            'expired' => 'Este enlace ha caducado',
            default   => 'Enlace no válido',
        };
        $sub = match($reason ?? '') {
            'used'    => 'Ya elegiste tu hueco con este enlace. Si necesitas cambiar la cita, responde al email y te ayudamos.',
            'expired' => 'El enlace para reservar ha expirado. Escríbenos y te enviamos uno nuevo.',
            default   => 'Este enlace de reserva no existe o no es correcto.',
        };
    @endphp
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl ring-1 ring-slate-200 p-8 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1 class="text-xl font-bold text-slate-800">{{ $titulo }}</h1>
        <p class="text-slate-500 mt-2 text-sm">{{ $sub }}</p>
        <p class="text-xs text-slate-400 mt-6">Gestionado por Vera · Jurux</p>
    </div>
</body>
</html>
