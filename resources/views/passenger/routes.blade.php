<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Rutas Disponibles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($routes->count() > 0)
                <!-- Rutas Disponibles con Autobuses Activos -->
                <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 overflow-hidden shadow-sm sm:rounded-lg border-2 border-green-200 dark:border-green-800">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100">🚍 Rutas Disponibles Ahora</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Rutas con autobuses activos en servicio</p>
                            </div>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                {{ $routes->filter(function($route) { return $route->buses()->where('status', 'active')->count() > 0; })->count() }} disponibles
                            </span>
                        </div>

                        @php
                            $availableRoutes = $routes->filter(function($route) {
                                return $route->buses()->where('status', 'active')->count() > 0;
                            });
                        @endphp

                        @if($availableRoutes->count() > 0)
                            <!-- Filtros para Rutas Disponibles -->
                            <div class="mb-4">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input type="text" id="available-route-search"
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-success-500 focus:border-success-500 sm:text-sm"
                                           placeholder="Buscar rutas disponibles por nombre o código..."
                                           onkeyup="filterAvailableRoutes()">
                                </div>
                            </div>

                            <div id="available-routes-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                @foreach($availableRoutes as $route)
                                    @php
                                        $activeBusesCount = $route->buses()->where('status', 'active')->count();
                                    @endphp
                                    <div class="available-route-card border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow bg-white dark:bg-gray-800"
                                         data-name="{{ strtolower($route->name) }}"
                                         data-code="{{ strtolower($route->code ?? '') }}">
                                        <div class="flex items-start mb-3">
                                            <div class="w-4 h-4 rounded-full mt-1 mr-3 flex-shrink-0" style="background-color: {{ $route->color ?? '#3388ff' }}"></div>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-700 dark:text-gray-100">{{ $route->name }}</h4>
                                                @if($route->code)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $route->code }}</p>
                                                @endif
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $route->description }}</p>

                                                <div class="flex items-center justify-between text-sm mb-2">
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        📍 {{ $route->stops->count() }} paradas
                                                    </span>
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        ⏱️ ~{{ $route->estimated_duration }} min
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                                        🚌 {{ $activeBusesCount }} autobús{{ $activeBusesCount != 1 ? 'es' : '' }} activo{{ $activeBusesCount != 1 ? 's' : '' }}
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                                        Disponible
                                                    </span>
                                                </div>

                                                <a href="{{ route('passenger.dashboard', ['route_id' => $route->id]) }}"
                                                   class="w-full inline-flex justify-center items-center px-3 py-2 bg-success-500 hover:bg-success-600 text-white text-sm font-medium rounded-md transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                                    </svg>
                                                    Ver en Mapa
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div id="no-available-routes-message" class="hidden text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">No se encontraron rutas disponibles con los filtros seleccionados.</p>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">No hay rutas con autobuses activos en este momento.</p>
                            </div>
                        @endif
                    </div>
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
        // Filtrar rutas disponibles (con autobuses activos)
        function filterAvailableRoutes() {
            const searchTerm = document.getElementById('available-route-search').value.toLowerCase();
            const routeCards = document.querySelectorAll('.available-route-card');
            let visibleCount = 0;

            routeCards.forEach(card => {
                const name = card.dataset.name;
                const code = card.dataset.code;

                const matchesSearch = name.includes(searchTerm) || code.includes(searchTerm);
                card.style.display = matchesSearch ? 'block' : 'none';
                if (matchesSearch) visibleCount++;
            });

            // Mostrar/ocultar mensaje de "no encontrado"
            const noResultsMsg = document.getElementById('no-available-routes-message');
            const routesGrid = document.getElementById('available-routes-grid');
            if (visibleCount === 0 && routeCards.length > 0) {
                routesGrid.style.display = 'none';
                noResultsMsg.classList.remove('hidden');
            } else {
                routesGrid.style.display = 'grid';
                noResultsMsg.classList.add('hidden');
            }
        }

    </script>
</x-app-layout>
