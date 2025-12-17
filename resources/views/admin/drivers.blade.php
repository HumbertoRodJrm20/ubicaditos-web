<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Conductores') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100">Lista de Conductores</h3>
                    </div>

                    <!-- Barra de búsqueda y filtros -->
                    <div class="mb-4 flex flex-col sm:flex-row gap-3">
                        <form method="GET" action="{{ route('admin.drivers') }}" class="flex-1 flex gap-2">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Buscar por nombre o email..."
                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-primary-500 focus:border-primary-500">
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                Buscar
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.drivers') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                    Limpiar
                                </a>
                            @endif
                        </form>
                        <form method="GET" action="{{ route('admin.drivers') }}" class="flex items-center gap-2">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <label class="text-sm text-gray-600 dark:text-gray-400">Mostrar:</label>
                            <select name="per_page"
                                    onchange="this.form.submit()"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                            </select>
                        </form>
                    </div>

                    @if($drivers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Horario</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Autobús</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Viajes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Calificación</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($drivers as $driver)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                {{ $driver->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $driver->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                @if($driver->work_shift === 'morning')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        6:00 AM - 2:00 PM
                                                    </span>
                                                @elseif($driver->work_shift === 'afternoon')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                        2:00 PM - 10:00 PM
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">Sin asignar</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                @if($driver->drivingBus)
                                                    <span class="font-medium">{{ $driver->drivingBus->license_plate }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">({{ $driver->drivingBus->model }})</span>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">Sin autobús</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                {{ $driver->trips_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                @if($driver->ratings_received_avg_rating)
                                                    <span class="font-medium">{{ number_format($driver->ratings_received_avg_rating, 1) }}</span> / 5.0
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">Sin calificación</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex gap-2">
                                                    @if($driver->drivingBus)
                                                        <form method="POST" action="{{ route('admin.drivers.unassign-bus', $driver) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('¿Desasignar autobús?')">
                                                                Desasignar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button" onclick="openAssignModal({{ $driver->id }})" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                                            Asignar Bus
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('admin.users.edit', $driver) }}" class="text-accent-600 hover:text-accent-900 dark:text-accent-400 dark:hover:text-accent-300 ml-2">
                                                        Editar
                                                    </a>
                                                    <form method="POST" action="{{ route('admin.users.destroy', $driver) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 ml-2" onclick="return confirm('¿Estás seguro de eliminar este conductor?')">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $drivers->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No hay conductores registrados.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Asignar Autobús -->
    <div id="assign-bus-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Asignar Autobús</h3>
                <form method="POST" id="assign-bus-form">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionar Autobús</label>
                        <select name="bus_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            <option value="">-- Seleccione un autobús --</option>
                            @foreach($availableBuses as $bus)
                                <option value="{{ $bus->id }}">{{ $bus->license_plate }} - {{ $bus->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-md hover:bg-primary-600">
                            Asignar
                        </button>
                        <button type="button" onclick="closeAssignModal()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAssignModal(driverId) {
            const modal = document.getElementById('assign-bus-modal');
            const form = document.getElementById('assign-bus-form');
            form.action = `/admin/drivers/${driverId}/assign-bus`;
            modal.classList.remove('hidden');
        }

        function closeAssignModal() {
            document.getElementById('assign-bus-modal').classList.add('hidden');
        }
    </script>
</x-app-layout>
