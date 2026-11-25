@php
    $counts = $incidents->countBy('status');

    $statusStyles = [
        \App\Models\Incident::STATUS_PENDING => [
            'classes' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800',
            'dot' => 'bg-amber-500',
        ],
        \App\Models\Incident::STATUS_IN_REVIEW => [
            'classes' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:ring-sky-800',
            'dot' => 'bg-sky-500',
        ],
        \App\Models\Incident::STATUS_RESOLVED => [
            'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800',
            'dot' => 'bg-emerald-500',
        ],
    ];

    $categoryStyles = [
        'route' => 'bg-[#eef2fa] text-[#4a5a8a] dark:bg-gray-700 dark:text-gray-200',
        'bus' => 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-200',
        'service' => 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-200',
        'driver' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-200',
        'stop' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-200',
        'other' => 'bg-slate-100 text-slate-500 dark:bg-gray-700 dark:text-gray-300',
    ];

    $categoryPaths = [
        'route' => '<circle cx="6" cy="18" r="2.2" /><circle cx="18" cy="6" r="2.2" /><path d="M8.2 18H14a3 3 0 0 0 0-6h-4a3 3 0 0 1 0-6h5.8" />',
        'bus' => '<rect x="4" y="4" width="16" height="13" rx="3" /><path d="M4 11h16" /><path d="M7 17v2M17 17v2" />',
        'service' => '<path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />',
        'driver' => '<circle cx="12" cy="8" r="3.5" /><path d="M5 20a7 7 0 0 1 14 0" />',
        'stop' => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" />',
        'other' => '<circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" />',
    ];

    $tiles = [
        ['label' => 'Pendientes', 'value' => $counts->get(\App\Models\Incident::STATUS_PENDING, 0), 'classes' => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800'],
        ['label' => 'En revisión', 'value' => $counts->get(\App\Models\Incident::STATUS_IN_REVIEW, 0), 'classes' => 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-900/30 dark:text-sky-200 dark:ring-sky-800'],
        ['label' => 'Resueltos', 'value' => $counts->get(\App\Models\Incident::STATUS_RESOLVED, 0), 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800'],
    ];
@endphp

<x-passenger-layout title="Mis reportes · Ubicaditos" active="Reportes">
    <div class="mx-auto max-w-5xl">

        {{-- Encabezado --}}
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-blue-300">Centro de reportes</p>
                <h1 class="mt-1 text-[28px] font-bold leading-tight tracking-tight dark:text-gray-100 sm:text-3xl">Mis reportes</h1>
                <p class="mt-1 max-w-xl text-xs text-[#5b6788] dark:text-gray-400">
                    Consulta el estado de las incidencias que has enviado sobre el servicio de transporte.
                </p>
            </div>

            <a href="{{ route('incidents.create') }}"
                class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 active:scale-[.99]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nuevo reporte
            </a>
        </header>

        @if (session('success'))
            <div class="mt-5 flex items-start gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800" role="status">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12.5 4.5 4.5L19 7.5" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mt-5 flex items-start gap-2 rounded-2xl bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700 ring-1 ring-rose-100 dark:bg-rose-900/30 dark:text-rose-200 dark:ring-rose-800" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Contadores --}}
        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            @foreach ($tiles as $tile)
                <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/80 p-4 shadow-lg shadow-[#4a5a8a]/10 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/80">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-base font-bold ring-1 {{ $tile['classes'] }}">
                        {{ $tile['value'] }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-[#1e2a4a] dark:text-gray-100">{{ $tile['label'] }}</p>
                        <p class="text-[11px] text-[#6b7699] dark:text-gray-400">{{ $tile['value'] == 1 ? 'reporte' : 'reportes' }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Listado --}}
        @if ($incidents->isEmpty())
            <div class="mt-6 rounded-[28px] border border-white/70 bg-white/80 p-10 text-center shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/80">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/30">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" />
                        <path d="M8 9h8M8 13h5" />
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-bold dark:text-gray-100">Todavía no has enviado reportes</h2>
                <p class="mx-auto mt-1 max-w-md text-xs text-[#5b6788] dark:text-gray-400">
                    Si detectaste un problema en una ruta, autobús, parada o con el servicio, cuéntanoslo desde el formulario.
                </p>
                <a href="{{ route('incidents.create') }}"
                    class="mt-5 inline-flex h-10 items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30">
                    Crear mi primer reporte
                </a>
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($incidents as $incident)
                    @php
                        $statusStyle = $statusStyles[$incident->status] ?? [
                            'classes' => 'bg-slate-100 text-slate-600 ring-slate-200',
                            'dot' => 'bg-slate-400',
                        ];
                        $categoryClass = $categoryStyles[$incident->category] ?? $categoryStyles['other'];
                        $categoryPath = $categoryPaths[$incident->category] ?? $categoryPaths['other'];
                    @endphp

                    <article class="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/80">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $categoryClass }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    {!! $categoryPath !!}
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h2 class="text-sm font-bold leading-tight text-[#1e2a4a] dark:text-gray-100">{{ $incident->category_label }}</h2>
                                        <p class="mt-0.5 text-[11px] text-[#8a94b3] dark:text-gray-400">
                                            {{ $incident->created_at->format('d/m/Y H:i') }}
                                            <span aria-hidden="true">·</span>
                                            Folio #{{ str_pad((string) $incident->id, 4, '0', STR_PAD_LEFT) }}
                                        </p>
                                    </div>

                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusStyle['classes'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                        {{ $incident->status_label }}
                                    </span>
                                </div>

                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#3d4a6b] dark:text-gray-300">{{ $incident->description }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-passenger-layout>
