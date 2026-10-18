<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Gestión de Conductores') }}
        </h2>
    </x-slot>

    @php
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';
        $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';
    @endphp

    <style>
        .drivers-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        .drivers-table th { font-size: 11px; letter-spacing: .01em; }
        .drivers-table td { font-size: 12px; }
        .drivers-table tbody tr { transition: background-color .15s ease; }
        .drivers-table tbody tr:hover { background-color: rgba(226, 232, 240, .38); }
        .drivers-table-wrap { scrollbar-width: thin; scrollbar-color: #c5ccdc transparent; }
        @media (prefers-reduced-motion: reduce) {
            .drivers-table tbody tr { transition: none; }
        }
    </style>

    <div class="drivers-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
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
                    Gestión de Conductores
                </h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Consulta conductores, asignaciones y actividad de viajes.</p>
            </div>

            <section class="{{ $panel }} p-4 sm:p-6">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold {{ $heading }}">Lista de conductores</h2>
                        <p class="mt-1 text-xs {{ $muted }}">
                            {{ method_exists($drivers, 'total') ? number_format($drivers->total()) : number_format($drivers->count()) }} conductores encontrados
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <form method="GET" action="{{ route('admin.drivers') }}" class="flex flex-1 items-center gap-2">
                            <label for="driver-search" class="sr-only">Buscar conductor</label>
                            <div class="relative min-w-0 flex-1 sm:w-64 sm:flex-none">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4 4"/>
                                </svg>
                                <input id="driver-search" type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Buscar nombre o email..."
                                    class="w-full rounded-full border border-slate-200/90 bg-white/90 py-2.5 pl-9 pr-4 text-sm text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder:text-gray-400">
                            </div>
                            <button type="submit" class="rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                                Buscar
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.drivers') }}" class="rounded-full border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">Limpiar</a>
                            @endif
                        </form>

                        <form method="GET" action="{{ route('admin.drivers') }}" class="flex items-center gap-2">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <label for="driver-per-page" class="whitespace-nowrap text-xs {{ $muted }}">Mostrar:</label>
                            <select id="driver-per-page" name="per_page" onchange="this.form.submit()"
                                class="rounded-full border border-slate-200 bg-white/90 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="10" {{ (int) request('per_page', 15) === 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ (int) request('per_page', 15) === 15 ? 'selected' : '' }}>15</option>
                                <option value="20" {{ (int) request('per_page', 15) === 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ (int) request('per_page', 15) === 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if($drivers->count() > 0)
                    <div class="drivers-table-wrap overflow-x-auto rounded-2xl border border-slate-200/80 bg-white/70 dark:border-gray-700 dark:bg-gray-800/50">
                        <table class="drivers-table min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                            <thead class="bg-slate-100/90 dark:bg-gray-700/80">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Nombre</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Email</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Horario</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Autobús</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Viajes</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Calificación</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white/50 dark:divide-gray-700 dark:bg-gray-800/30">
                                @foreach($drivers as $driver)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-gray-100">{{ $driver->name }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-gray-400">{{ $driver->email }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @if($driver->work_shift === 'morning')
                                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">6:00 AM - 2:00 PM</span>
                                            @elseif($driver->work_shift === 'afternoon')
                                                <span class="inline-flex rounded-full bg-orange-50 px-2.5 py-1 text-[11px] font-medium text-orange-700 dark:bg-orange-900/40 dark:text-orange-200">2:00 PM - 10:00 PM</span>
                                            @else
                                                <span class="text-slate-400 dark:text-gray-500">Sin asignar</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">
                                            @if($driver->drivingBus)
                                                <span class="font-medium text-slate-700 dark:text-gray-100">{{ $driver->drivingBus->license_plate }}</span>
                                                <span class="text-slate-400">({{ $driver->drivingBus->model }})</span>
                                            @else
                                                <span class="text-slate-400 dark:text-gray-500">Sin autobús</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-gray-100">{{ number_format($driver->trips_count) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">
                                            @if($driver->ratings_received_avg_rating)
                                                <span class="font-semibold text-slate-700 dark:text-gray-100">{{ number_format($driver->ratings_received_avg_rating, 1) }}</span> / 5.0
                                            @else
                                                <span class="text-slate-400 dark:text-gray-500">Sin calificación</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                @if($driver->drivingBus)
                                                    <form method="POST" action="{{ route('admin.drivers.unassign-bus', $driver) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="font-medium text-rose-600 transition hover:text-rose-800 dark:text-rose-400" onclick="return confirm('¿Desasignar autobús?')">Desasignar</button>
                                                    </form>
                                                @else
                                                    <button type="button" onclick="openAssignModal({{ $driver->id }})" class="font-medium text-[#53699e] transition hover:text-[#354775] dark:text-blue-300">Asignar bus</button>
                                                @endif
                                                <a href="{{ route('admin.users.edit', $driver) }}" class="font-medium text-slate-600 transition hover:text-slate-900 dark:text-gray-300 dark:hover:text-white">Editar</a>
                                                <form method="POST" action="{{ route('admin.users.destroy', $driver) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="font-medium text-rose-600 transition hover:text-rose-800 dark:text-rose-400" onclick="return confirm('¿Estás seguro de eliminar este conductor?')">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $drivers->links() }}
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-12 text-center dark:border-gray-700">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-gray-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M19 8v6m3-3h-6"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold {{ $heading }}">No se encontraron conductores</p>
                        <p class="mt-1 text-xs {{ $muted }}">Prueba con otro nombre o correo electrónico.</p>
                    </div>
                @endif
            </section>

            <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">Ubicaditos · Gestión de Conductores</p>
        </div>
    </div>

    {{-- Modal para asignar autobús --}}
    <div id="assign-bus-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-8">
            <button type="button" class="fixed inset-0 cursor-default bg-slate-900/40 backdrop-blur-sm" onclick="closeAssignModal()" aria-label="Cerrar modal"></button>
            <div class="relative z-10 w-full max-w-md rounded-[24px] border border-white/70 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h3 id="modal-title" class="text-lg font-semibold {{ $heading }}">Asignar autobús</h3>
                        <p class="mt-1 text-sm {{ $muted }}">Selecciona la unidad que utilizará este conductor.</p>
                    </div>
                    <button type="button" onclick="closeAssignModal()" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Cerrar">✕</button>
                </div>
                <form method="POST" id="assign-bus-form">
                    @csrf
                    <div class="mb-5">
                        <label for="assign-bus-select" class="mb-2 block text-sm font-medium {{ $heading }}">Seleccionar autobús</label>
                        <select id="assign-bus-select" name="bus_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">-- Seleccione un autobús --</option>
                            @foreach($availableBuses as $bus)
                                <option value="{{ $bus->id }}">{{ $bus->license_plate }} - {{ $bus->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <button type="button" onclick="closeAssignModal()" class="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancelar</button>
                        <button type="submit" class="flex-1 rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-105">Asignar autobús</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAssignModal(driverId) {
            const modal = document.getElementById('assign-bus-modal');
            const form = document.getElementById('assign-bus-form');

            form.action = `/admin/drivers/${driverId}/assign-bus`;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            document.getElementById('assign-bus-select')?.focus();
        }

        function closeAssignModal() {
            document.getElementById('assign-bus-modal')?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeAssignModal();
        });
    </script>
</x-app-layout>
