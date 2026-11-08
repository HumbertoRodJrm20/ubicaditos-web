@php
    $favoriteRouteIds = collect($favoriteRouteIds);
    $likedRouteIds = collect($likedRouteIds);

    // Una consulta por ruta: autobuses activos + paradas ordenadas por su posición en la ruta
    $cards = $routes->map(function ($route) {
        return [
            'route'  => $route,
            'active' => $route->buses()->where('status', 'active')->count(),
            'stops'  => $route->stops->sortBy('pivot.order')->values(),
        ];
    });

    // Datos mínimos para filtrar del lado del cliente (búsqueda + chips)
    $filterData = $cards->map(fn ($c) => [
        'id'       => $c['route']->id,
        'name'     => mb_strtolower($c['route']->name ?? ''),
        'code'     => mb_strtolower($c['route']->code ?? ''),
        'active'   => $c['active'] > 0,
        'favorite' => $favoriteRouteIds->contains($c['route']->id),
    ])->values();

    $pill = 'inline-flex items-center gap-1.5 rounded-full bg-[#eef2fa] px-2.5 py-1 text-[11px] font-medium text-[#3d4a6b]';
    $chipBase = 'inline-flex h-8 items-center gap-1.5 rounded-full px-3.5 text-xs font-semibold transition focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25';
    $chipOn = 'bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/25';
    $chipOff = 'border border-white/70 bg-white/80 text-[#3d4a6b] hover:bg-white';
@endphp
<x-passenger-layout title="Rutas · Ubicaditos">

    @if ($cards->isEmpty())
        {{-- Sin rutas configuradas --}}
        <div class="mx-auto mt-10 max-w-md rounded-[28px] border border-white/70 bg-white/80 p-10 text-center shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/30">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="6" cy="18" r="2.2" /><circle cx="18" cy="6" r="2.2" />
                    <path d="M8.2 18H14a3 3 0 0 0 0-6h-4a3 3 0 0 1 0-6h5.8" />
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-bold">No hay rutas disponibles</h3>
            <p class="mt-1 text-xs text-[#5b6788]">Las rutas aparecerán aquí una vez que el administrador las configure.</p>
        </div>
    @else
        <div x-data="routeList(@js($filterData))" @route-favorite-toggled.window="setFavorite($event.detail.id, $event.detail.favorite)">

            {{-- Encabezado + búsqueda --}}
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[28px] font-bold leading-tight tracking-tight sm:text-3xl">Rutas disponibles</h1>
                    <p class="mt-1 text-xs text-[#5b6788]">Elige una ruta para ver sus paradas y autobuses</p>
                </div>

                <div class="relative w-full sm:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[#8a94b3]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    </span>
                    <input type="text" x-model="q" aria-label="Buscar ruta" placeholder="Buscar ruta"
                        class="h-11 w-full rounded-full border border-white/70 bg-white/85 pl-11 pr-4 text-sm text-[#1e2a4a] shadow-lg shadow-[#4a5a8a]/10 backdrop-blur-xl placeholder:text-[#8a94b3] focus:border-[#6b7bad] focus:ring-4 focus:ring-[#6b7bad]/25" />
                </div>
            </header>

            {{-- Chips de filtro --}}
            <div class="mt-5 flex flex-wrap gap-2">
                <button type="button" @click="chip = 'all'"
                    :class="chip === 'all' ? '{{ $chipOn }}' : '{{ $chipOff }}'"
                    class="{{ $chipBase }}">
                    Todas
                </button>

                <button type="button" @click="chip = 'active'"
                    :class="chip === 'active' ? '{{ $chipOn }}' : '{{ $chipOff }}'"
                    class="{{ $chipBase }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="13" rx="3" /><path d="M4 11h16" /><path d="M7 17v2M17 17v2" /></svg>
                    Con autobuses activos
                </button>

                {{-- TODO: conectar la lógica de "Más cercanas" más adelante (por ahora solo visual) --}}
                <button type="button" title="Próximamente" class="{{ $chipBase }} {{ $chipOff }} cursor-not-allowed opacity-70">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3" /><circle cx="12" cy="12" r="7.5" /><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22" /></svg>
                    Más cercanas
                </button>

                <button type="button" @click="chip = 'favorites'"
                    :class="chip === 'favorites' ? '{{ $chipOn }}' : '{{ $chipOff }}'"
                    class="{{ $chipBase }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z" /></svg>
                    Favoritas
                </button>
            </div>

            {{-- Tarjetas --}}
            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($cards as $card)
                    @php
                        $route = $card['route'];
                        $color = $route->color ?: '#6b7bad';
                        $stops = $card['stops'];
                        $n = $stops->count();
                        $busCount = $card['active'];
                        $isLiked = $likedRouteIds->contains($route->id);
                        $isFavorite = $favoriteRouteIds->contains($route->id);
                        $likeCount = (int) ($route->likes_count ?? 0);

                        // Primera, intermedia y última parada (si hay 3 o menos, todas)
                        $preview = $n <= 3
                            ? $stops
                            : collect([$stops->first(), $stops->get(intdiv($n, 2)), $stops->last()]);
                    @endphp

                    <article x-show="show({{ $loop->index }})"
                        class="flex min-h-[176px] flex-col justify-between gap-6 rounded-3xl border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">

                        {{-- Encabezado de la tarjeta --}}
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-md shadow-[#4a5a8a]/25"
                                style="background-color: {{ $color }}; background-image: linear-gradient(135deg, rgba(255,255,255,.28), rgba(0,0,0,.14));">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="6" cy="18" r="2.2" /><circle cx="18" cy="6" r="2.2" />
                                    <path d="M8.2 18H14a3 3 0 0 0 0-6h-4a3 3 0 0 1 0-6h5.8" />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-[15px] font-bold leading-tight">{{ $route->name }}</h3>
                                @if ($route->code)
                                    <p class="mt-0.5 truncate text-[11px] text-[#8a94b3]">Código {{ $route->code }}</p>
                                @endif
                            </div>

                            @if ($busCount > 0)
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-600 ring-1 ring-emerald-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Activa
                                </span>
                            @else
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-500 ring-1 ring-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Sin servicio
                                </span>
                            @endif
                        </div>

                        {{-- Recorrido: primera → intermedia → última parada --}}
                        <div class="flex items-center gap-2 text-[11px] text-[#5b6788]">
                            @forelse ($preview as $stop)
                                @unless ($loop->first)
                                    <span class="h-[2px] w-4 shrink-0 rounded-full" style="background-color: {{ $color }}"></span>
                                @endunless
                                <span class="flex min-w-0 items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background-color: {{ $color }}"></span>
                                    <span class="truncate">{{ $stop->name }}</span>
                                </span>
                            @empty
                                <span class="italic text-[#8a94b3]">Sin paradas registradas</span>
                            @endforelse
                        </div>

                        {{-- Recorrido completo: se despliega sin salir de la tarjeta --}}
                        @if ($n > 3)
                            <details class="group -mt-2 rounded-2xl border border-slate-100 bg-white/60 px-3 py-2">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-[11px] font-semibold text-[#4b5a85] marker:hidden">
                                    <span>Ver las {{ $n }} paradas</span>
                                    <svg class="h-3.5 w-3.5 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                                </summary>
                                <ol class="mt-2 space-y-1.5">
                                    @foreach ($stops as $stop)
                                        <li class="flex items-start gap-2 text-[11px] text-[#5b6788]">
                                            <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white" style="background-color: {{ $color }}">{{ $loop->iteration }}</span>
                                            <span class="min-w-0">{{ $stop->name }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        @endif

                        {{-- Pie: conteos + acciones + botón --}}
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="{{ $pill }}"><span class="h-1 w-1 rounded-full bg-[#6b7bad]"></span>{{ $n }} {{ $n == 1 ? 'parada' : 'paradas' }}</span>
                                <span class="{{ $pill }}"><span class="h-1 w-1 rounded-full bg-[#6b7bad]"></span>{{ $busCount }} {{ $busCount == 1 ? 'autobús' : 'autobuses' }}</span>
                            </div>

                            <div class="flex shrink-0 items-center gap-1.5">
                                <button type="button"
                                    data-like-route="{{ $route->id }}"
                                    data-liked="{{ $isLiked ? 'true' : 'false' }}"
                                    aria-pressed="{{ $isLiked ? 'true' : 'false' }}"
                                    aria-label="Me gusta de {{ $route->name }}"
                                    onclick="toggleRouteLike({{ $route->id }}, this)"
                                    class="inline-flex h-8 items-center gap-1 rounded-full border px-2.5 text-[11px] font-semibold transition focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25 {{ $isLiked ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z" /></svg>
                                    <span data-like-count>{{ $likeCount }}</span>
                                </button>

                                <button type="button"
                                    data-favorite-route="{{ $route->id }}"
                                    data-favorite="{{ $isFavorite ? 'true' : 'false' }}"
                                    aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                                    aria-label="Ruta favorita {{ $route->name }}"
                                    onclick="toggleFavoriteRoute({{ $route->id }}, this)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border transition focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25 {{ $isFavorite ? 'border-rose-200 bg-rose-50 text-rose-500' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z" /></svg>
                                </button>

                                <a href="{{ route('passenger.dashboard', ['route_id' => $route->id]) }}"
                                    class="inline-flex h-8 shrink-0 items-center rounded-full border border-slate-200 bg-white px-4 text-xs font-semibold text-[#1e2a4a] shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25">
                                    Ver mapa
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Sin resultados --}}
            <div x-show="empty" x-cloak class="mt-10 text-center">
                <p class="text-sm text-[#5b6788]">No se encontraron rutas con los filtros seleccionados.</p>
            </div>
        </div>
    @endif

    <style>
        [data-liked="true"] svg,
        [data-favorite="true"] svg { fill: currentColor; }
    </style>

    <script>
        const ROUTES_CSRF = '{{ csrf_token() }}';

        // Filtro del lado del cliente: búsqueda por nombre o código + chips
        function routeList(items) {
            return {
                items: items,
                q: '',
                chip: 'all', // 'all' | 'active' | 'favorites'

                show(i) {
                    const item = this.items[i];
                    if (this.chip === 'active' && !item.active) return false;
                    if (this.chip === 'favorites' && !item.favorite) return false;

                    const q = this.q.trim().toLowerCase();
                    return !q || item.name.includes(q) || item.code.includes(q);
                },

                setFavorite(id, favorite) {
                    const item = this.items.find(entry => entry.id === id);
                    if (item) item.favorite = favorite;
                },

                get empty() {
                    return !this.items.some((_, i) => this.show(i));
                }
            }
        }

        function paintRouteLikeButton(button, liked) {
            const active = ['border-amber-200', 'bg-amber-50', 'text-amber-600'];
            const idle = ['border-slate-200', 'bg-white', 'text-slate-500', 'hover:bg-slate-50'];

            (liked ? idle : active).forEach(clazz => button.classList.remove(clazz));
            (liked ? active : idle).forEach(clazz => button.classList.add(clazz));

            button.dataset.liked = liked ? 'true' : 'false';
            button.setAttribute('aria-pressed', liked ? 'true' : 'false');
        }

        function paintRouteFavoriteButton(button, favorite) {
            const active = ['border-rose-200', 'bg-rose-50', 'text-rose-500'];
            const idle = ['border-slate-200', 'bg-white', 'text-slate-500', 'hover:bg-slate-50'];

            (favorite ? idle : active).forEach(clazz => button.classList.remove(clazz));
            (favorite ? active : idle).forEach(clazz => button.classList.add(clazz));

            button.dataset.favorite = favorite ? 'true' : 'false';
            button.setAttribute('aria-pressed', favorite ? 'true' : 'false');
        }

        window.toggleRouteLike = function (routeId, button) {
            button.disabled = true;

            fetch(`/likes/routes/${routeId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': ROUTES_CSRF,
                },
            })
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo actualizar el like');
                    return response.json();
                })
                .then(data => {
                    paintRouteLikeButton(button, data.liked);
                    const counter = button.querySelector('[data-like-count]');
                    if (counter) counter.textContent = data.count;
                })
                .catch(error => console.error('Like de ruta:', error))
                .finally(() => { button.disabled = false; });
        };

        window.toggleFavoriteRoute = function (routeId, button) {
            const isFavorite = button.dataset.favorite === 'true';
            button.disabled = true;

            fetch(`/passenger/favorite-routes/${routeId}`, {
                method: isFavorite ? 'DELETE' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': ROUTES_CSRF,
                },
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || data.error) {
                        throw new Error(data.error || 'No se pudo actualizar la ruta favorita');
                    }

                    paintRouteFavoriteButton(button, data.favorite);

                    window.dispatchEvent(new CustomEvent('route-favorite-toggled', {
                        detail: { id: routeId, favorite: data.favorite },
                    }));
                })
                .catch(error => {
                    console.error('Ruta favorita:', error);
                    alert('No se pudo actualizar la ruta favorita.');
                })
                .finally(() => { button.disabled = false; });
        };
    </script>
</x-passenger-layout>