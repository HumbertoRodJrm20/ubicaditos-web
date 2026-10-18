<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verifica tu correo · Ubicaditos</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-[#1e2a4a] bg-white">
    <div class="relative min-h-screen overflow-hidden bg-white">

        {{-- Fondo con manchas difuminadas --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 -right-24 h-[520px] w-[640px] rounded-full bg-[#c3d7ec] blur-[110px] opacity-90"></div>
            <div class="absolute top-1/4 -left-40 h-[460px] w-[560px] rounded-full bg-[#c5cbe6] blur-[110px] opacity-80"></div>
            <div class="absolute -bottom-40 left-1/3 h-[380px] w-[620px] rounded-full bg-[#d3daee] blur-[120px] opacity-80"></div>
            <div class="absolute -bottom-32 -right-20 h-[360px] w-[520px] rounded-full bg-[#c9d3ea] blur-[110px] opacity-70"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-[1400px] flex-col px-6 py-6 sm:px-10 lg:px-12">

            {{-- Logo --}}
            <header class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] shadow-md shadow-[#4a5a8a]/30">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="4" width="16" height="13" rx="3" />
                        <path d="M4 11h16" />
                        <circle cx="8.5" cy="14" r=".6" fill="currentColor" />
                        <circle cx="15.5" cy="14" r=".6" fill="currentColor" />
                        <path d="M7 17v2M17 17v2" />
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">Ubicaditos</span>
            </header>

            {{-- Contenido principal --}}
            <main class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-2 lg:gap-16">

                {{-- Columna izquierda: mensaje --}}
                <section class="max-w-xl lg:pl-4">
                    <h1 class="text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-[56px]">
                        Sigue tu autobús<br class="hidden sm:block"> en tiempo real.
                    </h1>
                    <p class="mt-6 max-w-md text-base leading-relaxed text-[#5b6788] sm:text-lg">
                        Rutas, paradas y tiempos de llegada del transporte público de tu ciudad, siempre a la mano.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-3 shadow-sm shadow-[#4a5a8a]/10 backdrop-blur">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a]">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="4" width="16" height="13" rx="3" />
                                    <path d="M4 11h16" />
                                    <path d="M7 17v2M17 17v2" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold leading-tight">Ruta 2 · Universidad</p>
                                <p class="text-xs text-[#6b7699]">Llega en 4 min</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-3 shadow-sm shadow-[#4a5a8a]/10 backdrop-blur">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#4f8f9c] to-[#7fb8b4]">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" />
                                    <circle cx="12" cy="9.5" r="2.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold leading-tight">12 autobuses</p>
                                <p class="text-xs text-[#6b7699]">activos ahora</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Columna derecha: tarjeta --}}
                <section class="flex justify-center lg:justify-end lg:pr-4">
                    <div class="w-full max-w-md rounded-[28px] border border-white/70 bg-white/75 p-8 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">

                        {{-- Ícono de sobre --}}
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] shadow-md shadow-[#4a5a8a]/30">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2.5" />
                                <path d="m4 7 8 6 8-6" />
                            </svg>
                        </div>

                        <h2 class="mt-5 text-2xl font-bold tracking-tight">Verifica tu correo</h2>
                        <p class="mt-1 text-xs leading-relaxed text-[#5b6788]">
                            Te enviamos un enlace de verificación. Ábrelo para activar tu cuenta y empezar a usar Ubicaditos.
                        </p>

                        {{-- Mensaje de éxito al reenviar --}}
                        @if (session('status') == 'verification-link-sent')
                            <div class="mt-5 flex items-center gap-2 rounded-xl bg-emerald-50 px-3.5 py-3 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100" role="status">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m5 12.5 4.5 4.5L19 7.5" />
                                </svg>
                                <span>Enviamos un nuevo enlace a tu correo.</span>
                            </div>
                        @endif

                        {{-- Reenviar correo de verificación --}}
                        <form method="POST" action="{{ route('verification.send') }}" x-data="{ sending: false }" @submit="sending = true" class="mt-5">
                            @csrf
                            <button type="submit" :disabled="sending"
                                class="flex h-12 w-full items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 active:scale-[.99] disabled:cursor-not-allowed disabled:opacity-70">
                                <span x-show="!sending">Reenviar correo de verificación</span>
                                <span x-show="sending" x-cloak>Enviando…</span>
                            </button>
                        </form>

                        {{-- Cerrar sesión --}}
                        <form method="POST" action="{{ route('logout') }}" class="mt-4">
                            @csrf
                            <button type="submit"
                                class="inline-flex h-9 items-center gap-2 rounded-full border border-slate-300 bg-white/70 px-4 text-xs font-medium text-[#3d4a6b] transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/20">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3" />
                                    <path d="M16 8l4 4-4 4M20 12H9" />
                                </svg>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
