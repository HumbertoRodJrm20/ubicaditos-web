<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Todos los Autobuses') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Autobuses Activos en Servicio -->
            <div class="mb-6 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 overflow-hidden shadow-sm sm:rounded-lg border-2 border-green-200 dark:border-green-800">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100">🚌 Autobuses en Servicio Ahora</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Autobuses activos y disponibles</p>
                        </div>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                            {{ $buses->where('status', 'active')->count() }} activos
                        </span>
                    </div>

                    @php
                        $activeBuses = $buses->where('status', 'active');
                        $routes = \App\Models\Route::where('is_active', true)->get();
                    @endphp

                    @if($activeBuses->count() > 0)
                        <!-- Filtros para Autobuses Activos -->
                        <div class="mb-4 space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="active-bus-search"
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                       placeholder="Buscar por placa, modelo o conductor..."
                                       onkeyup="filterActiveBuses()">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Filtrar por Ruta:</label>
                                <select id="route-filter-active" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                        onchange="filterActiveBuses()">
                                    <option value="">Todas las rutas</option>
                                    @foreach($routes as $route)
                                        <option value="{{ strtolower($route->name) }}">{{ $route->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="active-buses-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            @foreach($activeBuses as $bus)
                                <div class="active-bus-card border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow bg-white dark:bg-gray-800"
                                     data-plate="{{ strtolower($bus->license_plate) }}"
                                     data-model="{{ strtolower($bus->model) }}"
                                     data-driver="{{ strtolower($bus->driver->name ?? '') }}"
                                     data-route="{{ strtolower($bus->routes->first()->name ?? '') }}">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <div class="font-semibold text-gray-700 dark:text-gray-100">{{ $bus->license_plate }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $bus->model }}</div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                            Activo
                                        </span>
                                    </div>

                                    @if($bus->routes->first())
                                        <div class="mb-2 flex items-center">
                                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $bus->routes->first()->color ?? '#3388ff' }}"></div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-100">{{ $bus->routes->first()->name }}</span>
                                        </div>
                                    @endif

                                    <div class="space-y-1 text-sm mb-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Conductor:</span>
                                            <span class="text-gray-700 dark:text-gray-100">{{ $bus->driver->name ?? 'Sin asignar' }}</span>
                                        </div>
                                    </div>

                                    <a href="{{ route('passenger.dashboard', ['bus_id' => $bus->id]) }}"
                                       class="w-full inline-flex justify-center items-center px-3 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-md transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Ver en Mapa
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div id="no-active-buses-message" class="hidden text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No se encontraron autobuses activos con los filtros seleccionados.</p>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No hay autobuses activos en este momento.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Calificación -->
    <div id="rating-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Calificar Conductor</h3>
                <form id="rating-form" method="POST" action="">
                    @csrf
                    <input type="hidden" id="driver-id" name="driver_id" value="">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Conductor: <span id="driver-name" class="font-semibold"></span>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Calificación</label>
                        <div class="flex space-x-2">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" onclick="setRating({{ $i }})"
                                        class="star-btn text-3xl text-gray-300 hover:text-yellow-500 focus:outline-none"
                                        data-rating="{{ $i }}">
                                    ★
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="rating-value" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Comentario (opcional)</label>
                        <textarea name="comment" rows="3" maxlength="500"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm"
                                  placeholder="Comparte tu experiencia..."></textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Enviar Calificación
                        </button>
                        <button type="button" onclick="closeRatingModal()"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Filtrar autobuses activos
        function filterActiveBuses() {
            const searchTerm = document.getElementById('active-bus-search').value.toLowerCase();
            const routeFilter = document.getElementById('route-filter-active').value;
            const busCards = document.querySelectorAll('.active-bus-card');
            let visibleCount = 0;

            busCards.forEach(card => {
                const plate = card.dataset.plate;
                const model = card.dataset.model;
                const driver = card.dataset.driver;
                const route = card.dataset.route;

                let showCard = true;

                // Filtro de ruta
                if (routeFilter && route !== routeFilter) {
                    showCard = false;
                }

                // Búsqueda por texto
                if (searchTerm && showCard) {
                    const matchesSearch = plate.includes(searchTerm) ||
                                        model.includes(searchTerm) ||
                                        driver.includes(searchTerm) ||
                                        route.includes(searchTerm);
                    if (!matchesSearch) {
                        showCard = false;
                    }
                }

                card.style.display = showCard ? 'block' : 'none';
                if (showCard) visibleCount++;
            });

            // Mostrar/ocultar mensaje de "no encontrado"
            const noResultsMsg = document.getElementById('no-active-buses-message');
            const busesGrid = document.getElementById('active-buses-grid');
            if (visibleCount === 0 && busCards.length > 0) {
                busesGrid.style.display = 'none';
                noResultsMsg.classList.remove('hidden');
            } else {
                busesGrid.style.display = 'grid';
                noResultsMsg.classList.add('hidden');
            }
        }


        // Modal de calificación
        function showRatingModal(driverId, driverName) {
            document.getElementById('driver-id').value = driverId;
            document.getElementById('driver-name').textContent = driverName;
            document.getElementById('rating-form').action = `/passenger/rate/${driverId}`;
            document.getElementById('rating-modal').classList.remove('hidden');

            // Resetear estrellas
            document.querySelectorAll('.star-btn').forEach(btn => {
                btn.classList.remove('text-yellow-500');
                btn.classList.add('text-gray-300');
            });
            document.getElementById('rating-value').value = '';
        }

        function closeRatingModal() {
            document.getElementById('rating-modal').classList.add('hidden');
        }

        function setRating(rating) {
            document.getElementById('rating-value').value = rating;

            // Actualizar estrellas visuales
            document.querySelectorAll('.star-btn').forEach((btn, index) => {
                if (index < rating) {
                    btn.classList.add('text-yellow-500');
                    btn.classList.remove('text-gray-300');
                } else {
                    btn.classList.remove('text-yellow-500');
                    btn.classList.add('text-gray-300');
                }
            });
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('rating-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRatingModal();
            }
        });
    </script>
</x-app-layout>
