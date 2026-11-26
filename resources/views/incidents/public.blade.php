<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Incidencias públicas · Ubicaditos</title>

    @include('partials.theme-init')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')

    <style>
        body { font-family: Figtree, ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#eef2f9] via-[#e8edf7] to-[#f7f9fc] text-[#1e2a4a] antialiased dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 dark:text-gray-100">
    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14">
        <header class="text-center">
            <p class="text-[11px] font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-blue-300">Ubicaditos</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">Incidencias públicas</h1>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-[#5b6788] dark:text-gray-400">
                Reportes que los usuarios decidieron compartir. No mostramos quién los envió.
            </p>
        </header>

        @if ($incidents->isEmpty())
            <section class="mt-8 rounded-[28px] border border-white/80 bg-white/75 p-10 text-center shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
                <p class="text-sm font-semibold">Todavía no hay incidencias públicas.</p>
                <p class="mt-1 text-xs text-[#5b6788] dark:text-gray-400">Cuando un reporte sea marcado como público aparecerá aquí.</p>
            </section>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($incidents as $incident)
                    <article class="rounded-[24px] border border-white/80 bg-white/75 p-5 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-[#eef2fa] px-2.5 py-1 text-[11px] font-semibold text-[#3d4a6b] dark:bg-gray-700 dark:text-gray-200">
                                {{ $incident->category_label }}
                            </span>
                            <span class="text-[11px] text-[#8a94b3] dark:text-gray-400">{{ $incident->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#3d4a6b] dark:text-gray-300">{{ $incident->description }}</p>

                        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-[11px] text-[#5b6788] dark:text-gray-400">
                            @if ($incident->route)
                                <div>
                                    <dt class="font-semibold text-[#3d4a6b] dark:text-gray-200">Ruta</dt>
                                    <dd>{{ $incident->route->name }} @if ($incident->route->code) ({{ $incident->route->code }}) @endif</dd>
                                </div>
                            @endif
                            @if ($incident->bus)
                                <div>
                                    <dt class="font-semibold text-[#3d4a6b] dark:text-gray-200">Unidad</dt>
                                    <dd>{{ $incident->bus->license_plate }}</dd>
                                </div>
                            @endif
                            @if ($incident->latitude && $incident->longitude)
                                <div>
                                    <dt class="font-semibold text-[#3d4a6b] dark:text-gray-200">Ubicación</dt>
                                    <dd class="font-mono">{{ $incident->latitude }}, {{ $incident->longitude }}</dd>
                                </div>
                            @endif
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>
