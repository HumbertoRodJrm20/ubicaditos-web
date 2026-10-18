<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Incidencias y Emergencias') }}
        </h2>
    </x-slot>

    @php
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';
        $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';

        $statusStyles = [
            'pending' => ['label' => 'Pendiente', 'classes' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800', 'dot' => 'bg-amber-500'],
            'in_progress' => ['label' => 'En atención', 'classes' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:ring-sky-800', 'dot' => 'bg-sky-500'],
            'resolved' => ['label' => 'Resuelta', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800', 'dot' => 'bg-emerald-500'],
        ];

        $typeStyles = [
            'accident' => ['label' => 'Accidente', 'classes' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-200 dark:ring-rose-800'],
            'breakdown' => ['label' => 'Avería', 'classes' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800'],
            'medical' => ['label' => 'Médica', 'classes' => 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-900/30 dark:text-teal-200 dark:ring-teal-800'],
            'security' => ['label' => 'Seguridad', 'classes' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-900/30 dark:text-violet-200 dark:ring-violet-800'],
            'other' => ['label' => 'Otra', 'classes' => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-600'],
        ];

        $totalReports = $counts->sum();
    @endphp

    <style>
        .emergencies-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        @media (prefers-reduced-motion: reduce) {
            .emergency-card { transition: none !important; }
        }
    </style>

    <div class="emergencies-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
        <div class="mx-auto max-w-[1400px]">
            @if(session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-sm text-rose-800 shadow-sm dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-7 text-center">
                <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                <h1 class="text-3xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-4xl">
                    Incidencias y Emergencias
                </h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Reportes enviados por los conductores durante el servicio.</p>
            </div>

            {{-- Contadores rápidos --}}
            <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @php
                    $tiles = [
                        ['label' => 'Total', 'value' => $totalReports, 'badge' => 'bg-[#53658f]/10 text-[#53658f] dark:bg-[#8295c5]/20 dark:text-[#b6c4e6]'],
                        ['label' => 'Pendientes', 'value' => $counts->get('pending', 0), 'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200'],
                        ['label' => 'En atención', 'value' => $counts->get('in_progress', 0), 'badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200'],
                        ['label' => 'Resueltas', 'value' => $counts->get('resolved', 0), 'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200'],
                    ];
                @endphp

                @foreach ($tiles as $tile)
                    <div class="{{ $panel }} flex items-center gap-3 p-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-base font-bold {{ $tile['badge'] }}">
                            {{ $tile['value'] }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold {{ $heading }}">{{ $tile['label'] }}</p>
                            <p class="text-[11px] {{ $muted }}">reportes</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="{{ $panel }} p-4 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold {{ $heading }}">Reportes recibidos</h2>
                        <p class="mt-1 text-xs {{ $muted }}">{{ number_format($emergencies->count()) }} incidencias en la vista actual</p>
                    </div>

                    <form method="GET" action="{{ route('admin.emergencies') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <label for="emergency-status" class="whitespace-nowrap text-xs {{ $muted }}">Estado:</label>
                        <select id="emergency-status" name="status" onchange="this.form.submit()"
                            class="rounded-full border border-slate-200/90 bg-white/90 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">Todos los estados</option>
                            @foreach ($statuses as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                            @endforeach
                        </select>

                        <label for="emergency-type" class="whitespace-nowrap text-xs {{ $muted }}">Tipo:</label>
                        <select id="emergency-type" name="type" onchange="this.form.submit()"
                            class="rounded-full border border-slate-200/90 bg-white/90 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">Todos los tipos</option>
                            @foreach ($types as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" {{ $type === $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>

                        @if ($status || $type)
                            <a href="{{ route('admin.emergencies') }}"
                                class="rounded-full border border-slate-200 bg-white/80 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                Limpiar
                            </a>
                        @endif
                    </form>
                </div>

                @if ($emergencies->count() > 0)
                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach ($emergencies as $emergency)
                            @php
                                $typeStyle = $typeStyles[$emergency->type] ?? $typeStyles['other'];
                                $statusStyle = $statusStyles[$emergency->status] ?? null;
                            @endphp

                            <article class="emergency-card rounded-2xl border border-slate-200/80 bg-white/70 p-4 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800/50">
                                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $typeStyle['classes'] }}">
                                            {{ $typeStyle['label'] }}
                                        </span>

                                        @if ($statusStyle)
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusStyle['classes'] }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                                {{ $statusStyle['label'] }}
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-[11px] {{ $muted }}">{{ $emergency->created_at->diffForHumans() }}</span>
                                </div>

                                <p class="text-sm text-slate-700 dark:text-gray-200">{{ $emergency->description }}</p>

                                <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-gray-700/40">
                                        <dt class="{{ $muted }}">Reportó</dt>
                                        <dd class="mt-0.5 font-medium text-slate-700 dark:text-gray-100">{{ $emergency->reporter->name ?? 'Usuario eliminado' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-gray-700/40">
                                        <dt class="{{ $muted }}">Autobús</dt>
                                        <dd class="mt-0.5 font-medium text-slate-700 dark:text-gray-100">
                                            @if ($emergency->bus)
                                                {{ $emergency->bus->license_plate }}
                                                <span class="font-normal {{ $muted }}">({{ $emergency->bus->model ?: 'sin modelo' }})</span>
                                            @else
                                                Sin unidad asignada
                                            @endif
                                        </dd>
                                    </div>
                                    @if ($emergency->latitude && $emergency->longitude)
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-gray-700/40 sm:col-span-2">
                                            <dt class="{{ $muted }}">Ubicación del reporte</dt>
                                            <dd class="mt-0.5 font-mono text-[11px] text-slate-600 dark:text-gray-300">
                                                {{ $emergency->latitude }}, {{ $emergency->longitude }}
                                            </dd>
                                        </div>
                                    @endif
                                    @if ($emergency->resolved_at)
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-gray-700/40 sm:col-span-2">
                                            <dt class="{{ $muted }}">Resuelta</dt>
                                            <dd class="mt-0.5 font-medium text-slate-700 dark:text-gray-100">{{ $emergency->resolved_at->format('d/m/Y H:i') }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                <div class="mt-4 flex justify-end">
                                    @if ($emergency->status === 'pending')
                                        <form method="POST" action="{{ route('admin.emergencies.resolve', $emergency) }}">
                                            @csrf
                                            <button type="submit" onclick="return confirm('¿Marcar esta incidencia como resuelta?')"
                                                class="rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 py-2 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                                                Marcar como resuelta
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] {{ $muted }}">Sin acciones pendientes</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-12 text-center dark:border-gray-700">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-gray-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold {{ $heading }}">No hay incidencias que mostrar</p>
                        <p class="mt-1 text-xs {{ $muted }}">Los reportes de los conductores aparecerán aquí.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
