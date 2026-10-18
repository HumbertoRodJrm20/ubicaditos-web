<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Crear Usuario') }}
        </h2>
    </x-slot>

    @php
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';
        $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';

        $input = 'block w-full rounded-full border border-slate-200/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
        $inputOk = 'border-slate-200/90 dark:border-gray-600';
        $inputError = 'border-rose-400 dark:border-rose-500';
    @endphp

    <style>
        .users-form-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        .users-form-page .field-error { font-size: 12px; }
    </style>

    <div class="users-form-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
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

            <div class="mb-7 text-center">
                <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                <h1 class="text-3xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-4xl">
                    Crear Usuario
                </h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Registra un nuevo administrador, conductor o pasajero.</p>
            </div>

            <section class="{{ $panel }} p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.users.store') }}" x-data="userForm()" @submit="handleSubmit">
                    @csrf

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6">
                        <!-- Nombre(s) -->
                        <div>
                            <label for="first_name" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Nombre(s) <span class="text-rose-500">*</span></label>
                            <input type="text" name="first_name" id="first_name" required
                                   value="{{ old('first_name') }}"
                                   x-model="first_name"
                                   @input="validateFirstName"
                                   @blur="touchedFields.first_name = true; validateFirstName()"
                                   maxlength="35"
                                   class="{{ $input }} mt-1"
                                   :class="touchedFields.first_name && errors.first_name ? '{{ $inputError }}' : '{{ $inputOk }}'">
                            <p x-show="touchedFields.first_name && errors.first_name" x-text="errors.first_name" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('first_name')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Apellido Paterno -->
                        <div>
                            <label for="last_name" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Apellido Paterno <span class="text-rose-500">*</span></label>
                            <input type="text" name="last_name" id="last_name" required
                                   value="{{ old('last_name') }}"
                                   x-model="last_name"
                                   @input="validateLastName"
                                   @blur="touchedFields.last_name = true; validateLastName()"
                                   maxlength="35"
                                   class="{{ $input }} mt-1"
                                   :class="touchedFields.last_name && errors.last_name ? '{{ $inputError }}' : '{{ $inputOk }}'">
                            <p x-show="touchedFields.last_name && errors.last_name" x-text="errors.last_name" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('last_name')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Apellido Materno -->
                        <div>
                            <label for="maternal_last_name" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Apellido Materno <span class="text-rose-500">*</span></label>
                            <input type="text" name="maternal_last_name" id="maternal_last_name" required
                                   value="{{ old('maternal_last_name') }}"
                                   x-model="maternal_last_name"
                                   @input="validateMaternalLastName"
                                   @blur="touchedFields.maternal_last_name = true; validateMaternalLastName()"
                                   maxlength="35"
                                   class="{{ $input }} mt-1"
                                   :class="touchedFields.maternal_last_name && errors.maternal_last_name ? '{{ $inputError }}' : '{{ $inputOk }}'">
                            <p x-show="touchedFields.maternal_last_name && errors.maternal_last_name" x-text="errors.maternal_last_name" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('maternal_last_name')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Email <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" id="email" required
                                   value="{{ old('email') }}"
                                   x-model="email"
                                   @input="validateEmail"
                                   @blur="touchedFields.email = true; validateEmail()"
                                   class="{{ $input }} mt-1"
                                   :class="touchedFields.email && errors.email ? '{{ $inputError }}' : '{{ $inputOk }}'">
                            <p x-show="touchedFields.email && errors.email" x-text="errors.email" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('email')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label for="phone" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Teléfono <span class="text-rose-500">*</span></label>
                            <input type="text" name="phone" id="phone" required
                                   value="{{ old('phone') }}"
                                   x-model="phone"
                                   @input="validatePhone"
                                   @blur="touchedFields.phone = true; validatePhone()"
                                   maxlength="10"
                                   placeholder="10 dígitos"
                                   class="{{ $input }} mt-1"
                                   :class="touchedFields.phone && errors.phone ? '{{ $inputError }}' : '{{ $inputOk }}'">
                            <p x-show="touchedFields.phone && errors.phone" x-text="errors.phone" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('phone')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Rol (oculto) -->
                        <input type="hidden" name="role" x-model="role" value="{{ old('role', request('role')) }}">

                        <!-- Tipo de Usuario (solo lectura) -->
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Tipo de Usuario</label>
                            <div class="mt-1 rounded-2xl border border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-gray-600 dark:bg-gray-700/60">
                                <span class="inline-flex rounded-full bg-[#53658f]/10 px-2.5 py-1 text-[11px] font-semibold text-[#53658f] ring-1 ring-[#53658f]/20 dark:bg-[#8295c5]/20 dark:text-[#b6c4e6] dark:ring-[#8295c5]/30" x-text="
                                    role === 'admin' ? 'Administrador' :
                                    role === 'driver' ? 'Conductor' :
                                    role === 'passenger' ? 'Pasajero' : 'No especificado'
                                "></span>
                            </div>
                        </div>

                        <!-- Horario Laboral (solo para conductores) -->
                        <div x-show="role === 'driver'">
                            <label for="work_shift" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Horario Laboral <span class="text-rose-500">*</span></label>
                            <select name="work_shift" id="work_shift"
                                    x-model="work_shift"
                                    @change="validateWorkShift"
                                    @blur="touchedFields.work_shift = true; validateWorkShift()"
                                    :required="role === 'driver'"
                                    class="{{ $input }} mt-1"
                                    :class="touchedFields.work_shift && errors.work_shift ? '{{ $inputError }}' : '{{ $inputOk }}'">
                                <option value="">Seleccionar horario</option>
                                <option value="morning" {{ old('work_shift') === 'morning' ? 'selected' : '' }}>6:00 AM - 2:00 PM</option>
                                <option value="afternoon" {{ old('work_shift') === 'afternoon' ? 'selected' : '' }}>2:00 PM - 10:00 PM</option>
                            </select>
                            <p x-show="touchedFields.work_shift && errors.work_shift" x-text="errors.work_shift" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('work_shift')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label for="password" class="mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400">Contraseña <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" id="password" required
                                   x-model="password"
                                   @input="validatePassword"
                                   @blur="touchedFields.password = true; validatePassword()"
                                   placeholder="Mínimo 8 caracteres, incluir letras, números y símbolos"
                                   class="{{ $input }} mt-1"
                                   :class="touchedFields.password && errors.password ? '{{ $inputError }}' : '{{ $inputOk }}'">
                            <p x-show="touchedFields.password && errors.password" x-text="errors.password" class="field-error mt-1 text-rose-600 dark:text-rose-400"></p>
                            @error('password')
                                <p class="field-error mt-1 text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ url()->previous() }}"
                           class="rounded-full border border-slate-200 bg-white/85 px-5 py-2.5 text-center text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                            Crear Usuario
                        </button>
                    </div>
                </form>
            </section>

            <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">Ubicaditos · Crear Usuario</p>
        </div>
    </div>

    <script>
        function userForm() {
            return {
                first_name: '{{ old('first_name') }}',
                last_name: '{{ old('last_name') }}',
                maternal_last_name: '{{ old('maternal_last_name') }}',
                email: '{{ old('email') }}',
                phone: '{{ old('phone') }}',
                role: '{{ old('role', request('role')) }}',
                work_shift: '{{ old('work_shift') }}',
                password: '',
                errors: {},
                touchedFields: {},

                validateFirstName() {
                    if (!this.first_name || this.first_name.trim() === '') {
                        this.errors.first_name = 'El nombre es obligatorio';
                    } else if (this.first_name.length > 35) {
                        this.errors.first_name = 'El nombre no puede tener más de 35 caracteres';
                    } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(this.first_name)) {
                        this.errors.first_name = 'El nombre solo puede contener letras';
                    } else {
                        delete this.errors.first_name;
                    }
                },

                validateLastName() {
                    if (!this.last_name || this.last_name.trim() === '') {
                        this.errors.last_name = 'El apellido paterno es obligatorio';
                    } else if (this.last_name.length > 35) {
                        this.errors.last_name = 'El apellido paterno no puede tener más de 35 caracteres';
                    } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(this.last_name)) {
                        this.errors.last_name = 'El apellido paterno solo puede contener letras';
                    } else {
                        delete this.errors.last_name;
                    }
                },

                validateMaternalLastName() {
                    if (!this.maternal_last_name || this.maternal_last_name.trim() === '') {
                        this.errors.maternal_last_name = 'El apellido materno es obligatorio';
                    } else if (this.maternal_last_name.length > 35) {
                        this.errors.maternal_last_name = 'El apellido materno no puede tener más de 35 caracteres';
                    } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(this.maternal_last_name)) {
                        this.errors.maternal_last_name = 'El apellido materno solo puede contener letras';
                    } else {
                        delete this.errors.maternal_last_name;
                    }
                },

                validateEmail() {
                    if (!this.email || this.email.trim() === '') {
                        this.errors.email = 'El email es obligatorio';
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                        this.errors.email = 'El email no es válido';
                    } else {
                        delete this.errors.email;
                    }
                },

                validatePhone() {
                    if (!this.phone || this.phone.trim() === '') {
                        this.errors.phone = 'El teléfono es obligatorio';
                    } else if (!/^[0-9]+$/.test(this.phone)) {
                        this.errors.phone = 'El teléfono solo puede contener números';
                    } else if (this.phone.length > 10) {
                        this.errors.phone = 'El teléfono no puede tener más de 10 dígitos';
                    } else if (this.phone.length < 10) {
                        this.errors.phone = 'El teléfono debe tener exactamente 10 dígitos';
                    } else {
                        delete this.errors.phone;
                    }
                },

                validateRole() {
                    if (!this.role || this.role === '') {
                        this.errors.role = 'Debe seleccionar un rol';
                    } else {
                        delete this.errors.role;
                    }
                },

                validatePassword() {
                    if (!this.password || this.password.trim() === '') {
                        this.errors.password = 'La contraseña es obligatoria';
                    } else if (this.password.length < 8) {
                        this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                    } else if (!/[a-zA-Z]/.test(this.password)) {
                        this.errors.password = 'La contraseña debe contener al menos una letra';
                    } else if (!/[0-9]/.test(this.password)) {
                        this.errors.password = 'La contraseña debe contener al menos un número';
                    } else if (!/[@$!%*#?&\-_+=\[\]{}|:;,.<>?~`^()]/.test(this.password)) {
                        this.errors.password = 'La contraseña debe contener al menos un carácter especial';
                    } else {
                        delete this.errors.password;
                    }
                },

                validateWorkShift() {
                    if (this.role === 'driver' && (!this.work_shift || this.work_shift === '')) {
                        this.errors.work_shift = 'El horario laboral es obligatorio para conductores';
                    } else {
                        delete this.errors.work_shift;
                    }
                },

                validateAll() {
                    this.validateFirstName();
                    this.validateLastName();
                    this.validateMaternalLastName();
                    this.validateEmail();
                    this.validatePhone();
                    this.validateRole();
                    this.validateWorkShift();
                    this.validatePassword();
                    return Object.keys(this.errors).length === 0;
                },

                handleSubmit(event) {
                    // Marcar todos los campos como tocados
                    this.touchedFields = {
                        first_name: true,
                        last_name: true,
                        maternal_last_name: true,
                        email: true,
                        phone: true,
                        role: true,
                        work_shift: true,
                        password: true
                    };

                    // Validar todos los campos
                    if (!this.validateAll()) {
                        event.preventDefault();
                        alert('Por favor, corrige los errores en el formulario antes de continuar.');
                        return false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
