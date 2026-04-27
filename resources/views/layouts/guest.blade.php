<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'JURUX') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased bg-[#0d0d0d]">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <!-- Content -->
            <div class="w-full sm:max-w-md px-6">
                <!-- Logo -->
                <div class="flex justify-center mb-8">
                    <a href="/" class="flex items-center gap-2 group">
                        <img src="{{ asset('images/logo.png') }}" alt="JURUX Logo" class="w-14 h-14 group-hover:scale-110 transition-transform">
                        <span class="text-2xl font-bold text-white tracking-wide">JURUX</span>
                    </a>
                </div>

                <!-- Auth Card -->
                <div class="card-dark rounded-2xl px-8 py-10 border border-white/10 shadow-xl">
                    {{ $slot }}
                </div>

                <!-- Back to home -->
                <div class="text-center mt-6">
                    <a href="/" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">
                        ← Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
