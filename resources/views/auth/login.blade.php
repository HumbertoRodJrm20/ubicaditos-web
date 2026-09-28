<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="loginForm()" @submit.prevent="submitForm">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                required autofocus autocomplete="username"
                x-model="email"
                @input="validateEmail"
                @blur="touchedFields.email = true; validateEmail()"
                ::class="touchedFields.email && errors.email ? 'border-red-500' : ''" />
            <p x-show="touchedFields.email && errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            x-model="password"
                            @input="validatePassword"
                            @blur="touchedFields.password = true; validatePassword()"
                            ::class="touchedFields.password && errors.password ? 'border-red-500' : ''" />
            <p x-show="touchedFields.password && errors.password" x-text="errors.password" class="mt-1 text-sm text-red-600"></p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Recuérdame</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center gap-3">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif

                @if (Route::has('register'))
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('register') }}">
                        Registrarme
                    </a>
                @endif
            </div>

            <x-primary-button class="ms-3">
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>

    <script>
        function loginForm() {
            return {
                email: '{{ old('email') }}',
                password: '',
                errors: {},
                touchedFields: {},

                validateEmail() {
                    if (!this.email || this.email.trim() === '') {
                        this.errors.email = 'El correo electrónico es obligatorio';
                    } else {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(this.email)) {
                            this.errors.email = 'El formato del correo electrónico no es válido';
                        } else {
                            delete this.errors.email;
                        }
                    }
                },

                validatePassword() {
                    if (!this.password || this.password === '') {
                        this.errors.password = 'La contraseña es obligatoria';
                    } else {
                        delete this.errors.password;
                    }
                },

                submitForm(event) {
                    // Marcar todos los campos como touched
                    this.touchedFields.email = true;
                    this.touchedFields.password = true;

                    // Validar todos los campos
                    this.validateEmail();
                    this.validatePassword();

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
</x-guest-layout>
