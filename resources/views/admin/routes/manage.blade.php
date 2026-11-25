<x-passenger-layout title="Ubicaditos · Estudio de rutas" surface="admin" active="Estudio">
    <x-slot name="head">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin="">
    </x-slot>

    @php
        $input = 'w-full rounded-xl border border-slate-200/90 bg-white/90 px-3 py-2 text-[13px] text-slate-700 outline-none transition focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
        $button = 'rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-3 py-2 text-[12px] font-semibold text-white shadow-sm transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50';
        $softButton = 'rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-[12px] font-medium text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200';
    @endphp

    <div x-data="routeStudio(@js($studio))" x-init="init()" data-route-studio
         class="route-studio mx-auto max-w-[1500px] px-3 pb-8 pt-2 sm:px-6">
        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-csrf>

        @if(session('success'))
            <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-2 text-[13px] text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[.16em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                <h1 class="text-2xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-3xl">Estudio de rutas y paradas</h1>
                <p class="mt-1 text-[13px] text-slate-500 dark:text-gray-400">Diseña el recorrido sobre el mapa. Los cambios de coordenadas se guardan sólo al presionar Guardar.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="{{ $softButton }}" @click="refresh()" :disabled="loading">Actualizar</button>
                <a href="{{ route('admin.routes.create') }}" class="{{ $softButton }}">Nueva ruta (formulario)</a>
            </div>
        </div>

        <div class="grid gap-3 lg:grid-cols-[320px_minmax(0,1fr)]">
            {{-- Sidebar compacto --}}
            <aside class="studio-panel flex max-h-[calc(100vh-11rem)] flex-col overflow-hidden rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
                <div class="flex border-b border-slate-200/70 dark:border-gray-700">
                    <button type="button" class="flex-1 px-3 py-3 text-[13px] font-semibold transition" :class="panel === 'routes' ? 'text-[#3f4c75] dark:text-white border-b-2 border-[#6b7aa8]' : 'text-slate-500 dark:text-gray-400'" @click="panel = 'routes'">Rutas</button>
                    <button type="button" class="flex-1 px-3 py-3 text-[13px] font-semibold transition" :class="panel === 'stops' ? 'text-[#3f4c75] dark:text-white border-b-2 border-[#6b7aa8]' : 'text-slate-500 dark:text-gray-400'" @click="panel = 'stops'">Paradas</button>
                </div>

                <div x-show="panel === 'routes'" class="flex min-h-0 flex-1 flex-col">
                    <div class="p-3">
                        <label class="sr-only" for="studio-route-search">Buscar ruta</label>
                        <input id="studio-route-search" class="{{ $input }}" type="search" placeholder="Buscar ruta..." x-model="searchRoute">
                    </div>
                    <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-3">
                        <template x-for="route in filteredRoutes" :key="route.id">
                            <button type="button" class="flex w-full items-center gap-2 rounded-xl px-2 py-2 text-left transition hover:bg-slate-100/80 dark:hover:bg-gray-700/60" :class="route.id === selectedRouteId ? 'bg-[#6b7aa8]/15 ring-1 ring-[#6b7aa8]/40' : ''" @click="selectRoute(route.id)">
                                <span class="h-3 w-3 shrink-0 rounded-full ring-1 ring-white/70" :style="`background-color: ${route.color || '#64748b'}`"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-semibold text-[#2f3a5f] dark:text-gray-100" x-text="route.name"></span>
                                    <span class="block text-[11px] text-slate-500 dark:text-gray-400" x-text="route.code + ' · ' + route.stops.length + ' paradas'"></span>
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="route.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-200 text-slate-600 dark:bg-gray-600 dark:text-gray-300'" x-text="route.is_active ? 'Activa' : 'Inactiva'"></span>
                            </button>
                        </template>
                        <p x-show="filteredRoutes.length === 0" class="px-2 py-6 text-center text-[12px] text-slate-500 dark:text-gray-400">No hay rutas que coincidan con la búsqueda.</p>
                    </div>
                </div>

                <div x-show="panel === 'stops'" class="flex min-h-0 flex-1 flex-col" style="display: none;">
                    <div class="p-3">
                        <label class="sr-only" for="studio-stop-search">Buscar parada</label>
                        <input id="studio-stop-search" class="{{ $input }}" type="search" placeholder="Buscar parada..." x-model="searchStop">
                    </div>
                    <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-3">
                        <template x-for="stop in filteredStops" :key="stop.id">
                            <button type="button" class="flex w-full items-center justify-between gap-2 rounded-xl px-2 py-2 text-left transition hover:bg-slate-100/80 dark:hover:bg-gray-700/60" :class="stop.id === selectedStopId ? 'bg-[#6b7aa8]/15 ring-1 ring-[#6b7aa8]/40' : ''" @click="selectStop(stop.id)">
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-semibold text-[#2f3a5f] dark:text-gray-100" x-text="stop.name"></span>
                                    <span class="block truncate text-[11px] text-slate-500 dark:text-gray-400" x-text="stop.address || 'Sin dirección'"></span>
                                </span>
                                <span class="shrink-0 text-[10px] text-slate-400" x-text="stop.route_ids.length + ' rutas'"></span>
                            </button>
                        </template>
                        <p x-show="filteredStops.length === 0" class="px-2 py-6 text-center text-[12px] text-slate-500 dark:text-gray-400">No hay paradas que coincidan con la búsqueda.</p>
                    </div>
                </div>
            </aside>

            {{-- Mapa y panel de edición --}}
            <section class="min-w-0 space-y-3">
                <div class="studio-toolbar flex flex-wrap items-center gap-2 rounded-[20px] border border-white/80 bg-white/75 px-3 py-2 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
                    <template x-if="selectedRoute">
                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                            <span class="truncate text-[13px] font-semibold text-[#2f3a5f] dark:text-gray-100" x-text="selectedRoute.name"></span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600 dark:bg-gray-700 dark:text-gray-300" x-text="selectedRoute.code"></span>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400" x-text="(selectedRoute.estimated_duration || '—') + ' min'"></span>
                            <button type="button" class="{{ $softButton }}" @click="toggleRouteStatus()" :disabled="loading" x-text="selectedRoute.is_active ? 'Desactivar' : 'Activar'"></button>
                            <button type="button" class="{{ $softButton }}" @click="regenerate()" :disabled="loading">Regenerar trazado</button>
                            <button type="button" class="{{ $button }}" @click="saveSequence()" :disabled="loading">Guardar orden</button>
                        </div>
                    </template>
                    <template x-if="!selectedRoute">
                        <p class="text-[12px] text-slate-500 dark:text-gray-400">Selecciona una ruta para ver su trazado y sus paradas.</p>
                    </template>
                </div>

                <div class="relative overflow-hidden rounded-[24px] border border-white/80 bg-white/60 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] dark:border-white/10 dark:bg-gray-800/60">
                    <div id="route-studio-map" class="h-[420px] w-full sm:h-[520px] lg:h-[calc(100vh-19rem)]" aria-label="Mapa del estudio de rutas"></div>

                    <div x-show="loading" x-cloak class="absolute inset-0 z-[500] flex items-center justify-center bg-white/60 backdrop-blur-sm dark:bg-gray-900/60">
                        <div class="rounded-2xl bg-white/95 px-5 py-3 text-[13px] font-semibold text-[#3f4c75] shadow-lg dark:bg-gray-800 dark:text-gray-100">Cargando…</div>
                    </div>
                    <div x-show="error" x-cloak class="absolute left-3 right-3 top-3 z-[500] rounded-2xl border border-rose-200 bg-rose-50/95 px-4 py-2 text-[12px] text-rose-800 shadow dark:border-rose-800 dark:bg-rose-950/90 dark:text-rose-200" role="alert" x-text="error"></div>
                    <div x-show="success" x-cloak class="absolute bottom-3 left-3 z-[500] rounded-2xl border border-emerald-200 bg-emerald-50/95 px-4 py-2 text-[12px] text-emerald-800 shadow dark:border-emerald-800 dark:bg-emerald-950/90 dark:text-emerald-200" role="status" x-text="success"></div>
                    <div x-show="!loading && routes.length === 0 && stops.length === 0" x-cloak class="absolute inset-0 z-[400] flex items-center justify-center bg-white/70 dark:bg-gray-900/70">
                        <div class="max-w-xs rounded-2xl bg-white/95 p-5 text-center shadow dark:bg-gray-800">
                            <p class="text-[13px] font-semibold text-[#2f3a5f] dark:text-gray-100">Todavía no hay rutas ni paradas.</p>
                            <p class="mt-1 text-[12px] text-slate-500 dark:text-gray-400">Crea una ruta y haz clic en el mapa para ubicar la primera parada.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
                    {{-- Secuencia de paradas --}}
                    <div class="studio-panel rounded-[24px] border border-white/80 bg-white/75 p-4 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="text-[14px] font-semibold text-[#2f3a5f] dark:text-gray-100">Orden de paradas</h2>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400" x-text="sequence.length + ' paradas principales'"></span>
                        </div>

                        <template x-if="selectedRoute">
                            <div>
                                <div class="space-y-1">
                                    <template x-for="(stopId, index) in sequence" :key="stopId">
                                        <div class="flex items-center gap-2 rounded-xl bg-white/80 px-2 py-1.5 dark:bg-gray-700/50">
                                            <span class="w-5 text-center text-[11px] font-bold text-[#697aa8] dark:text-[#aab7df]" x-text="index + 1"></span>
                                            <span class="min-w-0 flex-1 truncate text-[12px] text-slate-700 dark:text-gray-200" x-text="stopName(stopId)"></span>
                                            <button type="button" class="rounded-lg px-2 py-1 text-[11px] text-slate-500 hover:bg-slate-100 disabled:opacity-30 dark:hover:bg-gray-600" :disabled="index === 0" @click="move(index, -1)" aria-label="Subir parada">↑</button>
                                            <button type="button" class="rounded-lg px-2 py-1 text-[11px] text-slate-500 hover:bg-slate-100 disabled:opacity-30 dark:hover:bg-gray-600" :disabled="index === sequence.length - 1" @click="move(index, 1)" aria-label="Bajar parada">↓</button>
                                            <button type="button" class="rounded-lg px-2 py-1 text-[11px] text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30" @click="removeFromSequence(index)" aria-label="Quitar parada">✕</button>
                                        </div>
                                    </template>
                                </div>
                                <p x-show="sequence.length === 0" class="py-4 text-center text-[12px] text-slate-500 dark:text-gray-400">Agrega paradas desde el mapa o desde la lista lateral.</p>

                                <div class="mt-3 border-t border-slate-200/70 pt-3 dark:border-gray-700">
                                    <label class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400" for="assign-stop">Agregar parada existente</label>
                                    <select id="assign-stop" class="{{ $input }}" @change="addToSequence($event.target.value); $event.target.value = ''">
                                        <option value="">Seleccionar…</option>
                                        <template x-for="stop in availableStops" :key="stop.id">
                                            <option :value="stop.id" x-text="stop.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div x-show="frequentStops().length > 0" class="mt-3 rounded-xl bg-amber-50/80 p-2 text-[11px] text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                    Paradas de alta frecuencia ancladas al trazado (se conservan al reordenar):
                                    <span x-text="frequentStops().map(s => s.name).join(', ')"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!selectedRoute">
                            <p class="py-4 text-center text-[12px] text-slate-500 dark:text-gray-400">Selecciona una ruta para editar su secuencia.</p>
                        </template>
                    </div>

                    {{-- Editor compacto de parada --}}
                    <div class="studio-panel rounded-[24px] border border-white/80 bg-white/75 p-4 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
                        <h2 class="mb-3 text-[14px] font-semibold text-[#2f3a5f] dark:text-gray-100" x-text="mode === 'create' ? 'Nueva parada en el mapa' : 'Editar parada'"></h2>
                        <div class="space-y-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400" for="stop-name">Nombre</label>
                                <input id="stop-name" class="{{ $input }}" type="text" x-model="draft.name" placeholder="Nombre de la parada">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400" for="stop-address">Dirección</label>
                                <input id="stop-address" class="{{ $input }}" type="text" x-model="draft.address" placeholder="Calle y número">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400" for="stop-lat">Latitud</label>
                                    <input id="stop-lat" class="{{ $input }}" type="number" step="0.0000001" min="-90" max="90" x-model="draft.latitude">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400" for="stop-lng">Longitud</label>
                                    <input id="stop-lng" class="{{ $input }}" type="number" step="0.0000001" min="-180" max="180" x-model="draft.longitude">
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">Haz clic en el mapa para elegir una ubicación o arrastra un marcador para ajustarla.</p>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <button type="button" class="{{ $button }}" @click="saveStop()" :disabled="loading">Guardar parada</button>
                                <button type="button" class="{{ $softButton }}" @click="resetDraft()">Limpiar</button>
                                <button x-show="mode === 'edit'" type="button" class="rounded-full px-3 py-2 text-[12px] font-medium text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/30" @click="deleteStop()" :disabled="loading">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
</x-passenger-layout>
