<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Opciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Accesos Rápidos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('passenger.dashboard') }}" class="block">
                    <div class="bg-primary-50 dark:bg-primary-900/20 border-2 border-primary-200 dark:border-primary-400 rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-primary-700 dark:text-primary-100">Ver Mapa</h3>
                                <p class="text-sm text-primary-600 dark:text-primary-400">Rastrea autobuses en tiempo real</p>
                            </div>
                            <svg class="w-12 h-12 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('passenger.routes') }}" class="block">
                    <div class="bg-success-50 dark:bg-success-900/20 border-2 border-success-200 dark:border-success-800 rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-success-900 dark:text-success-100">Rutas</h3>
                                <p class="text-sm text-success-600 dark:text-success-400">Consulta todas las rutas</p>
                            </div>
                            <svg class="w-12 h-12 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('passenger.buses') }}" class="block">
                    <div class="bg-accent-50 dark:bg-accent-900/20 border-2 border-accent-200 dark:border-accent-800 rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-accent-900 dark:text-accent-100">Autobuses</h3>
                                <p class="text-sm text-accent-600 dark:text-accent-400">Ver todos los autobuses</p>
                            </div>
                            <svg class="w-12 h-12 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Autobuses Activos -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Autobuses en Servicio</h3>
                    @if($activeBuses->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($activeBuses as $bus)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
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

                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Conductor:</span>
                                            <span class="text-gray-700 dark:text-gray-100">{{ $bus->driver->name ?? 'Sin asignar' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No hay autobuses en servicio en este momento.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Rutas Disponibles -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Rutas Disponibles</h3>
                    @if($routes->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($routes as $route)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <div class="w-4 h-4 rounded-full mt-1 mr-3 flex-shrink-0" style="background-color: {{ $route->color ?? '#3388ff' }}"></div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-700 dark:text-gray-100">{{ $route->name }}</h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $route->description }}</p>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    {{ $route->stops->count() }} paradas
                                                </span>
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    ~{{ $route->estimated_duration }} min
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No hay rutas disponibles.</p>
                    @endif
                </div>
            </div>

            <!-- Paradas Favoritas -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Mis Paradas Favoritas</h3>
                    @if($favoriteStops->count() > 0)
                        <div class="space-y-3">
                            @foreach($favoriteStops as $stop)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                        <div>
                                            <p class="font-medium text-gray-700 dark:text-gray-100">
                                                {{ $stop->pivot->nickname ?? $stop->name }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stop->address }}</p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('passenger.favorites.remove', $stop) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No tienes paradas favoritas todavía.</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Agrega tus paradas frecuentes desde el mapa</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
