@props(['fullscreen' => false, 'title' => 'Ubicaditos', 'active' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>[x-cloak] { display: none !important; }</style>
    {{ $head ?? '' }}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-[#1e2a4a] {{ $fullscreen ? 'overflow-hidden' : '' }}">
    <div class="relative min-h-screen bg-white">

        {{-- Barra flotante: componente passanger-nav (los enlaces y la pestaña activa se configuran ahí) --}}
        <div class="pointer-events-none fixed inset-x-3 top-3 z-[1100] sm:inset-x-6 sm:top-4">
            <div class="mx-auto max-w-[1400px]">
                <x-passenger-nav :active="$active" />
            </div>
        </div>

        @if ($fullscreen)
            {{-- Vista a pantalla completa (ej. mapa) --}}
            <main class="fixed inset-0">
                {{ $slot }}
            </main>
        @else
            {{-- Vista normal con fondo difuminado --}}
            <div aria-hidden="true" class="pointer-events-none fixed inset-0">
                <div class="absolute -top-32 -left-32 h-[520px] w-[620px] rounded-full bg-[#c3d7ec] opacity-90 blur-[110px]"></div>
                <div class="absolute -top-24 -right-24 h-[460px] w-[560px] rounded-full bg-[#c5cbe6] opacity-90 blur-[110px]"></div>
                <div class="absolute -bottom-40 left-1/4 h-[420px] w-[720px] rounded-full bg-[#cfd6ee] opacity-80 blur-[120px]"></div>
            </div>
            <main class="relative mx-auto max-w-[1400px] px-4 pb-10 pt-24 sm:px-6">
                {{ $slot }}
            </main>
        @endif
    </div>
</body>
</html>