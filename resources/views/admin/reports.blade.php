<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
                {{ __('Reportes y Estadísticas') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.export-pdf') }}" class="px-4 py-2 bg-danger-600 text-white text-sm font-medium rounded-md hover:bg-danger-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Exportar PDF
                </a>
                <a href="{{ route('admin.reports.export-excel') }}" class="px-4 py-2 bg-success-600 text-white text-sm font-medium rounded-md hover:bg-success-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Exportar Excel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Estadísticas Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Viajes Totales</div>
                    <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($stats['total_trips']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stats['total_trips_month'] }} este mes</div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Autobuses</div>
                    <div class="text-3xl font-bold text-accent-600 dark:text-accent-400">{{ $stats['total_buses'] }}</div>
                    <div class="text-xs text-success-600 dark:text-success-400 mt-1">{{ $stats['active_buses'] }} activos</div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuarios</div>
                    <div class="text-3xl font-bold text-success-600 dark:text-success-400">{{ number_format($stats['total_drivers'] + $stats['total_passengers']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stats['total_drivers'] }} conductores, {{ $stats['total_passengers'] }} pasajeros</div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Calificación Promedio</div>
                    <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($stats['average_rating'], 1) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">de 5.0 estrellas</div>
                </div>
            </div>

            <!-- Gráfica de Viajes por Día -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Viajes Realizados (Últimos 30 Días)</h3>
                <canvas id="tripsChart" height="80"></canvas>
            </div>

            <!-- Top 5 Rutas -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Rutas Más Utilizadas</h3>
                <canvas id="routesChart"></canvas>
            </div>

            <!-- Top 10 Autobuses Más Usados -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Autobuses Más Utilizados (Últimos 30 Días)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Placa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Viajes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Progreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($busUsage as $bus)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-100">{{ $bus->license_plate }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $bus->model }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">{{ $bus->trips_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        @php
                                            $maxTrips = $busUsage->max('trips_count');
                                            $percentage = $maxTrips > 0 ? min(($bus->trips_count / $maxTrips) * 100, 100) : 0;
                                        @endphp
                                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top 10 Conductores -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Mejores Conductores</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Viajes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Calificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($topDrivers as $driver)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-100">{{ $driver->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">{{ $driver->trips_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($driver->received_ratings_avg_rating)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">
                                            ⭐ {{ number_format($driver->received_ratings_avg_rating, 1) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Sin calificación</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Gráfica de Viajes por Día
        const tripsCtx = document.getElementById('tripsChart').getContext('2d');
        new Chart(tripsCtx, {
            type: 'line',
            data: {
                labels: @json($dates),
                datasets: [{
                    label: 'Viajes Realizados',
                    data: @json($counts),
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Gráfica de Rutas
        const routesCtx = document.getElementById('routesChart').getContext('2d');
        new Chart(routesCtx, {
            type: 'bar',
            data: {
                labels: @json($topRoutes->pluck('name')),
                datasets: [{
                    label: 'Viajes',
                    data: @json($topRoutes->pluck('trips_count')),
                    backgroundColor: [
                        '#10B981',
                        '#06B6D4',
                        '#A855F7',
                        '#F59E0B',
                        '#EF4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</x-app-layout>
