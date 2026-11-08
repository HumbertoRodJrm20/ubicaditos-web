<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Ubicaditos') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#1e2a4a] antialiased dark:text-gray-100">
        <div class="relative flex min-h-screen flex-col overflow-hidden bg-[#eef2f9] dark:bg-slate-900">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 -left-32 h-[520px] w-[620px] rounded-full bg-[#c3d7ec] opacity-90 blur-[110px]"></div>
                <div class="absolute -top-24 -right-24 h-[460px] w-[560px] rounded-full bg-[#c5cbe6] opacity-90 blur-[110px]"></div>
                <div class="absolute -bottom-40 left-1/4 h-[420px] w-[720px] rounded-full bg-[#cfd6ee] opacity-80 blur-[120px]"></div>
            </div>

            <header class="relative mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-6 sm:px-8">
                <div class="flex items-center gap-3 rounded-full border border-white/70 bg-white/70 px-4 py-2 shadow-lg shadow-[#4a5a8a]/10 backdrop-blur-xl">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="3" width="16" height="14" rx="3" /><path d="M4 11h16M8 21v-4M16 21v-4" /><circle cx="8.5" cy="14" r=".6" /><circle cx="15.5" cy="14" r=".6" />
                        </svg>
                    </span>
                    <span class="text-base font-bold tracking-tight text-[#2f3a5f] dark:text-gray-100">Ubicaditos</span>
                </div>

                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="inline-flex h-10 items-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/30 transition hover:brightness-110">
                            Ir al panel
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="inline-flex h-10 items-center rounded-full border border-white/70 bg-white/70 px-5 text-xs font-semibold text-[#4b5a85] shadow-sm backdrop-blur transition hover:bg-white dark:border-white/10 dark:bg-gray-800/70 dark:text-gray-200">
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="hidden h-10 items-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/30 transition hover:brightness-110 sm:inline-flex">
                                Crear cuenta
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="relative mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center gap-6 px-5 pb-16 text-center sm:px-8">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/70 px-4 py-1.5 text-[11px] font-semibold text-[#4b5a85] shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/70 dark:text-gray-200">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                    Transporte urbano en tiempo real
                </span>

                <h1 class="max-w-2xl text-4xl font-bold leading-tight tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-5xl">
                    Sigue tu autobús y comparte tu viaje con tranquilidad.
                </h1>

                <p class="max-w-xl text-sm text-[#5b6788] dark:text-gray-400">
                    Ubicaditos conecta pasajeros, conductores y administradores: mapa en vivo, paradas favoritas,
                    reportes ciudadanos y ubicación compartida con enlaces temporales.
                </p>

                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}"
                        class="inline-flex h-12 items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-8 text-sm font-semibold text-white shadow-xl shadow-[#4a5a8a]/30 transition hover:-translate-y-0.5 hover:brightness-110">
                        Entrar a Ubicaditos
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="inline-flex h-12 items-center justify-center rounded-full border border-white/70 bg-white/80 px-8 text-sm font-semibold text-[#4b5a85] shadow-lg backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-gray-800/70 dark:text-gray-200">
                            Soy nuevo, crear cuenta
                        </a>
                    @endif
                </div>
            </main>

            <footer class="relative pb-6 text-center text-[11px] text-[#7c86a4] dark:text-gray-500">
                Ubicaditos · Oaxaca de Juárez
            </footer>
        </div>
    </body>
</html>
