@php
    $user = auth()->user();
    $firstName = $user->first_name ?? \Illuminate\Support\Str::before($user->name ?? '', ' ');

    // TODO: conectar "Tu próximo autobús". Si el controlador manda $nextBus con esta forma,
    // la tarjeta se llena sola:
    //   ['minutes' => 4, 'unit' => '204', 'route' => 'Ruta 2', 'destination' => 'Universidad']
    $next = $nextBus ?? null;

    $count = $favoriteStops->count();

    $card = 'group relative flex min-h-[150px] flex-col justify-between overflow-hidden rounded-3xl p-5 text-white shadow-xl shadow-[#4a5a8a]/25 transition hover:-translate-y-0.5 hover:shadow-2xl focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/40';
    $iconTile = 'flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur';
    $arrow = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-[#1e2a4a] shadow-md transition group-hover:translate-x-0.5';
    $glass = 'rounded-3xl border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl';
    $btn = 'flex h-11 w-full items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 active:scale-[.99]';
@endphp
<x-passenger-layout title="Inicio · Ubicaditos">

    {{-- Saludo --}}
    <header>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Hola{{ $firstName ? ', ' . $firstName : '' }}</h1>
        <p class="mt-1 text-xs text-[#5b6788]">¿A dónde vamos hoy? Elige una opción para comenzar.</p>
    </header>

    @if (session('success'))
        <div class="mt-5 flex items-center gap-2 rounded-xl bg-emerald-50 px-3.5 py-3 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100" role="status">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Accesos rápidos --}}
    <div class="mt-6 grid gap-4 md:grid-cols-3">

        <a href="{{ route('passenger.dashboard') }}" class="{{ $card }} bg-gradient-to-br from-[#8f9bcb] to-[#4a5a8a]">
            <div class="{{ $iconTile }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" /></svg>
            </div>
            <div class="mt-6 flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold leading-tight">Mapa en vivo</h2>
                    <p class="mt-1 text-[11px] text-white/80">Ve los autobuses moverse</p>
                </div>
                <span class="{{ $arrow }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('passenger.routes') }}" class="{{ $card }} bg-gradient-to-br from-[#b4c8e6] to-[#6f86b8]">
            <div class="{{ $iconTile }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="18" r="2.2" /><circle cx="18" cy="6" r="2.2" /><path d="M8.2 18H14a3 3 0 0 0 0-6h-4a3 3 0 0 1 0-6h5.8" /></svg>
            </div>
            <div class="mt-6 flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold leading-tight">Rutas</h2>
                    <p class="mt-1 text-[11px] text-white/80">Consulta recorridos y paradas</p>
                </div>
                <span class="{{ $arrow }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('passenger.buses') }}" class="{{ $card }} bg-gradient-to-br from-[#66749f] to-[#1e2a4a]">
            <div class="{{ $iconTile }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="13" rx="3" /><path d="M4 11h16" /><path d="M7 17v2M17 17v2" /></svg>
            </div>
            <div class="mt-6 flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold leading-tight">Autobuses</h2>
                    <p class="mt-1 text-[11px] text-white/80">Conductores y calificaciones</p>
                </div>
                <span class="{{ $arrow }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                </span>
            </div>
        </a>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3 lg:items-start">

        {{-- Paradas favoritas --}}
        <section class="{{ $glass }} lg:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold">Paradas favoritas</h2>
                    <p class="mt-0.5 text-[11px] text-[#5b6788]">Acceso rápido a las paradas que más usas</p>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#eef2fa] px-2.5 py-1 text-[11px] font-medium text-[#3d4a6b]">
                    <span class="h-1 w-1 rounded-full bg-[#6b7bad]"></span>{{ $count }} {{ $count == 1 ? 'guardada' : 'guardadas' }}
                </span>
            </div>

            @if ($count > 0)
                <ul class="mt-4 space-y-2.5">
                    @foreach ($favoriteStops as $stop)
                        @php
                            $stopRoutes = $stop->routes ?? collect();
                            $color = $stopRoutes->first()?->color ?: '#6b7bad';
                            $nickname = $stop->pivot->nickname ?? null;
                            $title = $nickname ?: $stop->name;

                            // Subtítulo: rutas que pasan por la parada; si no hay, el nombre real o la dirección
                            $routeNames = $stopRoutes->pluck('name');
                            if ($routeNames->isNotEmpty()) {
                                $subtitle = $routeNames->take(2)->join(', ')
                                    . ($routeNames->count() > 2 ? ' +' . ($routeNames->count() - 2) : '');
                            } else {
                                $subtitle = ($nickname && $nickname !== $stop->name) ? $stop->name : $stop->address;
                            }
                        @endphp

                        <li class="flex items-center gap-3 rounded-2xl bg-white px-3 py-2.5 shadow-sm ring-1 ring-slate-100">
                            <a href="{{ route('passenger.dashboard', ['stop_id' => $stop->id]) }}"
                                class="flex min-w-0 flex-1 items-center gap-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white shadow-md shadow-[#4a5a8a]/20"
                                    style="background-color: {{ $color }}; background-image: linear-gradient(135deg, rgba(255,255,255,.28), rgba(0,0,0,.14));">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-bold text-[#1e2a4a]">{{ $title }}</p>
                                    <p class="truncate text-[11px] text-[#6b7699]">{{ $subtitle }}</p>
                                </div>
                            </a>

                            <form method="POST" action="{{ route('passenger.favorites.remove', $stop) }}" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Eliminar de favoritos" aria-label="Eliminar de favoritos"
                                    onclick="return confirm('¿Eliminar esta parada de favoritos?')"
                                    class="flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6" /></svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-4 rounded-2xl bg-white/70 px-4 py-10 text-center ring-1 ring-slate-100">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold">No tienes paradas favoritas todavía</p>
                    <p class="mt-1 text-[11px] text-[#5b6788]">Agrega tus paradas frecuentes desde el mapa haciendo clic en una parada</p>
                    <a href="{{ route('passenger.dashboard') }}"
                        class="mt-4 inline-flex h-9 items-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/30 transition hover:brightness-110">
                        Ir al mapa
                    </a>
                </div>
            @endif
        </section>

        {{-- Tu próximo autobús --}}
        <aside class="{{ $glass }}">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-[#5b6788]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                <h2 class="text-sm font-semibold">Tu próximo autobús</h2>
            </div>

            @if ($next)
                <p class="mt-4 text-5xl font-bold tracking-tight text-[#4a5a8a]">{{ $next['minutes'] }} min</p>
                <p class="mt-3 text-[11px] text-[#5b6788]">Unidad {{ $next['unit'] }} · {{ $next['route'] }} hacia {{ $next['destination'] }}</p>
            @else
                <p class="mt-4 text-5xl font-bold tracking-tight text-[#c3cbe0]">—</p>
                <p class="mt-3 text-[11px] text-[#5b6788]">Aún no hay un autobús próximo para mostrar.</p>
            @endif

            <a href="{{ route('passenger.dashboard') }}" class="{{ $btn }} mt-5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" /></svg>
                Ver en el mapa
            </a>
        </aside>
    </div>
</x-passenger-layout>