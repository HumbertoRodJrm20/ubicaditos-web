<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Crear Autobús') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.buses.store') }}" x-data="busForm()">
                        @csrf

                        <div class="space-y-6">
                            <!-- License Plate -->
                            <div>
                                <label for="license_plate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Placa <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="license_plate" id="license_plate" value="{{ old('license_plate') }}" required
                                    x-model="license_plate"
                                    @input="validateLicensePlate"
                                    @blur="touchedFields.license_plate = true; validateLicensePlate()"
                                    placeholder="ABC123 (3 letras y 3 números)"
                                    maxlength="6"
                                    style="text-transform: uppercase"
                                    class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                    :class="touchedFields.license_plate && errors.license_plate ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.license_plate && errors.license_plate" x-text="errors.license_plate" class="mt-1 text-sm text-red-600"></p>
                                @error('license_plate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Model -->
                            <div>
                                <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Modelo <span class="text-red-500">*</span>
                                </label>
                                <select name="model" id="model" required
                                    x-model="model"
                                    @change="validateModel"
                                    @blur="touchedFields.model = true; validateModel()"
                                    class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                    :class="touchedFields.model && errors.model ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                    <option value="">Seleccionar modelo</option>
                                    <option value="Foton AUV" {{ old('model') === 'Foton AUV' ? 'selected' : '' }}>Foton AUV</option>
                                    <option value="Yutong" {{ old('model') === 'Yutong' ? 'selected' : '' }}>Yutong</option>
                                    <option value="Mercedes-Benz" {{ old('model') === 'Mercedes-Benz' ? 'selected' : '' }}>Mercedes-Benz</option>
                                    <option value="Dina" {{ old('model') === 'Dina' ? 'selected' : '' }}>Dina</option>
                                    <option value="Toyota" {{ old('model') === 'Toyota' ? 'selected' : '' }}>Toyota</option>
                                    <option value="Honda" {{ old('model') === 'Honda' ? 'selected' : '' }}>Honda</option>
                                </select>
                                <p x-show="touchedFields.model && errors.model" x-text="errors.model" class="mt-1 text-sm text-red-600"></p>
                                @error('model')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Capacity -->
                            <div>
                                <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Capacidad <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="capacity" id="capacity" value="{{ old('capacity') }}" min="30" max="50" maxlength="2" required
                                    x-model="capacity"
                                    @input="validateCapacity"
                                    @blur="touchedFields.capacity = true; validateCapacity()"
                                    placeholder="Entre 30 y 50 pasajeros"
                                    class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                    :class="touchedFields.capacity && errors.capacity ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.capacity && errors.capacity" x-text="errors.capacity" class="mt-1 text-sm text-red-600"></p>
                                @error('capacity')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Driver -->
                            <div>
                                <label for="driver_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Conductor
                                </label>
                                <select name="driver_id" id="driver_id"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Sin conductor</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->name }} ({{ $driver->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('driver_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Estado <span class="text-red-500">*</span>
                                </label>
                                <select name="status" id="status" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Activo</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Routes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Rutas Asignadas <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2 max-h-48 overflow-y-auto p-3 border rounded-md"
                                     :class="touchedFields.routes && errors.routes ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                    @foreach($routes as $route)
                                        <div class="flex items-center">
                                            <input type="checkbox" name="routes[]" value="{{ $route->id }}" id="route_{{ $route->id }}"
                                                {{ in_array($route->id, old('routes', [])) ? 'checked' : '' }}
                                                @change="validateRoutes"
                                                class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            <label for="route_{{ $route->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $route->name }} ({{ $route->code }})
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p x-show="touchedFields.routes && errors.routes" x-text="errors.routes" class="mt-1 text-sm text-red-600"></p>
                                @error('routes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-4">
                                <a href="{{ route('admin.buses.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-md hover:bg-primary-600">
                                    Crear Autobús
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function busForm() {
            return {
                license_plate: '{{ old('license_plate') }}',
                model: '{{ old('model') }}',
                capacity: '{{ old('capacity') }}',
                errors: {},
                touchedFields: {},

                validateLicensePlate() {
                    // Convertir a mayúsculas automáticamente
                    this.license_plate = this.license_plate.toUpperCase();

                    if (!this.license_plate || this.license_plate.trim() === '') {
                        this.errors.license_plate = 'La placa es obligatoria';
                    } else if (this.license_plate.length !== 6) {
                        this.errors.license_plate = 'La placa debe tener exactamente 6 caracteres (3 letras y 3 números)';
                    } else if (!/^[A-Z]{3}[0-9]{3}$/.test(this.license_plate)) {
                        this.errors.license_plate = 'La placa debe tener el formato: 3 letras seguidas de 3 números (Ej: ABC123)';
                    } else {
                        delete this.errors.license_plate;
                    }
                },

                validateModel() {
                    if (!this.model || this.model === '') {
                        this.errors.model = 'Debe seleccionar un modelo';
                    } else {
                        delete this.errors.model;
                    }
                },

                validateCapacity() {
                    this.touchedFields.capacity = true;

                    // Limitar a 2 dígitos
                    if (this.capacity && this.capacity.toString().length > 2) {
                        this.capacity = this.capacity.toString().slice(0, 2);
                    }

                    const cap = parseInt(this.capacity);
                    if (!this.capacity || this.capacity === '') {
                        this.errors.capacity = 'El número de pasajeros es obligatorio';
                    } else if (isNaN(cap)) {
                        this.errors.capacity = 'La capacidad debe ser un número';
                    } else if (cap < 30) {
                        this.errors.capacity = 'La capacidad mínima es de 30 pasajeros';
                    } else if (cap > 50) {
                        this.errors.capacity = 'La capacidad máxima es de 50 pasajeros';
                    } else {
                        delete this.errors.capacity;
                    }
                },

                validateRoutes() {
                    this.touchedFields.routes = true;
                    const checkedRoutes = document.querySelectorAll('input[name="routes[]"]:checked');
                    if (checkedRoutes.length === 0) {
                        this.errors.routes = 'Debes seleccionar al menos una ruta';
                    } else {
                        delete this.errors.routes;
                    }
                }
            }
        }
    </script>
</x-app-layout>
