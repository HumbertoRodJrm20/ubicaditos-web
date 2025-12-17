<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Editar Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" x-data="userForm()" @submit="handleSubmit">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre(s) -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre(s) <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" id="first_name" required
                                       value="{{ old('first_name', $user->first_name) }}"
                                       x-model="first_name"
                                       @input="validateFirstName"
                                       @blur="touchedFields.first_name = true; validateFirstName()"
                                       maxlength="35"
                                       class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                       :class="touchedFields.first_name && errors.first_name ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.first_name && errors.first_name" x-text="errors.first_name" class="mt-1 text-sm text-red-600"></p>
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Apellido Paterno -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apellido Paterno <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" required
                                       value="{{ old('last_name', $user->last_name) }}"
                                       x-model="last_name"
                                       @input="validateLastName"
                                       @blur="touchedFields.last_name = true; validateLastName()"
                                       maxlength="35"
                                       class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                       :class="touchedFields.last_name && errors.last_name ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.last_name && errors.last_name" x-text="errors.last_name" class="mt-1 text-sm text-red-600"></p>
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Apellido Materno -->
                            <div>
                                <label for="maternal_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apellido Materno <span class="text-red-500">*</span></label>
                                <input type="text" name="maternal_last_name" id="maternal_last_name" required
                                       value="{{ old('maternal_last_name', $user->maternal_last_name) }}"
                                       x-model="maternal_last_name"
                                       @input="validateMaternalLastName"
                                       @blur="touchedFields.maternal_last_name = true; validateMaternalLastName()"
                                       maxlength="35"
                                       class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                       :class="touchedFields.maternal_last_name && errors.maternal_last_name ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.maternal_last_name && errors.maternal_last_name" x-text="errors.maternal_last_name" class="mt-1 text-sm text-red-600"></p>
                                @error('maternal_last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email', $user->email) }}"
                                       x-model="email"
                                       @input="validateEmail"
                                       @blur="touchedFields.email = true; validateEmail()"
                                       class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                       :class="touchedFields.email && errors.email ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.email && errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></p>
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Teléfono -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" id="phone" required
                                       value="{{ old('phone', $user->phone) }}"
                                       x-model="phone"
                                       @input="validatePhone"
                                       @blur="touchedFields.phone = true; validatePhone()"
                                       maxlength="10"
                                       placeholder="10 dígitos"
                                       class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                       :class="touchedFields.phone && errors.phone ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.phone && errors.phone" x-text="errors.phone" class="mt-1 text-sm text-red-600"></p>
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Rol -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol <span class="text-red-500">*</span></label>
                                <select name="role" id="role" required
                                        x-model="role"
                                        @change="validateRole"
                                        @blur="touchedFields.role = true; validateRole()"
                                        class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                        :class="touchedFields.role && errors.role ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrador</option>
                                    <option value="driver" {{ old('role', $user->role) === 'driver' ? 'selected' : '' }}>Conductor</option>
                                    <option value="passenger" {{ old('role', $user->role) === 'passenger' ? 'selected' : '' }}>Pasajero</option>
                                </select>
                                <p x-show="touchedFields.role && errors.role" x-text="errors.role" class="mt-1 text-sm text-red-600"></p>
                                @error('role')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Horario Laboral (solo para conductores) -->
                            <div x-show="role === 'driver'">
                                <label for="work_shift" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Horario Laboral <span class="text-red-500">*</span></label>
                                <select name="work_shift" id="work_shift"
                                        x-model="work_shift"
                                        @change="validateWorkShift"
                                        @blur="touchedFields.work_shift = true; validateWorkShift()"
                                        :required="role === 'driver'"
                                        class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                        :class="touchedFields.work_shift && errors.work_shift ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                    <option value="">Seleccionar horario</option>
                                    <option value="morning" {{ old('work_shift', $user->work_shift) === 'morning' ? 'selected' : '' }}>6:00 AM - 2:00 PM</option>
                                    <option value="afternoon" {{ old('work_shift', $user->work_shift) === 'afternoon' ? 'selected' : '' }}>2:00 PM - 10:00 PM</option>
                                </select>
                                <p x-show="touchedFields.work_shift && errors.work_shift" x-text="errors.work_shift" class="mt-1 text-sm text-red-600"></p>
                                @error('work_shift')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                                <input type="password" name="password" id="password"
                                       placeholder="Dejar en blanco para mantener la actual. Mínimo 8 caracteres, incluir letras, números y símbolos"
                                       x-model="password"
                                       @input="validatePassword"
                                       @blur="touchedFields.password = true; validatePassword()"
                                       class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-300"
                                       :class="touchedFields.password && errors.password ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'">
                                <p x-show="touchedFields.password && errors.password" x-text="errors.password" class="mt-1 text-sm text-red-600"></p>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="flex items-center">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1"
                                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Usuario activo</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ url()->previous() }}"
                               class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="px-4 py-2 bg-primary-500 text-white rounded-md hover:bg-primary-600">
                                Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function userForm() {
            return {
                first_name: '{{ old('first_name', $user->first_name) }}',
                last_name: '{{ old('last_name', $user->last_name) }}',
                maternal_last_name: '{{ old('maternal_last_name', $user->maternal_last_name) }}',
                email: '{{ old('email', $user->email) }}',
                phone: '{{ old('phone', $user->phone) }}',
                role: '{{ old('role', $user->role) }}',
                work_shift: '{{ old('work_shift', $user->work_shift) }}',
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
                    // En edición, la contraseña es opcional
                    if (this.password && this.password.trim() !== '') {
                        if (this.password.length < 8) {
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
