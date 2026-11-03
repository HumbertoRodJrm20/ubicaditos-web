@php
    $oldCategory = old('category', '');
    $oldDescription = old('description', '');
    $descriptionLength = mb_strlen($oldDescription);

    $fieldBase = 'w-full rounded-2xl border bg-white px-4 text-sm text-[#1e2a4a] shadow-sm outline-none transition placeholder:text-[#a0a8bd] focus:ring-4';
    $fieldOk = 'border-slate-200 focus:border-[#6b7bad] focus:ring-[#6b7bad]/20';
    $fieldError = 'border-rose-300 focus:border-rose-400 focus:ring-rose-100';

    $statuses = [
        ['label' => 'Pendiente', 'classes' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500', 'help' => 'El reporte fue recibido y espera revisión.'],
        ['label' => 'En revisión', 'classes' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500', 'help' => 'El equipo está analizando la incidencia.'],
        ['label' => 'Resuelto', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500', 'help' => 'La incidencia ya fue atendida.'],
    ];
@endphp

<x-passenger-layout title="Reportar incidencia · Ubicaditos" active="Reportes">
    <div class="mx-auto max-w-5xl">

        <a href="{{ route('incidents.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#5b6788] transition hover:text-[#2f3a5f] focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/20">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
            Volver a mis reportes
        </a>

        <header class="mt-3">
            <p class="text-[11px] font-semibold uppercase tracking-[.18em] text-[#697aa8]">Centro de reportes</p>
            <h1 class="mt-1 text-[28px] font-bold leading-tight tracking-tight sm:text-3xl">Reportar una incidencia</h1>
            <p class="mt-1 max-w-2xl text-xs text-[#5b6788]">
                Cuéntanos qué ocurrió. Tu reporte quedará asociado a tu cuenta y podrás consultar su estado.
            </p>
        </header>

        @if ($errors->any())
            <div class="mt-5 rounded-2xl bg-rose-50 px-4 py-3 text-rose-700 ring-1 ring-rose-100" role="alert">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" />
                    </svg>
                    <div>
                        <p class="text-xs font-semibold">Revisa la información antes de continuar:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:items-start">

            {{-- Formulario --}}
            <section class="rounded-[28px] border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl sm:p-6">
                <form method="POST" action="{{ route('incidents.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="category" class="block text-xs font-semibold text-[#1e2a4a]">
                            Tipo de incidencia <span class="text-rose-500" aria-hidden="true">*</span>
                        </label>
                        <p class="mt-0.5 text-[11px] text-[#6b7699]">Selecciona la opción que mejor describa el problema.</p>

                        <div class="relative mt-2">
                            <select id="category" name="category" required
                                aria-describedby="category-help @error('category') category-error @enderror"
                                class="h-12 appearance-none pr-10 {{ $fieldBase }} @error('category') {{ $fieldError }} @else {{ $fieldOk }} @enderror">
                                <option value="" disabled @selected($oldCategory === '')>Selecciona una categoría</option>
                                @foreach ($categories as $value => $label)
                                    <option value="{{ $value }}" @selected($oldCategory === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-[#8a94b3]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </span>
                        </div>

                        <p id="category-help" class="mt-1.5 text-[11px] text-[#8a94b3]">Solo se aceptan las categorías disponibles en el sistema.</p>

                        @error('category')
                            <p id="category-error" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ count: {{ $descriptionLength }} }">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <label for="description" class="block text-xs font-semibold text-[#1e2a4a]">
                                    Descripción <span class="text-rose-500" aria-hidden="true">*</span>
                                </label>
                                <p class="mt-0.5 text-[11px] text-[#6b7699]">Incluye detalles como ruta, unidad, lugar o momento del incidente.</p>
                            </div>
                            <p class="shrink-0 text-[11px] tabular-nums text-[#8a94b3]">
                                <span x-text="count">{{ $descriptionLength }}</span> / {{ $maxDescriptionLength }}
                            </p>
                        </div>

                        <textarea id="description" name="description" rows="7" required
                            maxlength="{{ $maxDescriptionLength }}"
                            aria-describedby="description-help @error('description') description-error @enderror"
                            @input="count = $event.target.value.length"
                            placeholder="Ej. El autobús de la ruta OAX-101 no pasó por la parada de Santo Domingo alrededor de las 8:00 a. m."
                            class="mt-2 resize-y {{ $fieldBase }} py-3 leading-6 @error('description') {{ $fieldError }} @else {{ $fieldOk }} @enderror">{{ $oldDescription }}</textarea>

                        <p id="description-help" class="mt-1.5 text-[11px] text-[#8a94b3]">
                            La descripción es obligatoria y admite hasta {{ $maxDescriptionLength }} caracteres.
                        </p>

                        @error('description')
                            <p id="description-error" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('incidents.index') }}"
                            class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-sm font-semibold text-[#3d4a6b] shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/20">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-6 text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 active:scale-[.99]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m22 2-7 20-4-9-9-4Z" /><path d="M22 2 11 13" />
                            </svg>
                            Enviar reporte
                        </button>
                    </div>
                </form>
            </section>

            {{-- Información de apoyo --}}
            <aside class="space-y-4">
                <section class="rounded-[28px] border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eef2fa] text-[#4a5a8a]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-[#1e2a4a]">Antes de enviar</h2>
                    </div>

                    <ul class="mt-3 space-y-2 text-[11px] leading-5 text-[#5b6788]">
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#6b7bad]"></span>
                            Describe hechos concretos; evita datos personales innecesarios.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#6b7bad]"></span>
                            Si recuerdas la ruta, la unidad o la parada, inclúyelos en la descripción.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#6b7bad]"></span>
                            Solo tú puedes consultar los reportes asociados a tu cuenta.
                        </li>
                    </ul>
                </section>

                <section class="rounded-[28px] border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">
                    <h2 class="text-sm font-bold text-[#1e2a4a]">Estados del reporte</h2>
                    <ul class="mt-3 space-y-3">
                        @foreach ($statuses as $status)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $status['classes'] }}">
                                    <span class="h-1 w-1 rounded-full {{ $status['dot'] }}"></span>
                                    {{ $status['label'] }}
                                </span>
                                <span class="text-[11px] leading-4 text-[#5b6788]">{{ $status['help'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</x-passenger-layout>
