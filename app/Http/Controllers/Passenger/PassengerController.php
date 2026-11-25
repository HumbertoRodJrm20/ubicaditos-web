<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Incident, Route, Stop, Rating};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Http};

class PassengerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $favoriteStops = $user->favoriteStops()->with('routes')->get();
        $favoriteRoutes = $user->favoriteRoutes()->withCount('likes')->get();
        $incidentsCount = $user->incidents()->count();
        $pendingIncidentsCount = $user->incidents()
            ->where('status', Incident::STATUS_PENDING)
            ->count();

        return view('passenger.menu', compact(
            'favoriteStops',
            'favoriteRoutes',
            'incidentsCount',
            'pendingIncidentsCount',
        ));
    }

    public function map()
    {
        $routes = Route::with(['stops', 'buses' => function($query) {
            $query->where('status', 'active');
        }])->where('is_active', true)->get();

        $stops = Stop::withCount('likes')->where('is_active', true)->get();
        $buses = Bus::with(['driver', 'routes'])->where('status', 'active')->get();

        $likedStopIds = auth()->user()->likes()
            ->where('likeable_type', Stop::class)
            ->pluck('likeable_id');

        return view('passenger.dashboard', compact('routes', 'stops', 'buses', 'likedStopIds'));
    }

    /**
     * Búsqueda de rutas para el mapa del pasajero.
     *
     * Acepta nombres de ruta, calles, colonias, referencias y nombres de
     * parada. Primero busca en la información local (rutas, paradas y sus
     * direcciones geocodificadas). Si no encuentra paradas, consulta Nominatim
     * (OpenStreetMap) para interpretar calles o lugares y busca rutas cercanas.
     *
     * Parámetros: q (texto), lat y lng (ubicación del pasajero, opcionales).
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'results' => [],
                'geocoded' => null,
            ]);
        }

        $userLat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $userLng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        $routes = Route::query()
            ->with(['stops' => function ($stopQuery) {
                $stopQuery->where('stops.is_active', true)->select(
                    'stops.id',
                    'stops.name',
                    'stops.address',
                    'stops.latitude',
                    'stops.longitude'
                );
            }])
            ->where('is_active', true)
            ->get();

        $tokens = $this->searchTokens($query);

        [$results, $hasStopMatch, $hasNameMatch] = $this->matchRoutesByText(
            $routes,
            $tokens,
            $userLat,
            $userLng
        );

        $geocoded = null;

        // Si el texto no coincide con ninguna ruta ni parada, se intenta
        // interpretar como una calle, colonia o lugar con Nominatim.
        if (! $hasStopMatch && ! $hasNameMatch) {
            $geocoded = $this->geocodePlace($query);

            if ($geocoded !== null) {
                $results = $this->addRoutesNearPlace(
                    $routes,
                    $results,
                    $geocoded,
                    $userLat,
                    $userLng
                );
            }
        }

        $results = array_values($results);

        usort($results, function (array $a, array $b) {
            // Primero las coincidencias por nombre de ruta; después las rutas
            // con la parada más cercana al usuario o al lugar buscado.
            if ($a['name_match'] !== $b['name_match']) {
                return $a['name_match'] ? -1 : 1;
            }

            if ($a['text_score'] !== $b['text_score']) {
                return $a['text_score'] <=> $b['text_score'];
            }

            $matchComparison = ($a['match_distance_km'] ?? PHP_FLOAT_MAX)
                <=> ($b['match_distance_km'] ?? PHP_FLOAT_MAX);

            if ($matchComparison !== 0) {
                return $matchComparison;
            }

            return ($a['distance_km'] ?? PHP_FLOAT_MAX)
                <=> ($b['distance_km'] ?? PHP_FLOAT_MAX);
        });

        $results = array_slice($results, 0, 12);

        foreach ($results as &$result) {
            unset($result['name_match'], $result['text_score']);
        }
        unset($result);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'geocoded' => $geocoded ? ['label' => $geocoded['label']] : null,
        ]);
    }

    public function routes()
    {
        $routes = Route::with(['stops', 'buses'])
            ->withCount('likes')
            ->where('is_active', true)
            ->get();

        $user = auth()->user();

        $favoriteRouteIds = $user->favoriteRoutes()->pluck('routes.id');
        $likedRouteIds = $user->likes()
            ->where('likeable_type', Route::class)
            ->pluck('likeable_id');

        return view('passenger.routes', compact('routes', 'favoriteRouteIds', 'likedRouteIds'));
    }

    /**
     * Listado de autobuses con búsqueda y filtrado.
     * Parámetros: q (placa, modelo o conductor), route_id, status (active|all|...) y sort (plate|occupancy).
     */
    public function buses(Request $request)
    {
        $status = $request->query('status', 'active');
        $search = trim((string) $request->query('q'));
        $routeId = $request->query('route_id');
        $sort = $request->query('sort', 'plate');

        $buses = Bus::with(['driver', 'routes'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('license_plate', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('driver', function ($driverQuery) use ($search) {
                            $driverQuery->where(function ($q) use ($search) {
                                $q->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('maternal_last_name', 'like', "%{$search}%");
                            });
                        });
                });
            })
            ->when($routeId, fn ($query) => $query->whereHas(
                'routes',
                fn ($routes) => $routes->where('routes.id', $routeId)
            ))
            ->when(
                $sort === 'occupancy',
                fn ($query) => $query->orderByDesc('current_passengers'),
                fn ($query) => $query->orderBy('license_plate')
            )
            ->get();

        $routes = Route::where('is_active', true)->get();

        // Datos completos y verificables para el modal de detalles (Requisito 4)
        $busDetailsData = $buses->map(fn ($bus) => [
            'id' => $bus->id,
            'plate' => $bus->license_plate ?? '—',
            'model' => $bus->model ?? 'Modelo no especificado',
            'capacity' => $bus->capacity,
            'passengers' => $bus->current_passengers ?? 0,
            'available_seats' => $bus->available_seats,
            'status' => $bus->status,
            'route' => $bus->routes->first()?->name ?? 'Sin ruta asignada',
            'route_code' => $bus->routes->first()?->code ?? '',
            'route_color' => $bus->routes->first()?->color ?? '#6b7bad',
            'driver' => $bus->driver?->name ?? 'Sin conductor asignado',
            'last_update' => $bus->last_location_update?->diffForHumans() ?? 'Sin datos de ubicación',
            'map_url' => route('passenger.dashboard', ['bus_id' => $bus->id]),
        ])->values();

        return view('passenger.buses', compact('buses', 'routes', 'busDetailsData'));
    }

    public function addFavorite(Stop $stop)
    {
        // Check if already favorited
        if (auth()->user()->favoriteStops()->where('stop_id', $stop->id)->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Esta parada ya está en tus favoritos'], 400);
            }
            return back()->with('error', 'Esta parada ya está en tus favoritos');
        }

        // El alias es opcional: si no llega, se guarda el nombre real de la
        // parada para que el pasajero no tenga que escribir nada.
        $nickname = trim((string) request('nickname'));

        auth()->user()->favoriteStops()->attach($stop->id, [
            'nickname' => $nickname !== '' ? $nickname : $stop->name,
        ]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Parada agregada a favoritos']);
        }

        return back()->with('success', 'Parada agregada a favoritos');
    }

    public function removeFavorite(Stop $stop)
    {
        auth()->user()->favoriteStops()->detach($stop->id);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Parada eliminada de favoritos']);
        }

        return back()->with('success', 'Parada eliminada de favoritos');
    }

    public function rateDriver(Request $request, $driverId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        Rating::create([
            'passenger_id' => auth()->id(),
            'driver_id' => $driverId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Calificación enviada correctamente');
    }

    /**
     * Coincidencia local por nombre de ruta o por texto de parada/dirección.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: bool, 2: bool}
     */
    private function matchRoutesByText($routes, array $tokens, ?float $userLat, ?float $userLng): array
    {
        $results = [];
        $hasStopMatch = false;
        $hasNameMatch = false;
        $nameMatches = [];

        // Primera pasada: saber si el texto identifica alguna ruta por nombre.
        foreach ($routes as $route) {
            $routeText = $this->normalizeText(implode(' ', array_filter([
                $route->name,
                $route->code,
                $route->description,
            ])));

            $nameMatches[(int) $route->id] = $this->containsAllTokens($routeText, $tokens);
            $hasNameMatch = $hasNameMatch || $nameMatches[(int) $route->id];
        }

        // Si la consulta ya identifica una ruta por nombre ("Ruta 4"), se
        // omiten las coincidencias parciales en paradas: evita resultados
        // ruidosos de paradas que sólo comparten "ruta" o un número.
        $skipStopMatching = $hasNameMatch && in_array('ruta', $tokens, true);

        $tokenCount = count($tokens);
        $requiredStopTokens = $tokenCount <= 2
            ? $tokenCount
            : max(2, (int) ceil($tokenCount / 2));

        foreach ($routes as $route) {
            $routeId = (int) $route->id;
            $nameMatch = $nameMatches[$routeId];
            $bestTextStop = null;
            $bestTextDistance = INF;
            $bestTextScore = 2;

            if (! $skipStopMatching) {
                foreach ($route->stops as $stop) {
                    $stopText = $this->normalizeText(trim(($stop->name ?? '') . ' ' . ($stop->address ?? '')));
                    $matchedTokens = $this->matchingTokenCount($stopText, $tokens);

                    // Se acepta una coincidencia completa o, en búsquedas
                    // largas, al menos la mitad de las palabras (ej.
                    // "Universidad Autónoma Benito Juárez" puede coincidir con
                    // "UABJO · Universidad Autónoma").
                    if ($matchedTokens === 0 || $matchedTokens < $requiredStopTokens) {
                        continue;
                    }

                    $hasStopMatch = true;

                    // Las coincidencias completas siempre ganan. En las
                    // parciales se pondera el orden de las palabras buscadas:
                    // es más relevante "Universidad Autónoma" que "Benito
                    // Juárez" para la consulta "Universidad Autónoma Benito
                    // Juárez".
                    $score = $matchedTokens === $tokenCount
                        ? -1000
                        : -$this->matchingTokenWeight($stopText, $tokens);

                    $distance = $this->distanceToStop($userLat, $userLng, $stop);

                    if ($bestTextStop === null
                        || $score < $bestTextScore
                        || ($score === $bestTextScore && $distance < $bestTextDistance)) {
                        $bestTextStop = $stop;
                        $bestTextDistance = $distance;
                        $bestTextScore = $score;
                    }
                }
            }

            if (! $nameMatch && $bestTextStop === null) {
                continue;
            }

            if ($bestTextStop === null) {
                $bestTextStop = $this->closestStop($route->stops, $userLat, $userLng);
                $bestTextDistance = $this->distanceToStop($userLat, $userLng, $bestTextStop);
                $bestTextScore = 0;
            }

            $reason = $nameMatch
                ? 'Coincide con el nombre de la ruta'
                : 'Pasa por ' . ($bestTextStop->name ?? 'una parada del recorrido');

            $results[$routeId] = $this->buildSearchResult(
                $route,
                $bestTextStop,
                $nameMatch ? 'route' : 'stop',
                $reason,
                $userLat,
                $userLng,
                is_finite($bestTextDistance) ? $bestTextDistance : null,
                $nameMatch,
                $nameMatch ? 0 : $bestTextScore
            );
        }

        return [$results, $hasStopMatch, $hasNameMatch];
    }

    /**
     * Agrega rutas cuya parada o trazado esté cerca del lugar geocodificado.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    private function addRoutesNearPlace(
        $routes,
        array $results,
        array $place,
        ?float $userLat,
        ?float $userLng
    ): array {
        $label = $place['label'];

        foreach ($routes as $route) {
            $routeId = (int) $route->id;

            if (isset($results[$routeId])) {
                continue;
            }

            $nearestStop = $this->closestStop($route->stops, $place['lat'], $place['lng']);

            if ($nearestStop === null) {
                continue;
            }

            $distanceToStop = $this->distanceKm(
                $place['lat'],
                $place['lng'],
                (float) $nearestStop->latitude,
                (float) $nearestStop->longitude
            );

            $distanceToPath = $this->distanceToRoutePathKm(
                $place['lat'],
                $place['lng'],
                $route->path_coordinates
            );

            $stopIsNear = $distanceToStop <= 0.8;
            $pathIsNear = $distanceToPath !== null && $distanceToPath <= 0.35;

            // Se exige una parada razonablemente cercana para poder abordar.
            if ((! $stopIsNear && ! $pathIsNear) || $distanceToStop > 2.0) {
                continue;
            }

            $results[$routeId] = $this->buildSearchResult(
                $route,
                $nearestStop,
                $stopIsNear ? 'location_stop' : 'location_path',
                $stopIsNear
                    ? 'Tiene una parada cerca de ' . $label
                    : 'Su recorrido pasa cerca de ' . $label,
                $userLat,
                $userLng,
                min($distanceToStop, $distanceToPath ?? INF),
                false,
                2
            );
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSearchResult(
        Route $route,
        ?Stop $stop,
        string $matchType,
        string $reason,
        ?float $userLat,
        ?float $userLng,
        ?float $matchDistance,
        bool $nameMatch,
        int $textScore = 0
    ): array {
        $userDistance = $this->distanceToStop($userLat, $userLng, $stop);

        return [
            'id' => (int) $route->id,
            'name' => $route->name,
            'code' => $route->code,
            'color' => $route->color,
            'description' => $route->description,
            'match_type' => $matchType,
            'reason' => $reason,
            'stop' => $stop ? [
                'id' => (int) $stop->id,
                'name' => $stop->name,
                'address' => $stop->address,
                'latitude' => (float) $stop->latitude,
                'longitude' => (float) $stop->longitude,
            ] : null,
            'distance_km' => is_finite($userDistance) ? round($userDistance, 3) : null,
            'match_distance_km' => $matchDistance !== null && is_finite($matchDistance)
                ? round($matchDistance, 3)
                : null,
            'name_match' => $nameMatch,
            'text_score' => $textScore,
        ];
    }

    private function closestStop($stops, ?float $lat, ?float $lng): ?Stop
    {
        $closest = null;
        $closestDistance = INF;

        foreach ($stops as $stop) {
            if (! $this->hasValidCoordinates($stop)) {
                continue;
            }

            if ($lat === null || $lng === null) {
                return $stop;
            }

            $distance = $this->distanceKm(
                $lat,
                $lng,
                (float) $stop->latitude,
                (float) $stop->longitude
            );

            if ($distance < $closestDistance) {
                $closest = $stop;
                $closestDistance = $distance;
            }
        }

        return $closest;
    }

    private function distanceToStop(?float $lat, ?float $lng, ?Stop $stop): float
    {
        if ($lat === null || $lng === null || ! $this->hasValidCoordinates($stop)) {
            return INF;
        }

        return $this->distanceKm(
            $lat,
            $lng,
            (float) $stop->latitude,
            (float) $stop->longitude
        );
    }

    private function hasValidCoordinates($stop): bool
    {
        if ($stop === null || $stop->latitude === null || $stop->longitude === null) {
            return false;
        }

        $lat = (float) $stop->latitude;
        $lng = (float) $stop->longitude;

        return $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180
            && ! ($lat === 0.0 && $lng === 0.0);
    }

    /**
     * Distancia mínima (km) entre un punto y los vértices del trazado guardado.
     */
    private function distanceToRoutePathKm(float $lat, float $lng, $path): ?float
    {
        if (! is_array($path) || count($path) < 2) {
            return null;
        }

        $points = [];
        foreach ($path as $point) {
            if (is_array($point)) {
                // GeoJSON: [longitud, latitud]
                $pointLng = $point[0] ?? null;
                $pointLat = $point[1] ?? null;
            } else {
                $pointLat = $point['lat'] ?? $point['latitude'] ?? null;
                $pointLng = $point['lng'] ?? $point['longitude'] ?? null;
            }

            if (! is_numeric($pointLat) || ! is_numeric($pointLng)) {
                continue;
            }

            $pointLat = (float) $pointLat;
            $pointLng = (float) $pointLng;

            if ($pointLat >= -90 && $pointLat <= 90
                && $pointLng >= -180 && $pointLng <= 180
                && ! ($pointLat === 0.0 && $pointLng === 0.0)) {
                $points[] = [$pointLat, $pointLng];
            }
        }

        if (count($points) < 2) {
            return null;
        }

        // Para trazados muy densos se muestrea; la precisión sigue siendo
        // suficiente para decidir si la ruta pasa cerca de un lugar.
        $stride = max(1, (int) floor(count($points) / 500));
        $minimum = INF;
        $previous = null;

        foreach ($points as $index => $point) {
            if ($index % $stride !== 0) {
                continue;
            }

            $minimum = min($minimum, $this->distanceKm($lat, $lng, $point[0], $point[1]));

            if ($previous !== null) {
                $midpoint = [
                    ($previous[0] + $point[0]) / 2,
                    ($previous[1] + $point[1]) / 2,
                ];
                $minimum = min($minimum, $this->distanceKm($lat, $lng, $midpoint[0], $midpoint[1]));
            }

            $previous = $point;
        }

        return is_finite($minimum) ? $minimum : null;
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @return array<int, string>
     */
    private function searchTokens(string $query): array
    {
        $stopWords = [
            'de', 'del', 'la', 'las', 'el', 'los', 'y', 'en', 'al',
            'por', 'para', 'con', 'un', 'una', 'unos', 'unas', 'lo',
        ];

        $tokens = array_values(array_filter(
            explode(' ', $this->normalizeText($query)),
            function (string $token) use ($stopWords) {
                if (in_array($token, $stopWords, true)) {
                    return false;
                }

                return mb_strlen($token) >= 2 || ctype_digit($token);
            }
        ));

        if ($tokens === []) {
            $fallback = $this->normalizeText($query);
            $tokens = $fallback !== '' ? [$fallback] : [];
        }

        return $tokens;
    }

    private function containsAllTokens(string $haystack, array $tokens): bool
    {
        if ($haystack === '' || $tokens === []) {
            return false;
        }

        return $this->matchingTokenCount($haystack, $tokens) === count($tokens);
    }

    private function matchingTokenCount(string $haystack, array $tokens): int
    {
        if ($haystack === '' || $tokens === []) {
            return 0;
        }

        $matched = 0;

        foreach ($tokens as $token) {
            if ($this->haystackContainsToken($haystack, $token)) {
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * Pondera las palabras encontradas según su posición en la búsqueda.
     * Las primeras palabras describen mejor el lugar buscado.
     */
    private function matchingTokenWeight(string $haystack, array $tokens): int
    {
        $weight = 0;
        $count = count($tokens);

        foreach ($tokens as $index => $token) {
            if ($this->haystackContainsToken($haystack, $token)) {
                $weight += $count - $index;
            }
        }

        return $weight;
    }

    private function haystackContainsToken(string $haystack, string $token): bool
    {
        // Los números se comparan como palabra completa: "Ruta 4" no debe
        // coincidir con "Ruta 14".
        if (ctype_digit($token)) {
            return preg_match(
                '/(?<![a-z0-9])' . preg_quote($token, '/') . '(?![0-9])/',
                $haystack
            ) === 1;
        }

        return str_contains($haystack, $token);
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower((string) $value);
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
            'ò' => 'o', 'ù' => 'u',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        // Abreviaturas frecuentes en direcciones y en lo que escribe el
        // pasajero: "Av. Universidad" debe coincidir con "Avenida Universidad".
        $abbreviations = [
            'av' => 'avenida',
            'prol' => 'prolongacion',
            'col' => 'colonia',
            'cda' => 'cerrada',
            'blvd' => 'boulevard',
            'cto' => 'circuito',
        ];

        foreach ($abbreviations as $abbreviation => $fullWord) {
            $value = preg_replace('/\b' . $abbreviation . '\b/', $fullWord, $value) ?? $value;
        }

        return trim($value);
    }

    /**
     * Geocodifica una calle, colonia o lugar con Nominatim (OpenStreetMap).
     * Es gratuito y no requiere clave; se cachea un día para respetar el
     * límite de una petición por segundo del servicio.
     */
    private function geocodePlace(string $query): ?array
    {
        $cacheKey = 'passenger-geocode:' . sha1(mb_strtolower(trim($query)));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        // Nominatim permite como máximo una petición por segundo. Se respeta
        // ese límite de forma global para no bloquear la cuenta de la app.
        $lastRequest = (int) Cache::get('passenger-geocode:last-request', 0);

        if ($lastRequest > 0 && (time() - $lastRequest) < 1) {
            return null;
        }

        Cache::put('passenger-geocode:last-request', time(), now()->addMinute());

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'Ubicaditos') . '/1.0 (+' . config('app.url', 'http://localhost') . ')',
                'Accept-Language' => 'es',
            ])->timeout(5)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'countrycodes' => 'mx',
                // Sesgo hacia Oaxaca y su zona conurbada, sin limitar
                // búsquedas a lugares del resto del estado.
                'viewbox' => '-98.6,18.5,-95.5,15.6',
                'bounded' => 0,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $item = $response->json('0');

            if (! is_array($item) || ! isset($item['lat'], $item['lon'])) {
                return null;
            }

            $label = trim((string) ($item['display_name'] ?? $query));
            $labelParts = array_map('trim', explode(',', $label));
            $label = implode(', ', array_slice($labelParts, 0, 3));

            if (mb_strlen($label) > 90) {
                $label = mb_substr($label, 0, 87) . '…';
            }

            $place = [
                'lat' => (float) $item['lat'],
                'lng' => (float) $item['lon'],
                'label' => $label,
            ];

            Cache::put($cacheKey, $place, now()->addDay());

            return $place;
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
