@php
    $cardClass = 'bg-white/70 backdrop-blur-xl border border-white/80 shadow-[0_20px_50px_-20px_rgba(60,70,110,0.35)] rounded-[32px] p-5 flex flex-col';
    $panelClass = 'bg-white/80 rounded-[4px] px-2 py-2 text-[14px] leading-5';
    $labelClass = 'text-slate-400';
    $valueClass = 'text-slate-500';
    $iconBox = 'w-10 h-10 rounded-xl bg-gradient-to-br from-[#4b5a85] to-[#2f3a5f] text-white flex items-center justify-center shrink-0';
    $route = $bus?->routes->first();
@endphp

<x-passenger-layout title="Ubicaditos · Inicio">
    <div class="space-y-4">

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[27px] font-bold text-[#1e2a4a] leading-tight">Panel de Conductor</h1>
                <p class="text-[11px] text-slate-500">Gestiona tu autobús</p>
            </div>
            <a href="{{ route('driver.statistics') }}"
               class="inline-flex h-9 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/25 transition hover:brightness-105">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9m5 10V5m5 14v-7m5 7V11"/></svg>
                Mis estadísticas
            </a>
        </div>

        @if(session('success'))
            <div class="px-4 py-3 rounded-2xl bg-emerald-50/90 border border-emerald-200 text-emerald-700 text-sm" role="alert">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="px-4 py-3 rounded-2xl bg-rose-50/90 border border-rose-200 text-rose-700 text-sm" role="alert">{{ session('error') }}</div>
        @endif

        @if($currentTrip)
            <section class="rounded-[32px] bg-gradient-to-r from-[#e3e9f8]/90 to-white/60 backdrop-blur-xl border border-[#b9c6ea] shadow-lg p-5">
                <h2 class="text-[15px] font-semibold text-[#3f4c75] mb-3">Viaje en progreso</h2>
                <div class="grid gap-4 md:grid-cols-3 text-[14px]">
                    <div>
                        <span class="text-slate-400">Ruta</span>
                        <p class="font-medium text-[#2f3a5f]">{{ $currentTrip->route->name }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Hora de inicio</span>
                        <p class="font-medium text-[#2f3a5f]">{{ $currentTrip->started_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Duración</span>
                        <p class="font-medium text-[#2f3a5f]">{{ $currentTrip->started_at->diffForHumans(null, true) }}</p>
                    </div>
                </div>
            </section>
        @endif

        <div class="grid gap-5 lg:grid-cols-2 items-stretch pt-2">

            <section class="{{ $cardClass }}">
                <div class="flex items-center gap-3 mb-10">
                    <span class="{{ $iconBox }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>
                    </span>
                    <h2 class="text-[16px] font-semibold text-[#2f3a5f]">Mi Unidad</h2>
                </div>

                @if($bus)
                    <div class="{{ $panelClass }}">
                        <div class="flex justify-between"><span class="{{ $labelClass }}">Placa:</span><span class="{{ $valueClass }}">{{ $bus->license_plate }}</span></div>
                        <div class="flex justify-between"><span class="{{ $labelClass }}">Modelo:</span><span class="{{ $valueClass }}">{{ $bus->model }}</span></div>
                        <div class="flex justify-between"><span class="{{ $labelClass }}">Capacidad:</span><span class="{{ $valueClass }}">{{ $bus->capacity }} pasajeros</span></div>
                        <div class="flex justify-between items-center">
                            <span class="{{ $labelClass }}">Estado:</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 rounded-full text-[10px] leading-5 {{ $bus->status === 'active' ? 'bg-[#dcf3e5] text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                <span class="w-1 h-1 rounded-full {{ $bus->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $bus->status === 'active' ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>

                    <div class="{{ $panelClass }} mt-10">
                        <div class="flex justify-between items-center">
                            <span class="{{ $labelClass }}">Ruta asignada:</span>
                            <span class="inline-flex items-center gap-2 font-medium text-slate-600">
                                @if($route)<span class="w-2 h-2 rounded-full" style="background-color: {{ $route->color ?? '#4b5a85' }}"></span>@endif
                                {{ $route->name ?? 'Sin ruta' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="{{ $labelClass }}">Mi calificación:</span>
                            <span class="{{ $valueClass }}">
                                @if($averageRating)
                                    {{ number_format($averageRating, 1) }} / 5.0
                                @else
                                    Sin calificaciones
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="flex-1 flex items-center justify-center py-8">
                        <img src="{{ asset('images/bus-unit.png') }}" alt="" class="w-[210px] h-auto mix-blend-multiply select-none" draggable="false">
                    </div>

                    <div class="bg-white/80 rounded-[4px] px-8 py-1.5 flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('driver.map') }}" class="flex-1 inline-flex items-center justify-center gap-2 h-[41px] rounded-full bg-gradient-to-r from-[#86bcc0] to-[#3ed6e0] text-white text-[13px] font-medium hover:brightness-105 transition">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>
                            Ver Mapa
                        </a>
                        <button type="button" onclick="openEmergencyModal()"
                            class="sm:w-[183px] h-[41px] rounded-full bg-gradient-to-r from-[#d62f3a] to-[#e8a0a6] text-white text-[13px] font-medium hover:brightness-105 transition">
                            Reportar Emergencia
                        </button>
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center text-center py-10">
                        <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <p class="mt-3 text-[14px] text-slate-500">No tienes un autobús asignado. Contacta al administrador.</p>
                    </div>
                @endif
            </section>

            <section class="{{ $cardClass }}">
                <div class="flex items-center gap-3 mb-3">
                    <span class="{{ $iconBox }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2.5"></circle><circle cx="18" cy="18" r="2.5"></circle><path d="M8.5 6H15a3 3 0 010 6H9a3 3 0 000 6h6.5"></path></svg>
                    </span>
                    <h2 class="text-[16px] font-semibold text-[#2f3a5f]">Mis viajes recientes</h2>
                </div>

                @if($recentTrips->count() > 0)
                    <div class="bg-white/80 rounded-[4px] ml-2 px-1.5 pb-2 overflow-x-auto">
                        <table class="min-w-full text-[14px] leading-5">
                            <thead>
                                <tr class="text-left uppercase text-[13px] text-slate-400 border-b border-slate-300/70">
                                    <th class="py-1.5 pr-4 font-normal">Ruta</th>
                                    <th class="py-1.5 pr-4 font-normal">Fecha inicio</th>
                                    <th class="py-1.5 pr-4 font-normal">Fecha fin</th>
                                    <th class="py-1.5 font-normal">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-400">
                                @foreach($recentTrips as $trip)
                                    <tr>
                                        <td class="pr-4 whitespace-nowrap {{ $loop->first ? 'pt-2.5' : '' }}">{{ $trip->route->name }}</td>
                                        <td class="pr-4 whitespace-nowrap {{ $loop->first ? 'pt-2.5' : '' }}">{{ $trip->started_at->format('d/m/Y H:i:s') }}</td>
                                        <td class="pr-4 whitespace-nowrap {{ $loop->first ? 'pt-2.5' : '' }}">
                                            @if($trip->completed_at)
                                                {{ $trip->completed_at->format('d/m/Y H:i:s') }}
                                            @else
                                                En curso
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap {{ $loop->first ? 'pt-2.5' : '' }}">
                                            <span class="inline-flex items-center gap-1 px-2 rounded-full text-[10px] leading-4 {{ $trip->status === 'in_progress' ? 'bg-[#fdeeb2] text-amber-800' : 'bg-[#dcf3e5] text-emerald-700' }}">
                                                <span class="w-1 h-1 rounded-full {{ $trip->status === 'in_progress' ? 'bg-amber-600' : 'bg-emerald-500' }}"></span>
                                                {{ $trip->status === 'in_progress' ? 'En curso' : 'Completado' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="bg-white/80 rounded-[4px] ml-2 px-3 py-3 text-[14px] text-slate-400">No has realizado viajes todavía.</p>
                @endif

                <div class="mt-5 flex justify-center">
                    <img src="{{ asset('images/bus-stop.png') }}" alt="" class="w-[66px] h-auto mix-blend-multiply select-none" draggable="false">
                </div>
            </section>
        </div>
    </div>

    {{-- Modal de reporte de incidencia/emergencia --}}
    <div id="emergency-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-[#1e2a4a]/35 p-4 backdrop-blur-sm">
        <div class="my-auto w-full max-w-md rounded-[28px] border border-white/70 bg-white p-6 shadow-2xl shadow-[#4a5a8a]/20">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#1e2a4a]">Reportar incidencia</h3>
                    <p class="mt-1 text-xs text-[#5b6788]">
                        El administrador recibirá el reporte con la ubicación de tu unidad.
                    </p>
                </div>
                <button type="button" onclick="closeEmergencyModal()" aria-label="Cerrar modal"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" action="{{ route('driver.emergency') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="emergency-type" class="mb-1.5 block text-xs font-semibold text-[#3d4a6b]">
                        Tipo de incidencia
                    </label>
                    <select id="emergency-type" name="type" required
                        class="h-11 w-full rounded-2xl border border-white/70 bg-[#f7f9fd] px-3.5 text-sm text-[#1e2a4a] focus:border-[#6b7bad] focus:ring-4 focus:ring-[#6b7bad]/25">
                        <option value="accident">Accidente</option>
                        <option value="breakdown">Avería mecánica</option>
                        <option value="medical">Emergencia médica</option>
                        <option value="security">Incidente de seguridad</option>
                        <option value="other" selected>Otra incidencia</option>
                    </select>
                </div>

                <div>
                    <label for="emergency-description" class="mb-1.5 block text-xs font-semibold text-[#3d4a6b]">
                        Descripción
                    </label>
                    <textarea id="emergency-description" name="description" rows="4" maxlength="500" required
                        oninput="document.getElementById('emergency-counter').textContent = this.value.length + '/500'"
                        placeholder="Describe qué ocurrió..."
                        class="w-full resize-none rounded-2xl border border-white/70 bg-[#f7f9fd] px-3.5 py-3 text-sm text-[#1e2a4a] placeholder:text-[#8a94b3] focus:border-[#6b7bad] focus:ring-4 focus:ring-[#6b7bad]/25"></textarea>
                    <p class="mt-1 text-right text-[10px] text-[#8a94b3]"><span id="emergency-counter">0/500</span></p>
                </div>

                <p class="rounded-2xl bg-amber-50 px-3.5 py-2.5 text-[11px] leading-relaxed text-amber-700">
                    Al enviar, tu unidad cambiará a estado <strong>emergencia</strong> y dejará de aparecer
                    como en servicio hasta que el administrador lo resuelva.
                </p>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="button" onclick="closeEmergencyModal()"
                        class="h-10 flex-1 rounded-full border border-slate-200 bg-white px-5 text-xs font-semibold text-[#5b6788] transition hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="h-10 flex-1 rounded-full bg-gradient-to-b from-[#e05a66] to-[#c02631] px-5 text-xs font-semibold text-white shadow-md shadow-[#c02631]/25 transition hover:brightness-105">
                        Enviar reporte
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEmergencyModal() {
            const modal = document.getElementById('emergency-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('emergency-type').focus();
        }

        function closeEmergencyModal() {
            const modal = document.getElementById('emergency-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Cerrar con Escape
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeEmergencyModal();
        });
    </script>
</x-passenger-layout>