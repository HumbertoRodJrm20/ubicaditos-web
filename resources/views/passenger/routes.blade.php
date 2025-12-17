<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Rutas Disponibles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($routes->count() > 0)
                <!-- Buscador de Rutas -->
                <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="route-search"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="Buscar rutas por nombre o código..."
                                   onkeyup="searchRoutes()">
                        </div>
                    </div>
                </div>

                <div id="routes-grid" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($routes as $route)
                        <div class="route-card bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                             data-name="{{ strtolower($route->name) }}"
                             data-code="{{ strtolower($route->code ?? '') }}">
                            <div class="p-6">
                                <!-- Encabezado de la Ruta -->
                                <div class="flex items-start mb-4">
                                    <div class="w-6 h-6 rounded-full flex-shrink-0 mr-3" style="background-color: {{ $route->color ?? '#3388ff' }}"></div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $route->name }}</h3>
                                        @if($route->code)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Código: {{ $route->code }}</p>
                                        @endif
                                        @if($route->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $route->description }}</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Información de la Ruta -->
                                <div class="grid grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Paradas</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $route->stops->count() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Duración estimada</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">~{{ $route->estimated_duration }} min</p>
                                    </div>
                                </div>

                                <!-- Autobuses Activos -->
                                @php
                                    $activeBuses = $route->buses()->where('status', 'active')->get();
                                @endphp
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Autobuses en servicio: {{ $activeBuses->count() }}
                                    </h4>
                                    @if($activeBuses->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($activeBuses as $bus)
                                                <div class="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bus->license_plate }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay autobuses activos en esta ruta</p>
                                    @endif
                                </div>

                                <!-- Paradas -->
                                @if($route->stops->count() > 0)
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recorrido</h4>
                                        <div class="relative">
                                            <!-- Línea vertical -->
                                            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300 dark:bg-gray-600"></div>

                                            <div class="space-y-4">
                                                @foreach($route->stops as $index => $stop)
                                                    <div class="flex items-start relative">
                                                        <!-- Círculo de la parada -->
                                                        <div class="relative z-10 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold text-white"
                                                             style="background-color: {{ $route->color ?? '#3388ff' }}">
                                                            {{ $index + 1 }}
                                                        </div>

                                                        <!-- Información de la parada -->
                                                        <div class="ml-4 flex-1 pb-4">
                                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $stop->name }}</p>
                                                            @if($stop->address)
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stop->address }}</p>
                                                            @endif
                                                            @if($index < $route->stops->count() - 1 && $stop->pivot->estimated_time_from_previous)
                                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                                    ↓ ~{{ $stop->pivot->estimated_time_from_previous }} min a la siguiente parada
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Botón Ver en Mapa -->
                                <div class="mt-4">
                                    <a href="{{ route('passenger.dashboard', ['route_id' => $route->id]) }}"
                                       class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                        </svg>
                                        Ver en Mapa
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No hay rutas disponibles</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Las rutas aparecerán aquí una vez que el administrador las configure.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function searchRoutes() {
            const searchTerm = document.getElementById('route-search').value.toLowerCase();
            const routeCards = document.querySelectorAll('.route-card');

            routeCards.forEach(card => {
                const name = card.dataset.name;
                const code = card.dataset.code;

                const matchesSearch = name.includes(searchTerm) || code.includes(searchTerm);

                card.style.display = matchesSearch ? 'block' : 'none';
            });
        }
    </script>
</x-app-layout>
