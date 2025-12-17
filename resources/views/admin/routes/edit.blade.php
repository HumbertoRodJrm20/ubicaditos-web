<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Editar Ruta') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.routes.update', $route) }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $route->name) }}" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Código <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="code" id="code" value="{{ old('code', $route->code) }}" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
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
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">{{ old('description', $route->description) }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Color -->
                            <div>
                                <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Color
                                </label>
                                <input type="color" name="color" id="color" value="{{ old('color', $route->color ?? '#8b5cf6') }}"
                                    class="mt-1 block h-10 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @error('color')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Estimated Duration -->
                            <div>
                                <label for="estimated_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Duración Estimada (minutos)
                                </label>
                                <input type="number" name="estimated_duration" id="estimated_duration" value="{{ old('estimated_duration', $route->estimated_duration) }}" min="1"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @error('estimated_duration')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Is Active -->
                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $route->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Ruta Activa
                                </label>
                            </div>

                            <!-- Stops -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Paradas (Selecciona en orden)
                                </label>
                                <div id="stops-container" class="space-y-2 max-h-64 overflow-y-auto p-3 border border-gray-300 dark:border-gray-700 rounded-md">
                                    @foreach($stops as $stop)
                                        @php
                                            $isSelected = in_array($stop->id, old('stops', $route->stops->pluck('id')->toArray()));
                                        @endphp
                                        <div class="flex items-center">
                                            <input type="checkbox" name="stops[]" value="{{ $stop->id }}" id="stop_{{ $stop->id }}"
                                                {{ $isSelected ? 'checked' : '' }}
                                                class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            <label for="stop_{{ $stop->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $stop->name }} - {{ $stop->address }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-gray-500">El orden de las paradas será determinado por el orden de selección</p>
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
                                    Actualizar Ruta
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
