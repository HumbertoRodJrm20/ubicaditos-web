<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" x-data="registerForm()" @submit.prevent="submitForm">
        @csrf

        <div class="space-y-4">
            <!-- First Name -->
            <div>
                <x-input-label for="first_name" :value="__('Nombre')" />
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name"
                    :value="old('first_name')" required autofocus autocomplete="given-name"
                    x-model="first_name"
                    @input="validateFirstName"
                    @blur="touchedFields.first_name = true"
                    maxlength="35"
                    ::class="touchedFields.first_name && errors.first_name ? 'border-red-500' : ''" />
                <p x-show="touchedFields.first_name && errors.first_name" x-text="errors.first_name" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            <!-- Last Name -->
            <div>
                <x-input-label for="last_name" :value="__('Apellido Paterno')" />
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name"
                    :value="old('last_name')" required autocomplete="family-name"
                    x-model="last_name"
                    @input="validateLastName"
                    @blur="touchedFields.last_name = true"
                    maxlength="35"
                    ::class="touchedFields.last_name && errors.last_name ? 'border-red-500' : ''" />
                <p x-show="touchedFields.last_name && errors.last_name" x-text="errors.last_name" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>

            <!-- Maternal Last Name -->
            <div>
                <x-input-label for="maternal_last_name" :value="__('Apellido Materno')" />
                <x-text-input id="maternal_last_name" class="block mt-1 w-full" type="text" name="maternal_last_name"
                    :value="old('maternal_last_name')" required
                    x-model="maternal_last_name"
                    @input="validateMaternalLastName"
                    @blur="touchedFields.maternal_last_name = true"
                    maxlength="35"
                    ::class="touchedFields.maternal_last_name && errors.maternal_last_name ? 'border-red-500' : ''" />
                <p x-show="touchedFields.maternal_last_name && errors.maternal_last_name" x-text="errors.maternal_last_name" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('maternal_last_name')" class="mt-2" />
            </div>

            <!-- Phone -->
            <div>
                <x-input-label for="phone" :value="__('Teléfono')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone"
                    :value="old('phone')" required
                    x-model="phone"
                    @input="validatePhone"
                    @blur="touchedFields.phone = true"
                    maxlength="10"
                    placeholder="10 dígitos"
                    ::class="touchedFields.phone && errors.phone ? 'border-red-500' : ''" />
                <p x-show="touchedFields.phone && errors.phone" x-text="errors.phone" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                    :value="old('email')" required autocomplete="username"
                    x-model="email"
                    @input="validateEmail"
                    @blur="touchedFields.email = true"
                    ::class="touchedFields.email && errors.email ? 'border-red-500' : ''" />
                <p x-show="touchedFields.email && errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Contraseña')" />
                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password"
                                x-model="password"
                                @input="validatePassword"
                                @blur="touchedFields.password = true"
                                ::class="touchedFields.password && errors.password ? 'border-red-500' : ''" />
                <p x-show="touchedFields.password && errors.password" x-text="errors.password" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation"
                                required autocomplete="new-password"
                                x-model="password_confirmation"
                                @input="validatePasswordConfirmation"
                                @blur="touchedFields.password_confirmation = true"
                                ::class="touchedFields.password_confirmation && errors.password_confirmation ? 'border-red-500' : ''" />
                <p x-show="touchedFields.password_confirmation && errors.password_confirmation" x-text="errors.password_confirmation" class="mt-1 text-sm text-red-600"></p>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Registrarse') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        function registerForm() {
            return {
                first_name: '{{ old('first_name') }}',
                last_name: '{{ old('last_name') }}',
                maternal_last_name: '{{ old('maternal_last_name') }}',
                phone: '{{ old('phone') }}',
                email: '{{ old('email') }}',
                password: '',
                password_confirmation: '',
                errors: {},
                touchedFields: {},
                emailCheckTimeout: null,

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
                    // Solo permitir números
                    this.phone = this.phone.replace(/\D/g, '');

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
                        return;
                    }

                    // Validar formato de email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.email)) {
                        this.errors.email = 'El formato del email no es válido';
                        return;
                    }

                    // Esperar 500ms antes de verificar en el servidor
                    this.emailCheckTimeout = setTimeout(() => {
                        fetch('{{ route('check-email') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: this.email })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.available) {
                                this.errors.email = 'Este email ya está registrado';
                            } else {
                                delete this.errors.email;
                            }
                        })
                        .catch(error => {
                            console.error('Error validando email:', error);
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
</x-guest-layout>
