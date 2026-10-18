<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Confirmar contraseña · Ubicaditos</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-[#1e2a4a] bg-white">
    <div class="relative min-h-screen overflow-hidden bg-white">

        {{-- Fondo con manchas difuminadas --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 right-0 h-[520px] w-[640px] rounded-full bg-[#bcc8e3] blur-[110px] opacity-90"></div>
            <div class="absolute -bottom-40 -left-32 h-[460px] w-[620px] rounded-full bg-[#c3d7ec] blur-[110px] opacity-90"></div>
            <div class="absolute -bottom-40 left-1/3 h-[380px] w-[620px] rounded-full bg-[#dfe5f4] blur-[120px] opacity-80"></div>
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
            <main class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-2 lg:gap-16">

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

                {{-- Columna derecha: tarjeta --}}
                <section class="flex justify-center lg:justify-end lg:pr-4">
                    <div class="w-full max-w-md rounded-[28px] border border-white/70 bg-white/75 p-8 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">

                        {{-- Ícono de candado --}}
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] shadow-md shadow-[#4a5a8a]/30">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="5" y="11" width="14" height="9" rx="2.5" />
                                <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                            </svg>
                        </div>

                        <h2 class="mt-5 text-2xl font-bold tracking-tight">Confirma tu contraseña</h2>
                        <p class="mt-1 text-xs leading-relaxed text-[#5b6788]">
                            Esta es un área segura. Confirma tu contraseña antes de continuar.
                        </p>

                        <form method="POST" action="{{ route('password.confirm') }}" x-data="confirmForm()" @submit.prevent="submitForm" class="mt-5" novalidate>
                            @csrf

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-xs font-medium text-[#3d4a6b]">Contraseña</label>
                                <div class="relative mt-2">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[#6b7699]">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="5" y="11" width="14" height="9" rx="2.5" />
                                            <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                                        </svg>
                                    </span>
                                    <input id="password" name="password" required autofocus autocomplete="current-password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        x-model="password"
                                        @input="validatePassword"
                                        @blur="touched = true; validatePassword()"
                                        :class="touched && errors.password ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-slate-200 focus:border-[#6b7bad] focus:ring-[#6b7bad]/25'"
                                        class="block h-12 w-full rounded-xl border bg-white pl-11 pr-12 text-sm text-[#1e2a4a] placeholder:text-[#9aa3bd] shadow-sm focus:ring-4" />

                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-[#6b7699] hover:text-[#3d4a6b] focus:outline-none"
                                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                        <svg x-show="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 3l18 18" />
                                            <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a10 10 0 0 0 4.4-1" />
                                            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                        </svg>
                                    </button>
                                </div>
                                <p x-show="touched && errors.password" x-text="errors.password" x-cloak class="mt-1 text-xs text-red-600"></p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <!-- Submit -->
                            <button type="submit"
                                class="mt-5 flex h-12 w-full items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 active:scale-[.99]">
                                Confirmar
                            </button>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>

    <script>
        function confirmForm() {
            return {
                password: '',
                showPassword: false,
                errors: {},
                touched: false,

                validatePassword() {
                    if (!this.password || this.password === '') {
                        this.errors.password = 'La contraseña es obligatoria';
                    } else {
                        delete this.errors.password;
                    }
                },

                submitForm(event) {
                    this.touched = true;
                    this.validatePassword();

                    // Prevenir el envío si hay errores
                    if (Object.keys(this.errors).length > 0) {
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