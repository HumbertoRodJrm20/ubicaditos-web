<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Ubicaditos') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#1e2a4a] antialiased dark:text-gray-100">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#eef2f9] px-4 py-10 dark:bg-slate-900 sm:px-6">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 -left-32 h-[520px] w-[620px] rounded-full bg-[#c3d7ec] opacity-90 blur-[110px]"></div>
                <div class="absolute -top-24 -right-24 h-[460px] w-[560px] rounded-full bg-[#c5cbe6] opacity-90 blur-[110px]"></div>
                <div class="absolute -bottom-40 left-1/4 h-[420px] w-[720px] rounded-full bg-[#cfd6ee] opacity-80 blur-[120px]"></div>
            </div>

            <div class="relative mb-5 flex flex-col items-center">
                <a href="/" class="flex items-center gap-3 rounded-3xl border border-white/70 bg-white/70 px-5 py-3 shadow-lg shadow-[#4a5a8a]/10 backdrop-blur-xl">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white shadow-md shadow-[#4a5a8a]/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="3" width="16" height="14" rx="3" /><path d="M4 11h16M8 21v-4M16 21v-4" /><circle cx="8.5" cy="14" r=".6" /><circle cx="15.5" cy="14" r=".6" />
                        </svg>
                    </span>
                    <span class="text-xl font-bold tracking-tight text-[#2f3a5f] dark:text-gray-100">Ubicaditos</span>
                </a>
            </div>

            <div class="relative w-full overflow-hidden rounded-3xl border border-white/70 bg-white/80 px-5 py-6 shadow-xl shadow-[#4a5a8a]/15 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/80 sm:max-w-md sm:px-7">
                {{ $slot }}
            </div>

            <p class="relative mt-6 text-center text-[11px] text-[#7c86a4] dark:text-gray-400">
                Ubicaditos · Transporte urbano en tiempo real
            </p>
        </div>
    </body>
</html>
