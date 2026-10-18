<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Crear Autobús') }}
        </h2>
    </x-slot>

    @php
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';
        $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';
    @endphp

    <style>
        .buses-form-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        .buses-form-page input[type="checkbox"] { accent-color: #53658f; }
        .buses-form-routes { scrollbar-width: thin; scrollbar-color: #c5ccdc transparent; }
    </style>

    <div class="buses-form-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
        <div class="mx-auto max-w-[1400px]">
            @if(session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-sm text-rose-800 shadow-sm dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mx-auto max-w-3xl">
                <div class="mb-7 text-center">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                    <h1 class="text-3xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-4xl">
                        Crear Autobús
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Registra una nueva unidad de la flota con su conductor y rutas.</p>
                </div>

                <section class="{{ $panel }} p-4 sm:p-6">
                    <form method="POST" action="{{ route('admin.buses.store') }}" x-data="busForm()">
                        @csrf

                        <div class="space-y-6">
                            <!-- License Plate -->
                            <div>
                                <label for="license_plate" class="block mb-1 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                    Placa <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="license_plate" id="license_plate" value="{{ old('license_plate') }}" required
                                    x-model="license_plate"
                                    @input="validateLicensePlate"
                                    @blur="touchedFields.license_plate = true; validateLicensePlate()"
                                    placeholder="ABC123 (3 letras y 3 números)"
                                    maxlength="6"
                                    style="text-transform: uppercase"
                                    class="block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder:text-gray-400"
                                    :class="touchedFields.license_plate && errors.license_plate ? 'border-rose-500 dark:border-rose-500' : ''">
                                <p x-show="touchedFields.license_plate && errors.license_plate" x-text="errors.license_plate" class="mt-1 text-xs text-rose-600 dark:text-rose-400"></p>
                                @error('license_plate')
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Model -->
                            <div>
                                <label for="model" class="block mb-1 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                    Modelo <span class="text-rose-500">*</span>
                                </label>
                                <select name="model" id="model" required
                                    x-model="model"
                                    @change="validateModel"
                                    @blur="touchedFields.model = true; validateModel()"
                                    class="block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="touchedFields.model && errors.model ? 'border-rose-500 dark:border-rose-500' : ''">
                                    <option value="">Seleccionar modelo</option>
                                    <option value="Foton AUV" {{ old('model') === 'Foton AUV' ? 'selected' : '' }}>Foton AUV</option>
                                    <option value="Yutong" {{ old('model') === 'Yutong' ? 'selected' : '' }}>Yutong</option>
                                    <option value="Mercedes-Benz" {{ old('model') === 'Mercedes-Benz' ? 'selected' : '' }}>Mercedes-Benz</option>
                                    <option value="Dina" {{ old('model') === 'Dina' ? 'selected' : '' }}>Dina</option>
                                    <option value="Toyota" {{ old('model') === 'Toyota' ? 'selected' : '' }}>Toyota</option>
                                    <option value="Honda" {{ old('model') === 'Honda' ? 'selected' : '' }}>Honda</option>
                                </select>
                                <p x-show="touchedFields.model && errors.model" x-text="errors.model" class="mt-1 text-xs text-rose-600 dark:text-rose-400"></p>
                                @error('model')
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Capacity -->
                            <div>
                                <label for="capacity" class="block mb-1 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                    Capacidad <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="capacity" id="capacity" value="{{ old('capacity') }}" min="30" max="50" maxlength="2" required
                                    x-model="capacity"
                                    @input="validateCapacity"
                                    @blur="touchedFields.capacity = true; validateCapacity()"
                                    placeholder="Entre 30 y 50 pasajeros"
                                    class="block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder:text-gray-400"
                                    :class="touchedFields.capacity && errors.capacity ? 'border-rose-500 dark:border-rose-500' : ''">
                                <p x-show="touchedFields.capacity && errors.capacity" x-text="errors.capacity" class="mt-1 text-xs text-rose-600 dark:text-rose-400"></p>
                                @error('capacity')
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Driver -->
                            <div>
                                <label for="driver_id" class="block mb-1 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                    Conductor
                                </label>
                                <select name="driver_id" id="driver_id"
                                    class="block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">Sin conductor</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->name }} ({{ $driver->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('driver_id')
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block mb-1 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                    Estado <span class="text-rose-500">*</span>
                                </label>
                                <select name="status" id="status" required
                                    class="block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Activo</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Routes -->
                            <div>
                                <label class="block mb-2 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                    Rutas Asignadas <span class="text-rose-500">*</span>
                                </label>
                                <div class="buses-form-routes max-h-64 space-y-1 overflow-y-auto rounded-2xl border border-slate-200/90 bg-white/60 p-3 dark:border-gray-600 dark:bg-gray-700/40"
                                     :class="touchedFields.routes && errors.routes ? 'border-rose-500 dark:border-rose-500' : ''">
                                    @foreach($routes as $route)
                                        <div class="flex items-center gap-2 rounded-xl px-2 py-1.5 transition hover:bg-slate-50 dark:hover:bg-gray-700/40">
                                            <input type="checkbox" name="routes[]" value="{{ $route->id }}" id="route_{{ $route->id }}"
                                                {{ in_array($route->id, old('routes', [])) ? 'checked' : '' }}
                                                @change="validateRoutes"
                                                class="h-4 w-4 rounded border-slate-300 focus:ring-[#8295c5] dark:border-gray-600 dark:bg-gray-700">
                                            <label for="route_{{ $route->id }}" class="min-w-0 flex-1 cursor-pointer text-sm text-slate-700 dark:text-gray-300">
                                                {{ $route->name }} ({{ $route->code }})
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p x-show="touchedFields.routes && errors.routes" x-text="errors.routes" class="mt-1 text-xs text-rose-600 dark:text-rose-400"></p>
                                @error('routes')
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 dark:border-gray-700 sm:flex-row sm:justify-end">
                                <a href="{{ route('admin.buses.index') }}" class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white/85 px-5 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    Cancelar
                                </a>
                                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                                    Crear Autobús
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">Ubicaditos · Gestión de Autobuses</p>
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
