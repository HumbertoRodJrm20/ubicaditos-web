<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Reportes de Emergencia') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Todas las Emergencias</h3>

                    @if($emergencies->count() > 0)
                        <div class="space-y-4">
                            @foreach($emergencies as $emergency)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $emergency->status === 'pending' ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $emergency->status === 'pending' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                                    {{ $emergency->status === 'pending' ? 'Pendiente' : 'Resuelta' }}
                                                </span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ ucfirst($emergency->type) }}
                                                </span>
                                            </div>

                                            <div class="mb-2">
                                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $emergency->description }}</p>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <div>
                                                    <span class="font-medium">Reportado por:</span> {{ $emergency->reporter->name }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Autobús:</span> {{ $emergency->bus->license_plate ?? 'N/A' }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Fecha:</span> {{ $emergency->created_at->format('d/m/Y H:i') }}
                                                </div>
                                                @if($emergency->latitude && $emergency->longitude)
                                                    <div>
                                                        <span class="font-medium">Ubicación:</span>
                                                        <a href="https://www.google.com/maps?q={{ $emergency->latitude }},{{ $emergency->longitude }}"
                                                           target="_blank"
                                                           class="text-blue-600 dark:text-blue-400 hover:underline">
                                                            Ver en mapa
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>

                                            @if($emergency->resolved_at)
                                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="font-medium">Resuelta:</span> {{ $emergency->resolved_at->format('d/m/Y H:i') }}
                                                </div>
                                            @endif
                                        </div>

                                        @if($emergency->status === 'pending')
                                            <form method="POST" action="{{ route('admin.emergencies.resolve', $emergency) }}" class="ml-4">
                                                @csrf
                                                <button type="submit"
                                                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                    Marcar como Resuelta
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $emergencies->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No hay reportes de emergencia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
