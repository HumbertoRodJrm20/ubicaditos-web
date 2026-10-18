@php
    // Clases compartidas para los campos del formulario
    $input = 'block h-11 w-full rounded-xl border bg-white pl-10 pr-4 text-sm text-[#1e2a4a] placeholder:text-[#9aa3bd] shadow-sm focus:ring-4';
    $inputPass = 'block h-11 w-full rounded-xl border bg-white pl-10 pr-11 text-sm text-[#1e2a4a] placeholder:text-[#9aa3bd] shadow-sm focus:ring-4';
    $ok = 'border-slate-200 focus:border-[#6b7bad] focus:ring-[#6b7bad]/25';
    $bad = 'border-red-500 focus:border-red-500 focus:ring-red-200';
    $label = 'block text-xs font-medium text-[#3d4a6b]';
    $icon = 'pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#6b7699]';
    $eyeBtn = 'absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#6b7699] hover:text-[#3d4a6b] focus:outline-none';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Crear cuenta · Ubicaditos</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-[#1e2a4a] bg-white">
    <div class="relative min-h-screen overflow-hidden bg-white">

        {{-- Fondo con manchas difuminadas --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 -right-32 h-[520px] w-[620px] rounded-full bg-[#c3d7ec] blur-[110px] opacity-90"></div>
            <div class="absolute top-1/3 -left-40 h-[460px] w-[560px] rounded-full bg-[#c5cbe6] blur-[110px] opacity-80"></div>
            <div class="absolute -bottom-40 left-1/3 h-[420px] w-[720px] rounded-full bg-[#cfd6ee] blur-[120px] opacity-80"></div>
            <div class="absolute -bottom-32 -right-20 h-[360px] w-[520px] rounded-full bg-[#c9d3ea] blur-[110px] opacity-70"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-[1400px] flex-col px-6 py-6 sm:px-10 lg:px-12">

            {{-- Logo --}}
            <header class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] shadow-md shadow-[#4a5a8a]/30">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="4" width="16" height="13" rx="3" />
                        <path d="M4 11h16" />
                        <circle cx="8.5" cy="14" r=".6" fill="currentColor" />
                        <circle cx="15.5" cy="14" r=".6" fill="currentColor" />
                        <path d="M7 17v2M17 17v2" />
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">Ubicaditos</span>
            </header>

            {{-- Contenido principal --}}
            <main class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[1fr_1.15fr] lg:gap-16">

                {{-- Columna izquierda: mensaje --}}
                <section class="max-w-xl lg:pl-4">
                    <h1 class="text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-[56px]">
                        Sigue tu autobús<br class="hidden sm:block"> en tiempo real.
                    </h1>
                    <p class="mt-6 max-w-md text-base leading-relaxed text-[#5b6788] sm:text-lg">
                        Rutas, paradas y tiempos de llegada del transporte público de tu ciudad, siempre a la mano.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-3 shadow-sm shadow-[#4a5a8a]/10 backdrop-blur">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a]">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="4" width="16" height="13" rx="3" />
                                    <path d="M4 11h16" />
                                    <path d="M7 17v2M17 17v2" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold leading-tight">Ruta 2 · Universidad</p>
                                <p class="text-xs text-[#6b7699]">Llega en 4 min</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-3 shadow-sm shadow-[#4a5a8a]/10 backdrop-blur">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#4f8f9c] to-[#7fb8b4]">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" />
                                    <circle cx="12" cy="9.5" r="2.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold leading-tight">12 autobuses</p>
                                <p class="text-xs text-[#6b7699]">activos ahora</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Columna derecha: tarjeta de registro --}}
                <section class="flex justify-center lg:justify-end lg:pr-4">
                    <div class="w-full max-w-[560px] rounded-[28px] border border-white/70 bg-white/75 p-8 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">

                        <h2 class="text-2xl font-bold tracking-tight">Crea tu cuenta</h2>
                        <p class="mt-1 text-xs text-[#5b6788]">Regístrate como pasajero y guarda tus paradas favoritas.</p>

                        <form method="POST" action="{{ route('register') }}" x-data="registerForm()" @submit.prevent="submitForm" class="mt-6" novalidate>
                            @csrf

                            <div class="grid gap-x-4 gap-y-4 sm:grid-cols-2">

                                <!-- First Name -->
                                <div>
                                    <label for="first_name" class="{{ $label }}">Nombre</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6" /></svg>
                                        </span>
                                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}"
                                            required autofocus autocomplete="given-name" maxlength="35" placeholder="Mariana"
                                            x-model="first_name"
                                            @input="validateFirstName"
                                            @blur="touchedFields.first_name = true; validateFirstName()"
                                            :class="touchedFields.first_name && errors.first_name ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $input }}" />
                                    </div>
                                    <p x-show="touchedFields.first_name && errors.first_name" x-text="errors.first_name" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label for="last_name" class="{{ $label }}">Apellido paterno</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6" /></svg>
                                        </span>
                                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}"
                                            required autocomplete="family-name" maxlength="35" placeholder="López"
                                            x-model="last_name"
                                            @input="validateLastName"
                                            @blur="touchedFields.last_name = true; validateLastName()"
                                            :class="touchedFields.last_name && errors.last_name ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $input }}" />
                                    </div>
                                    <p x-show="touchedFields.last_name && errors.last_name" x-text="errors.last_name" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                                </div>

                                <!-- Maternal Last Name -->
                                <div>
                                    <label for="maternal_last_name" class="{{ $label }}">Apellido materno</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6" /></svg>
                                        </span>
                                        <input id="maternal_last_name" name="maternal_last_name" type="text" value="{{ old('maternal_last_name') }}"
                                            required maxlength="35" placeholder="Hernández"
                                            x-model="maternal_last_name"
                                            @input="validateMaternalLastName"
                                            @blur="touchedFields.maternal_last_name = true; validateMaternalLastName()"
                                            :class="touchedFields.maternal_last_name && errors.maternal_last_name ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $input }}" />
                                    </div>
                                    <p x-show="touchedFields.maternal_last_name && errors.maternal_last_name" x-text="errors.maternal_last_name" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <x-input-error :messages="$errors->get('maternal_last_name')" class="mt-1" />
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="{{ $label }}">Celular</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" /></svg>
                                        </span>
                                        <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone') }}"
                                            required autocomplete="tel-national" placeholder="10 dígitos"
                                            x-model="phone"
                                            @input="validatePhone"
                                            @blur="touchedFields.phone = true; validatePhone()"
                                            :class="touchedFields.phone && errors.phone ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $input }}" />
                                    </div>
                                    <p x-show="touchedFields.phone && errors.phone" x-text="errors.phone" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                </div>

                                <!-- Email Address -->
                                <div class="sm:col-span-2">
                                    <label for="email" class="{{ $label }}">Correo electrónico</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5" /><path d="m4 7 8 6 8-6" /></svg>
                                        </span>
                                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                                            required autocomplete="username" placeholder="tucorreo@ejemplo.com"
                                            x-model="email"
                                            @input="validateEmail"
                                            @blur="touchedFields.email = true; validateEmail()"
                                            :class="touchedFields.email && errors.email ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $input }}" />
                                    </div>
                                    <p x-show="touchedFields.email && errors.email" x-text="errors.email" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <p x-show="emailStatus === 'checking' && !errors.email" x-cloak class="mt-1 text-xs text-[#6b7699]">Verificando correo…</p>
                                    <p x-show="emailStatus === 'available' && !errors.email" x-cloak class="mt-1 text-xs text-emerald-600">Correo disponible</p>
                                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                                </div>

                                <!-- Password -->
                                <div>
                                    <label for="password" class="{{ $label }}">Contraseña</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.5" /><path d="M8 11V8a4 4 0 0 1 8 0v3" /></svg>
                                        </span>
                                        <input id="password" name="password" required autocomplete="new-password" placeholder="••••••••"
                                            :type="showPassword ? 'text' : 'password'"
                                            x-model="password"
                                            @input="validatePassword"
                                            @blur="touchedFields.password = true; validatePassword()"
                                            :class="touchedFields.password && errors.password ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $inputPass }}" />
                                        <button type="button" @click="showPassword = !showPassword" class="{{ $eyeBtn }}"
                                            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                            <svg x-show="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                            <svg x-show="showPassword" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18" /><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a10 10 0 0 0 4.4-1" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                                        </button>
                                    </div>
                                    <p x-show="touchedFields.password && errors.password" x-text="errors.password" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="password_confirmation" class="{{ $label }}">Confirmar contraseña</label>
                                    <div class="relative mt-1.5">
                                        <span class="{{ $icon }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.5" /><path d="M8 11V8a4 4 0 0 1 8 0v3" /></svg>
                                        </span>
                                        <input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••"
                                            :type="showConfirm ? 'text' : 'password'"
                                            x-model="password_confirmation"
                                            @input="validatePasswordConfirmation"
                                            @blur="touchedFields.password_confirmation = true; validatePasswordConfirmation()"
                                            :class="touchedFields.password_confirmation && errors.password_confirmation ? '{{ $bad }}' : '{{ $ok }}'"
                                            class="{{ $inputPass }}" />
                                        <button type="button" @click="showConfirm = !showConfirm" class="{{ $eyeBtn }}"
                                            :aria-label="showConfirm ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                            <svg x-show="!showConfirm" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                            <svg x-show="showConfirm" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18" /><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a10 10 0 0 0 4.4-1" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                                        </button>
                                    </div>
                                    <p x-show="touchedFields.password_confirmation && errors.password_confirmation" x-text="errors.password_confirmation" x-cloak class="mt-1 text-xs text-red-600"></p>
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                                </div>
                            </div>

                            <!-- Indicador de seguridad de la contraseña -->
                            <div class="mt-4">
                                <div class="grid grid-cols-4 gap-2">
                                    <template x-for="i in 4" :key="i">
                                        <span class="h-1 rounded-full transition-colors"
                                            :class="strength >= i ? strengthColor : 'bg-slate-200'"></span>
                                    </template>
                                </div>
                                <p class="mt-1.5 text-[11px] text-[#6b7699]" x-text="strengthLabel"></p>
                            </div>

                            <!-- Submit -->
                            <button type="submit"
                                class="mt-5 flex h-12 w-full items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 active:scale-[.99]">
                                Crear cuenta
                            </button>

                            <p class="mt-5 text-xs text-[#5b6788]">
                                ¿Ya tienes cuenta?
                                <a href="{{ route('login') }}" class="font-semibold text-[#1e2a4a] hover:underline">Inicia sesión</a>
                            </p>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>

    <script>
        function registerForm() {
            return {
                first_name: @js(old('first_name', '')),
                last_name: @js(old('last_name', '')),
                maternal_last_name: @js(old('maternal_last_name', '')),
                phone: @js(old('phone', '')),
                email: @js(old('email', '')),
                password: '',
                password_confirmation: '',
                showPassword: false,
                showConfirm: false,
                errors: {},
                touchedFields: {},
                emailCheckTimeout: null,
                emailStatus: null,        // null | 'checking' | 'available' | 'taken'
                lastCheckedEmail: null,

                // ---- Indicador de seguridad ----
                get strength() {
                    if (!this.password) return 0;
                    let score = 0;
                    if (this.password.length >= 8) score++;
                    if (/[A-Z]/.test(this.password)) score++;
                    if (/\d/.test(this.password)) score++;
                    if (/[^A-Za-z0-9]/.test(this.password)) score++;
                    return score;
                },
                get strengthColor() {
                    return ['', 'bg-red-400', 'bg-amber-400', 'bg-emerald-400', 'bg-emerald-500'][this.strength];
                },
                get strengthLabel() {
                    if (!this.password) return 'Usa mayúsculas, números y símbolos';
                    return ['', 'Contraseña muy débil · usa mayúsculas, números y símbolos', 'Contraseña débil · usa mayúsculas, números y símbolos', 'Contraseña buena · te falta un poco más', 'Contraseña segura'][this.strength];
                },

                // ---- Validaciones ----
                validateFirstName() {
                    if (!this.first_name || this.first_name.trim() === '') {
                        this.errors.first_name = 'El nombre es obligatorio';
                    } else if (!/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/.test(this.first_name)) {
                        this.errors.first_name = 'El nombre solo puede contener letras';
                    } else if (this.first_name.length > 35) {
                        this.errors.first_name = 'El nombre no puede tener más de 35 caracteres';
                    } else {
                        delete this.errors.first_name;
                    }
                },

                validateLastName() {
                    if (!this.last_name || this.last_name.trim() === '') {
                        this.errors.last_name = 'El apellido paterno es obligatorio';
                    } else if (!/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/.test(this.last_name)) {
                        this.errors.last_name = 'El apellido paterno solo puede contener letras';
                    } else if (this.last_name.length > 35) {
                        this.errors.last_name = 'El apellido paterno no puede tener más de 35 caracteres';
                    } else {
                        delete this.errors.last_name;
                    }
                },

                validateMaternalLastName() {
                    if (!this.maternal_last_name || this.maternal_last_name.trim() === '') {
                        this.errors.maternal_last_name = 'El apellido materno es obligatorio';
                    } else if (!/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/.test(this.maternal_last_name)) {
                        this.errors.maternal_last_name = 'El apellido materno solo puede contener letras';
                    } else if (this.maternal_last_name.length > 35) {
                        this.errors.maternal_last_name = 'El apellido materno no puede tener más de 35 caracteres';
                    } else {
                        delete this.errors.maternal_last_name;
                    }
                },

                validatePhone() {
                    // Solo permitir números y máximo 10 dígitos
                    this.phone = this.phone.replace(/\D/g, '').slice(0, 10);

                    if (!this.phone || this.phone.trim() === '') {
                        this.errors.phone = 'El teléfono es obligatorio';
                    } else if (!/^\d+$/.test(this.phone)) {
                        this.errors.phone = 'El teléfono solo puede contener números';
                    } else if (this.phone.length !== 10) {
                        this.errors.phone = 'El teléfono debe tener exactamente 10 dígitos';
                    } else {
                        delete this.errors.phone;
                    }
                },

                validateEmail() {
                    // Limpiar timeout anterior
                    if (this.emailCheckTimeout) {
                        clearTimeout(this.emailCheckTimeout);
                    }

                    if (!this.email || this.email.trim() === '') {
                        this.errors.email = 'El email es obligatorio';
                        this.emailStatus = null;
                        return;
                    }

                    // Validar formato de email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.email)) {
                        this.errors.email = 'El formato del email no es válido';
                        this.emailStatus = null;
                        return;
                    }

                    // Si ya verificamos este mismo correo, reutilizar el resultado
                    if (this.email === this.lastCheckedEmail && this.emailStatus) {
                        if (this.emailStatus === 'taken') {
                            this.errors.email = 'Este email ya está registrado';
                        } else {
                            delete this.errors.email;
                        }
                        return;
                    }

                    // Formato válido: limpiar error de formato y verificar en el servidor
                    delete this.errors.email;
                    this.emailStatus = 'checking';
                    const emailToCheck = this.email;

                    // Esperar 500ms antes de verificar en el servidor
                    this.emailCheckTimeout = setTimeout(() => {
                        fetch('{{ route('check-email') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: emailToCheck })
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Ignorar respuestas viejas si el usuario ya cambió el correo
                            if (emailToCheck !== this.email) return;

                            this.lastCheckedEmail = emailToCheck;
                            if (!data.available) {
                                this.emailStatus = 'taken';
                                this.errors.email = 'Este email ya está registrado';
                            } else {
                                this.emailStatus = 'available';
                                delete this.errors.email;
                            }
                        })
                        .catch(error => {
                            console.error('Error validando email:', error);
                            this.emailStatus = null;
                        });
                    }, 500);
                },

                validatePassword() {
                    if (!this.password || this.password === '') {
                        this.errors.password = 'La contraseña es obligatoria';
                    } else if (this.password.length < 8) {
                        this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                    } else if (!/(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/.test(this.password)) {
                        this.errors.password = 'La contraseña debe contener letras, números y caracteres especiales';
                    } else {
                        delete this.errors.password;
                    }
                    // Re-validar confirmación si ya se ingresó
                    if (this.touchedFields.password_confirmation) {
                        this.validatePasswordConfirmation();
                    }
                },

                validatePasswordConfirmation() {
                    if (!this.password_confirmation || this.password_confirmation === '') {
                        this.errors.password_confirmation = 'Debe confirmar la contraseña';
                    } else if (this.password !== this.password_confirmation) {
                        this.errors.password_confirmation = 'Las contraseñas no coinciden';
                    } else {
                        delete this.errors.password_confirmation;
                    }
                },

                submitForm(event) {
                    // Marcar todos los campos como touched
                    this.touchedFields.first_name = true;
                    this.touchedFields.last_name = true;
                    this.touchedFields.maternal_last_name = true;
                    this.touchedFields.phone = true;
                    this.touchedFields.email = true;
                    this.touchedFields.password = true;
                    this.touchedFields.password_confirmation = true;

                    // Validar todos los campos
                    this.validateFirstName();
                    this.validateLastName();
                    this.validateMaternalLastName();
                    this.validatePhone();
                    this.validateEmail();
                    this.validatePassword();
                    this.validatePasswordConfirmation();

                    // Verificar si hay errores
                    if (Object.keys(this.errors).length > 0) {
                        // Prevenir el envío si hay errores
                        event.preventDefault();
                        return false;
                    }

                    // Si no hay errores, enviar el formulario
                    event.target.submit();
                }
            }
        }
    </script>
</body>
</html>