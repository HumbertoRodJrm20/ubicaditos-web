<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Crear Ruta') }}
        </h2>
    </x-slot>

    @php
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';
        $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';
        $input = 'block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
        $label = 'block mb-1 text-[11px] font-medium text-slate-500 dark:text-gray-400';
    @endphp

    <style>
        .routes-form-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
    </style>

    <div class="routes-form-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
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

            {{-- Encabezado --}}
            <div class="mb-7 text-center">
                <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                <h1 class="text-3xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-4xl">
                    Crear Ruta
                </h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Registra una nueva ruta y selecciona sus paradas en orden.</p>
            </div>

            <section class="{{ $panel }} mx-auto max-w-3xl p-4 sm:p-6">
                <h2 class="mb-5 text-base font-semibold {{ $heading }}">Datos de la ruta</h2>

                <form method="POST" action="{{ route('admin.routes.store') }}" x-data="routeForm()">
                    @csrf

                    <div class="space-y-5">
                        <!-- Name -->
                        <div>
                            <label for="name" class="{{ $label }}">
                                Nombre <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                x-model="name"
                                @blur="touchedFields.name = true; validateName()"
                                @input="validateName"
                                class="{{ $input }}"
                                :class="touchedFields.name && errors.name ? 'border-rose-500 dark:border-rose-500' : ''">
                            <p x-show="touchedFields.name && errors.name" x-text="errors.name" class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"></p>
                            @error('name')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Code -->
                        <div>
                            <label for="code" class="{{ $label }}">
                                Código <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" required
                                x-model="code"
                                @blur="touchedFields.code = true; validateCode()"
                                @input="validateCode"
                                class="{{ $input }}"
                                :class="touchedFields.code && errors.code ? 'border-rose-500 dark:border-rose-500' : ''">
                            <p x-show="touchedFields.code && errors.code" x-text="errors.code" class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"></p>
                            @error('code')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="{{ $label }}">
                                Descripción
                            </label>
                            <textarea name="description" id="description" rows="3"
                                class="block w-full rounded-2xl border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition resize-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Color -->
                        <div>
                            <label for="color" class="{{ $label }}">
                                Color
                            </label>
                            <input type="color" name="color" id="color" value="{{ old('color', '#8b5cf6') }}"
                                class="block h-11 w-20 cursor-pointer rounded-2xl border border-slate-200/90 bg-white/90 p-1.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#8295c5]/30 dark:border-gray-600 dark:bg-gray-700">
                            @error('color')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Estimated Duration -->
                        <div>
                            <label for="estimated_duration" class="{{ $label }}">
                                Duración Estimada (minutos) <span class="text-slate-400 dark:text-gray-500">(Máximo 2:30 horas / 150 min)</span>
                            </label>
                            <input type="number" name="estimated_duration" id="estimated_duration" value="{{ old('estimated_duration') }}"
                                min="1" max="150" maxlength="3"
                                x-model="duration"
                                @input="validateDuration"
                                @blur="touchedFields.duration = true; validateDuration()"
                                class="{{ $input }}"
                                :class="touchedFields.duration && errors.duration ? 'border-rose-500 dark:border-rose-500' : ''">
                            <p x-show="touchedFields.duration && errors.duration" x-text="errors.duration" class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"></p>
                            @error('estimated_duration')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Is Active -->
                        <div class="flex min-h-9 items-center gap-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="h-5 w-5 rounded border-slate-300 text-[#53658f] shadow-sm accent-[#53658f] focus:ring-[#8295c5] dark:border-gray-600 dark:bg-gray-700">
                            <label for="is_active" class="block text-sm text-slate-600 dark:text-gray-300">
                                Ruta Activa
                            </label>
                        </div>

                        <!-- Stops -->
                        <div>
                            <label class="{{ $label }} mb-2">
                                Paradas (Selecciona en orden) <span class="text-rose-500">*</span>
                            </label>
                            <div id="stops-container" class="max-h-64 space-y-1 overflow-y-auto rounded-2xl border border-slate-200/90 bg-white/70 p-2 dark:border-gray-600 dark:bg-gray-700/40"
                                 :class="touchedFields.stops && errors.stops ? 'border-rose-500 dark:border-rose-500' : ''">
                                @foreach($stops as $stop)
                                    <div class="flex min-h-9 items-center gap-2 rounded-xl px-2 py-1 transition hover:bg-slate-100/70 dark:hover:bg-gray-700/40">
                                        <input type="checkbox" name="stops[]" value="{{ $stop->id }}" id="stop_{{ $stop->id }}"
                                            {{ in_array($stop->id, old('stops', [])) ? 'checked' : '' }}
                                            @change="validateStops"
                                            class="h-4 w-4 shrink-0 rounded border-slate-300 text-[#53658f] accent-[#53658f] focus:ring-[#8295c5] dark:border-gray-600 dark:bg-gray-700">
                                        <label for="stop_{{ $stop->id }}" class="min-w-0 cursor-pointer text-sm text-slate-600 dark:text-gray-300">
                                            {{ $stop->name }} - {{ $stop->address }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-1 text-xs {{ $muted }}">El orden de las paradas será determinado por el orden de selección</p>
                            <p x-show="touchedFields.stops && errors.stops" x-text="errors.stops" class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"></p>
                            @error('stops')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                            <a href="{{ route('admin.routes.index') }}" class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 bg-white/85 px-4 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                Cancelar
                            </a>
                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                                Crear Ruta
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">Ubicaditos · Crear Ruta</p>
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
