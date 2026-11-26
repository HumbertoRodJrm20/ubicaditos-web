@php
    $fieldBase = 'w-full rounded-full border bg-white px-4 text-sm text-[#1e2a4a] shadow-sm outline-none transition placeholder:text-[#a0a8bd] focus:ring-4 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder:text-gray-400';
    $fieldOk = 'border-slate-200 focus:border-[#6b7bad] focus:ring-[#6b7bad]/20';
    $fieldError = 'border-rose-300 focus:border-rose-400 focus:ring-rose-100 dark:border-rose-700';
    $panel = 'rounded-[28px] border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/80 sm:p-6';
@endphp

<x-passenger-layout title="Reportar emergencia · Ubicaditos" active="Reportes">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('passenger.menu') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#5b6788] transition hover:text-[#2f3a5f] focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/20 dark:text-gray-400 dark:hover:text-gray-100">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
            Volver al menú
        </a>

        <header class="mt-3">
            <p class="text-[11px] font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-blue-300">Ayuda inmediata</p>
            <h1 class="mt-1 text-[28px] font-bold leading-tight tracking-tight dark:text-gray-100 sm:text-3xl">Reportar una emergencia</h1>
            <p class="mt-1 max-w-2xl text-xs text-[#5b6788] dark:text-gray-400">
                Tu reporte llegará al panel administrativo. La unidad y la ubicación son opcionales; inclúyelas si puedes.
            </p>
        </header>

        @if ($errors->any())
            <div class="mt-5 rounded-2xl bg-rose-50 px-4 py-3 text-rose-700 ring-1 ring-rose-100 dark:bg-rose-900/30 dark:text-rose-200 dark:ring-rose-800" role="alert">
                <p class="text-xs font-semibold">Revisa la información antes de continuar:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="{{ $panel }} mt-6">
            <form method="POST" action="{{ route('passenger.emergency.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="type" class="block text-xs font-semibold text-[#1e2a4a] dark:text-gray-100">Tipo de emergencia <span class="text-rose-500" aria-hidden="true">*</span></label>
                    <select id="type" name="type" required class="mt-2 h-12 {{ $fieldBase }} @error('type') {{ $fieldError }} @else {{ $fieldOk }} @enderror">
                        <option value="" disabled @selected(old('type') === '')>Selecciona una opción</option>
                        <option value="accident" @selected(old('type') === 'accident')>Accidente</option>
                        <option value="breakdown" @selected(old('type') === 'breakdown')>Avería</option>
                        <option value="medical" @selected(old('type') === 'medical')>Emergencia médica</option>
                        <option value="security" @selected(old('type') === 'security')>Seguridad</option>
                        <option value="other" @selected(old('type') === 'other')>Otra</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold text-[#1e2a4a] dark:text-gray-100">Descripción <span class="text-rose-500" aria-hidden="true">*</span></label>
                    <textarea id="description" name="description" rows="4" required maxlength="500"
                        placeholder="Cuéntanos qué está pasando y dónde ocurre."
                        class="mt-2 resize-y {{ $fieldBase }} py-3 @error('description') {{ $fieldError }} @else {{ $fieldOk }} @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bus_id" class="block text-xs font-semibold text-[#1e2a4a] dark:text-gray-100">Unidad (opcional)</label>
                    <select id="bus_id" name="bus_id" class="mt-2 h-12 {{ $fieldBase }} @error('bus_id') {{ $fieldError }} @else {{ $fieldOk }} @enderror">
                        <option value="">No estoy seguro</option>
                        @foreach ($buses as $bus)
                            <option value="{{ $bus->id }}" @selected((string) old('bus_id') === (string) $bus->id)>
                                {{ $bus->license_plate }} @if ($bus->model) · {{ $bus->model }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('bus_id')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="latitude" class="block text-xs font-semibold text-[#1e2a4a] dark:text-gray-100">Latitud (opcional)</label>
                        <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude') }}"
                            placeholder="17.0612345" class="mt-2 h-12 {{ $fieldBase }} @error('latitude') {{ $fieldError }} @else {{ $fieldOk }} @enderror">
                        @error('latitude')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="longitude" class="block text-xs font-semibold text-[#1e2a4a] dark:text-gray-100">Longitud (opcional)</label>
                        <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude') }}"
                            placeholder="-96.7256789" class="mt-2 h-12 {{ $fieldBase }} @error('longitude') {{ $fieldError }} @else {{ $fieldOk }} @enderror">
                        @error('longitude')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('passenger.menu') }}"
                        class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-sm font-semibold text-[#3d4a6b] shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#e06c6c] to-[#b93f4d] px-6 text-sm font-semibold text-white shadow-lg shadow-rose-500/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-rose-300/40 active:scale-[.99]">
                        Enviar emergencia
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-passenger-layout>
