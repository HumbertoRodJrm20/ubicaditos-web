@php
    $cardClass = 'rounded-[28px] border border-white/70 bg-white/80 p-4 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl';
    $labelClass = 'text-[11px] text-[#5b6788]';
    $valueClass = 'text-xl font-bold text-[#1e2a4a]';
    $muted = 'text-[#5b6788]';

    $kpiTiles = [
        ['label' => 'Viajes completados', 'value' => number_format($stats['completed_trips']), 'hint' => number_format($stats['total_trips']) . ' en total'],
        ['label' => 'Pasajeros', 'value' => number_format($stats['total_passengers']), 'hint' => 'transportados'],
        ['label' => 'Ocupación promedio', 'value' => number_format($stats['avg_occupancy'], 1) . '%', 'hint' => 'por viaje'],
        ['label' => 'Calificación', 'value' => number_format($stats['avg_rating'], 1) . ' ★', 'hint' => number_format($stats['ratings_count']) . ' calificaciones'],
        ['label' => 'Distancia', 'value' => number_format($stats['total_distance_km'], 1) . ' km', 'hint' => 'recorrida'],
        ['label' => 'Tiempo al volante', 'value' => floor($stats['total_minutes'] / 60) . ' h ' . ($stats['total_minutes'] % 60) . ' min', 'hint' => number_format($stats['avg_minutes']) . ' min por viaje'],
        ['label' => 'Viaje activo', 'value' => number_format($stats['active_trips']), 'hint' => 'en curso ahora'],
        ['label' => 'Incidencias', 'value' => number_format($stats['emergencies_reported']), 'hint' => 'reportadas por ti'],
    ];

    $maxDayTrips = max(1, (int) $stats['by_day']->max());
    $totalDistance = max(0.01, $stats['total_distance_km']);
@endphp

<x-passenger-layout title="Ubicaditos · Mis estadísticas">
    <div class="space-y-4">
        <div>
            <h1 class="text-[27px] font-bold leading-tight text-[#1e2a4a]">Mis estadísticas</h1>
            <p class="text-[11px] text-slate-500">Resumen de tu desempeño como conductor</p>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($kpiTiles as $tile)
                <div class="{{ $cardClass }} !p-4">
                    <p class="{{ $labelClass }}">{{ $tile['label'] }}</p>
                    <p class="{{ $valueClass }} mt-1 leading-tight">{{ $tile['value'] }}</p>
                    <p class="mt-0.5 text-[10px] text-[#8a94b3]">{{ $tile['hint'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Viajes por día --}}
        <section class="{{ $cardClass }}">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-[15px] font-bold text-[#1e2a4a]">Viajes de los últimos 7 días</h2>
                    <p class="text-[11px] {{ $muted }}">Viajes iniciados por fecha</p>
                </div>
                <span class="rounded-full bg-[#eef2fa] px-3 py-1 text-[10px] font-semibold text-[#5b6788]">
                    {{ number_format($stats['by_day']->sum()) }} viajes
                </span>
            </div>

            @if ($stats['by_day']->isEmpty())
                <p class="rounded-2xl bg-[#f7f9fd] px-4 py-8 text-center text-xs {{ $muted }}">
                    Aún no tienes viajes registrados en esta semana.
                </p>
            @else
                <div class="flex h-36 items-end gap-2">
                    @foreach ($stats['by_day'] as $date => $total)
                        @php
                            $height = max(8, (int) round(($total / $maxDayTrips) * 100));
                            $dayLabel = \Carbon\Carbon::parse($date)->locale('es')->isoFormat('dd');
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5" title="{{ $dayLabel }}: {{ $total }} viajes">
                            <span class="text-[10px] font-semibold text-[#5b6788]">{{ $total }}</span>
                            <div class="w-full rounded-t-lg bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a]"
                                 style="height: {{ $height }}%"></div>
                            <span class="text-[10px] capitalize {{ $muted }}">{{ $dayLabel }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- Rutas más frecuentes --}}
            <section class="{{ $cardClass }}">
                <h2 class="mb-3 text-[15px] font-bold text-[#1e2a4a]">Rutas más frecuentes</h2>

                @if ($stats['by_route']->isEmpty())
                    <p class="rounded-2xl bg-[#f7f9fd] px-4 py-8 text-center text-xs {{ $muted }}">
                        Completa viajes para ver tus rutas principales.
                    </p>
                @else
                    <ul class="space-y-2">
                        @foreach ($stats['by_route'] as $entry)
                            @php
                                $share = $stats['completed_trips'] > 0
                                    ? (int) round(($entry->total / max(1, $stats['completed_trips'])) * 100)
                                    : 0;
                            @endphp
                            <li class="rounded-2xl bg-[#f7f9fd] px-3 py-2.5">
                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <span class="min-w-0 truncate font-semibold text-[#1e2a4a]">
                                        {{ $entry->route->name ?? 'Ruta eliminada' }}
                                    </span>
                                    <span class="shrink-0 font-semibold text-[#5b6788]">
                                        {{ $entry->total }} viajes · {{ $share }}%
                                    </span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full"
                                         style="width: {{ $share }}%; background-color: {{ $entry->route->color ?? '#6b7bad' }}"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Calificaciones recientes --}}
            <section class="{{ $cardClass }}">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-[15px] font-bold text-[#1e2a4a]">Calificaciones recientes</h2>
                    <span class="rounded-full bg-[#eef2fa] px-3 py-1 text-[10px] font-semibold text-[#5b6788]">
                        {{ number_format($stats['avg_rating'], 1) }} ★ promedio
                    </span>
                </div>

                @if ($recentRatings->isEmpty())
                    <p class="rounded-2xl bg-[#f7f9fd] px-4 py-8 text-center text-xs {{ $muted }}">
                        Todavía no recibes calificaciones de pasajeros.
                    </p>
                @else
                    <ul class="space-y-2">
                        @foreach ($recentRatings as $rating)
                            <li class="flex items-center gap-3 rounded-2xl bg-[#f7f9fd] px-3 py-2.5">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-[11px] font-bold text-white">
                                    {{ collect(explode(' ', trim($rating->passenger->name ?? '—')))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-[#1e2a4a]">{{ $rating->passenger->name ?? 'Pasajero' }}</p>
                                    <p class="text-[10px] {{ $muted }}">{{ $rating->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-bold text-[#efb45e]">{{ number_format($rating->rating, 1) }} ★</p>
                                    @if ($rating->comment)
                                        <p class="max-w-[140px] truncate text-[10px] {{ $muted }}" title="{{ $rating->comment }}">{{ $rating->comment }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('driver.dashboard') }}"
                class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-full border border-white/70 bg-white/85 px-5 text-xs font-semibold text-[#3d4a6b] shadow-sm transition hover:bg-white">
                Volver al panel
            </a>
            <a href="{{ route('driver.map') }}"
                class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/25 transition hover:brightness-105">
                Ver mi ruta en el mapa
            </a>
        </div>
    </div>
</x-passenger-layout>
