@php
    $heading = 'text-[#273252] dark:text-gray-100';
    $muted = 'text-slate-500 dark:text-gray-400';
    $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';

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

    $nextStatuses = [
        \App\Models\Incident::STATUS_PENDING => [\App\Models\Incident::STATUS_IN_REVIEW, \App\Models\Incident::STATUS_RESOLVED],
        \App\Models\Incident::STATUS_IN_REVIEW => [\App\Models\Incident::STATUS_RESOLVED],
        \App\Models\Incident::STATUS_RESOLVED => [\App\Models\Incident::STATUS_PENDING],
    ];

    $actionLabels = [
        \App\Models\Incident::STATUS_IN_REVIEW => 'Poner en revisión',
        \App\Models\Incident::STATUS_RESOLVED => 'Marcar como resuelto',
        \App\Models\Incident::STATUS_PENDING => 'Reabrir reporte',
    ];

    $actionStyles = [
        \App\Models\Incident::STATUS_IN_REVIEW => 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200',
        \App\Models\Incident::STATUS_RESOLVED => 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
        \App\Models\Incident::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight {{ $heading }}">
            {{ __('Gestión de reportes') }}
        </h2>
    </x-slot>

    <div class="incidents-admin -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
        <div class="mx-auto max-w-[1200px]">

            @if (session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-7 text-center">
                <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                <h1 class="text-3xl font-bold tracking-tight {{ $heading }} sm:text-4xl">Gestión de reportes</h1>
                <p class="mt-2 text-sm {{ $muted }}">Da seguimiento a las incidencias que envían pasajeros y conductores.</p>
            </div>

            {{-- Contadores por estado --}}
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
                @foreach ($statuses as $value => $label)
                    @php
                        $style = $statusStyles[$value];
                        $total = (int) ($counts[$value] ?? 0);
                    @endphp
                    <a href="{{ route('admin.incidents.index', array_filter(['status' => $value, 'category' => $category, 'q' => $search])) }}"
                        class="{{ $panel }} flex items-center justify-between gap-3 p-4 transition hover:-translate-y-0.5">
                        <span class="flex items-center gap-2.5 text-sm font-semibold {{ $heading }}">
                            <span class="h-2.5 w-2.5 rounded-full {{ $style['dot'] }}"></span>
                            {{ $label }}
                        </span>
                        <span class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $style['classes'] }}">
                            {{ $total }}
                        </span>
                    </a>
                @endforeach
            </div>

            <section class="{{ $panel }} p-4 sm:p-6">
                <form method="GET" action="{{ route('admin.incidents.index') }}" class="grid gap-3 lg:grid-cols-[1fr_auto_auto_auto]">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4 4"/>
                        </svg>
                        <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por descripción o usuario..."
                            class="w-full rounded-full border border-slate-200/90 bg-white/90 py-2.5 pl-9 pr-4 text-sm text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <select name="status" class="rounded-full border border-slate-200/90 bg-white/90 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="category" class="rounded-full border border-slate-200/90 bg-white/90 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                            Filtrar
                        </button>
                        @if ($status || $category || $search !== '')
                            <a href="{{ route('admin.incidents.index') }}" class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white/85 px-4 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>

                <div class="mt-6 space-y-3">
                    @forelse ($incidents as $incident)
                        @php
                            $style = $statusStyles[$incident->status] ?? $statusStyles[\App\Models\Incident::STATUS_PENDING];
                            $author = $incident->user;
                        @endphp

                        <article class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800/60">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $style['classes'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $style['dot'] }}"></span>
                                            {{ $incident->status_label }}
                                        </span>
                                        <span class="rounded-full bg-[#eef2fa] px-2.5 py-1 text-[11px] font-medium text-[#3d4a6b] dark:bg-gray-700 dark:text-gray-300">
                                            {{ $incident->category_label }}
                                        </span>
                                        <span class="text-[11px] {{ $muted }}">
                                            #{{ $incident->id }} · {{ $incident->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <p class="mt-3 whitespace-pre-line text-sm text-slate-700 dark:text-gray-200">{{ $incident->description }}</p>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] {{ $muted }}">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-[9px] font-bold text-white">
                                                {{ mb_strtoupper(mb_substr($author?->first_name ?? 'U', 0, 1)) }}{{ mb_strtoupper(mb_substr($author?->last_name ?? '', 0, 1)) }}
                                            </span>
                                            {{ $author?->name ?? 'Usuario eliminado' }}
                                        </span>
                                        @if ($author?->email)
                                            <span>{{ $author->email }}</span>
                                        @endif
                                        @if ($incident->resolved_at)
                                            <span class="text-emerald-600 dark:text-emerald-300">Resuelto {{ $incident->resolved_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2 sm:flex-col sm:items-stretch">
                                    @foreach ($nextStatuses[$incident->status] ?? [] as $nextStatus)
                                        <form method="POST" action="{{ route('admin.incidents.update', $incident) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $nextStatus }}">
                                            <button type="submit"
                                                class="inline-flex h-9 w-full items-center justify-center rounded-full border px-4 text-[11px] font-semibold transition {{ $actionStyles[$nextStatus] }}">
                                                {{ $actionLabels[$nextStatus] }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-12 text-center dark:border-gray-700">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-gray-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6M9 16h6M9 8h6"/><path d="M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                            </div>
                            <p class="mt-3 text-sm font-semibold {{ $heading }}">No hay reportes con esos filtros</p>
                            <p class="mt-1 text-xs {{ $muted }}">Cuando un pasajero o conductor envíe una incidencia aparecerá aquí.</p>
                        </div>
                    @endforelse
                </div>

                @if ($incidents->hasPages())
                    <div class="mt-5">
                        {{ $incidents->links() }}
                    </div>
                @endif
            </section>

            <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">Ubicaditos · Gestión de reportes</p>
        </div>
    </div>

    <style>
        .incidents-admin {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        @media (prefers-color-scheme: dark) {
            .dark .incidents-admin {
                background: linear-gradient(135deg, #0f172a 0%, #111827 52%, #0b1220 100%);
            }
        }
    </style>
</x-app-layout>
