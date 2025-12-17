<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Crear Ruta') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.routes.store') }}" x-data="routeForm()">
                        @csrf

                        <div class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                    x-model="name"
                                    @blur="touchedFields.name = true; validateName()"
                                    @input="validateName"
                                    class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                    :class="touchedFields.name && errors.name ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.name && errors.name" x-text="errors.name" class="mt-1 text-sm text-red-600"></p>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Código <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}" required
                                    x-model="code"
                                    @blur="touchedFields.code = true; validateCode()"
                                    @input="validateCode"
                                    class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                    :class="touchedFields.code && errors.code ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.code && errors.code" x-text="errors.code" class="mt-1 text-sm text-red-600"></p>
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Descripción
                                </label>
                                <textarea name="description" id="description" rows="3"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Color -->
                            <div>
                                <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Color
                                </label>
                                <input type="color" name="color" id="color" value="{{ old('color', '#8b5cf6') }}"
                                    class="mt-1 block h-10 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @error('color')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Estimated Duration -->
                            <div>
                                <label for="estimated_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Duración Estimada (minutos) <span class="text-gray-500 text-xs">(Máximo 2:30 horas / 150 min)</span>
                                </label>
                                <input type="number" name="estimated_duration" id="estimated_duration" value="{{ old('estimated_duration') }}"
                                    min="1" max="150" maxlength="3"
                                    x-model="duration"
                                    @input="validateDuration"
                                    @blur="touchedFields.duration = true; validateDuration()"
                                    class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                    :class="touchedFields.duration && errors.duration ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.duration && errors.duration" x-text="errors.duration" class="mt-1 text-sm text-red-600"></p>
                                @error('estimated_duration')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Is Active -->
                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Ruta Activa
                                </label>
                            </div>

                            <!-- Stops -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Paradas (Selecciona en orden) <span class="text-red-500">*</span>
                                </label>
                                <div id="stops-container" class="space-y-2 max-h-64 overflow-y-auto p-3 border rounded-md"
                                     :class="touchedFields.stops && errors.stops ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                    @foreach($stops as $stop)
                                        <div class="flex items-center">
                                            <input type="checkbox" name="stops[]" value="{{ $stop->id }}" id="stop_{{ $stop->id }}"
                                                {{ in_array($stop->id, old('stops', [])) ? 'checked' : '' }}
                                                @change="validateStops"
                                                class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            <label for="stop_{{ $stop->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $stop->name }} - {{ $stop->address }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-gray-500">El orden de las paradas será determinado por el orden de selección</p>
                                <p x-show="touchedFields.stops && errors.stops" x-text="errors.stops" class="mt-1 text-sm text-red-600"></p>
                                @error('stops')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-4">
                                <a href="{{ route('admin.routes.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-md hover:bg-primary-600">
                                    Crear Ruta
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function routeForm() {
            return {
                name: '{{ old('name') }}',
                code: '{{ old('code') }}',
                duration: '{{ old('estimated_duration') }}',
                errors: {},
                touchedFields: {},

                validateName() {
                    if (!this.name || this.name.trim() === '') {
                        this.errors.name = 'El nombre de la ruta es obligatorio';
                    } else if (this.name.trim().length < 3) {
                        this.errors.name = 'El nombre debe tener al menos 3 caracteres';
                    } else {
                        delete this.errors.name;
                    }
                },

                validateCode() {
                    if (!this.code || this.code.trim() === '') {
                        this.errors.code = 'El código de la ruta es obligatorio';
                    } else if (this.code.trim().length < 2) {
                        this.errors.code = 'El código debe tener al menos 2 caracteres';
                    } else {
                        delete this.errors.code;
                    }
                },

                validateDuration() {
                    const dur = parseInt(this.duration);

                    // Limitar a 3 dígitos
                    if (this.duration && this.duration.toString().length > 3) {
                        this.duration = this.duration.toString().slice(0, 3);
                    }

                    if (this.duration && this.duration !== '') {
                        if (isNaN(dur)) {
                            this.errors.duration = 'La duración debe ser un número';
                        } else if (dur < 1) {
                            this.errors.duration = 'La duración mínima es de 1 minuto';
                        } else if (dur > 150) {
                            this.errors.duration = 'La duración máxima es de 150 minutos (2:30 horas)';
                        } else {
                            delete this.errors.duration;
                        }
                    } else {
                        delete this.errors.duration;
                    }
                },

                validateStops() {
                    this.touchedFields.stops = true;
                    const checkedStops = document.querySelectorAll('input[name="stops[]"]:checked');
                    if (checkedStops.length === 0) {
                        this.errors.stops = 'Debes seleccionar al menos una parada';
                    } else if (checkedStops.length < 2) {
                        this.errors.stops = 'Debes seleccionar al menos 2 paradas para crear una ruta';
                    } else {
                        delete this.errors.stops;
                    }
                }
            }
        }
    </script>
</x-app-layout>
