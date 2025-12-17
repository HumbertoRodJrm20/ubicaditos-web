<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Panel de Conductor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Información del Autobús -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Mi Autobús</h3>
                    @if($bus)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Placa:</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-100">{{ $bus->license_plate }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Modelo:</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-100">{{ $bus->model }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Capacidad:</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-100">{{ $bus->capacity }} pasajeros</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Estado:</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $bus->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $bus->status === 'active' ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ruta asignada:</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-100">
                                        {{ $bus->routes->first()->name ?? 'Sin ruta' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Mi calificación:</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-100">
                                        @if($averageRating)
                                            {{ number_format($averageRating, 1) }} / 5.0
                                        @else
                                            Sin calificaciones
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('driver.map') }}"
                               class="w-full px-6 py-3 bg-primary-500 text-white text-sm font-medium rounded-md hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 text-center inline-block">
                                🗺️ Ver Mapa y Navegación
                            </a>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No tienes un autobús asignado. Contacta al administrador.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Viaje Actual -->
            @if($currentTrip)
                <div class="bg-primary-50 dark:bg-primary-900/10 overflow-hidden shadow-sm sm:rounded-lg border-2 border-primary-200 dark:border-primary-400">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-primary-700 dark:text-primary-100 mb-4">Viaje en Progreso</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="text-primary-600 dark:text-primary-400">Ruta:</span>
                                <p class="font-medium text-primary-700 dark:text-primary-100">{{ $currentTrip->route->name }}</p>
                            </div>
                            <div>
                                <span class="text-primary-600 dark:text-primary-400">Hora de Inicio:</span>
                                <p class="font-medium text-primary-700 dark:text-primary-100">{{ $currentTrip->started_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                            <div>
                                <span class="text-primary-600 dark:text-primary-400">Duración:</span>
                                <p class="font-medium text-primary-700 dark:text-primary-100">{{ $currentTrip->started_at->diffForHumans(null, true) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Viajes Recientes -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Mis Viajes Recientes</h3>
                    @if($recentTrips->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ruta</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Inicio</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Fin</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentTrips as $trip)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                {{ $trip->route->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $trip->started_at->format('d/m/Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                @if($trip->completed_at)
                                                    {{ $trip->completed_at->format('d/m/Y H:i:s') }}
                                                @else
                                                    <span class="text-warning-600 dark:text-warning-400">En progreso</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $trip->status === 'in_progress' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' }}">
                                                    {{ $trip->status === 'in_progress' ? 'En progreso' : 'Completado' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No has realizado viajes todavía.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
