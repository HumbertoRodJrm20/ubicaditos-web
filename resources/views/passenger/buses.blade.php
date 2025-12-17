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

            <!-- Filtros y Búsqueda -->
            <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Búsqueda -->
                        <div class="flex-1 min-w-[250px]">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="bus-search"
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Buscar por placa, modelo, ruta o conductor..."
                                       onkeyup="filterBuses()">
                            </div>
                        </div>

                        <!-- Filtro de Estado -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Estado:</label>
                            <select id="status-filter" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm"
                                    onchange="filterBuses()">
                                <option value="">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Autobuses -->
            @if($buses->count() > 0)
                <div id="buses-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($buses as $bus)
                        <div class="bus-card bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow"
                             data-status="{{ $bus->status }}"
                             data-availability="{{ $bus->available_seats > 0 ? 'available' : 'full' }}"
                             data-plate="{{ strtolower($bus->license_plate) }}"
                             data-model="{{ strtolower($bus->model) }}"
                             data-route="{{ strtolower($bus->routes->first()->name ?? '') }}"
                             data-driver="{{ strtolower($bus->driver->name ?? '') }}">
                            <div class="p-6">
                                <!-- Encabezado del Autobús -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $bus->license_plate }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $bus->model }}</p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        {{ $bus->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                           ($bus->status === 'emergency' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                           'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                        {{ $bus->status === 'active' ? 'Activo' :
                                           ($bus->status === 'emergency' ? 'Emergencia' : 'Inactivo') }}
                                    </span>
                                </div>

                                <!-- Ruta Asignada -->
                                @if($bus->routes && $bus->routes->count() > 0)
                                    @php $route = $bus->routes->first(); @endphp
                                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-4 h-4 rounded-full mr-2 flex-shrink-0" style="background-color: {{ $route->color ?? '#3388ff' }}"></div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $route->name }}</p>
                                                @if($route->code)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $route->code }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">Sin ruta asignada</p>
                                    </div>
                                @endif

                                <!-- Información del Conductor -->
                                <div class="mb-4 space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Conductor:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $bus->driver->name ?? 'Sin asignar' }}
                                        </span>
                                    </div>
                                    @if($bus->driver && $bus->driver->average_rating)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Calificación:</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                ⭐ {{ number_format($bus->driver->average_rating, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>


                                <!-- Última Actualización -->
                                @if($bus->last_location_update)
                                    <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Actualizado {{ $bus->last_location_update->diffForHumans() }}
                                        </span>
                                    </div>
                                @endif

                                <!-- Acciones -->
                                <div class="flex gap-2">
                                    @if($bus->status === 'active' && $bus->current_latitude && $bus->current_longitude)
                                        <a href="{{ route('passenger.dashboard', ['route_id' => $bus->routes->first()?->id, 'bus_id' => $bus->id]) }}"
                                           class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Ver en Mapa
                                        </a>
                                    @endif

                                    @if($bus->driver && $bus->status === 'active')
                                        <button onclick="showRatingModal({{ $bus->driver->id }}, '{{ $bus->driver->name }}')"
                                                class="px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                            ⭐ Calificar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No hay autobuses registrados</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Los autobuses aparecerán aquí una vez que el administrador los agregue.</p>
                    </div>
                </div>
            @endif
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
        // Filtrar autobuses
        function filterBuses() {
            const searchTerm = document.getElementById('bus-search').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const busCards = document.querySelectorAll('.bus-card');

            busCards.forEach(card => {
                const status = card.dataset.status;
                const plate = card.dataset.plate;
                const model = card.dataset.model;
                const route = card.dataset.route;
                const driver = card.dataset.driver;

                let showCard = true;

                // Filtro de estado
                if (statusFilter && status !== statusFilter) {
                    showCard = false;
                }

                // Búsqueda por texto
                if (searchTerm && showCard) {
                    const matchesSearch = plate.includes(searchTerm) ||
                                        model.includes(searchTerm) ||
                                        route.includes(searchTerm) ||
                                        driver.includes(searchTerm);
                    if (!matchesSearch) {
                        showCard = false;
                    }
                }

                card.style.display = showCard ? 'block' : 'none';
            });
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
