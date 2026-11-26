<x-guest-layout>
    <section class="w-full max-w-2xl rounded-[24px] border border-white/80 bg-white/75 p-8 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
        <h1 class="mb-2 text-2xl font-semibold text-[#273252] dark:text-gray-100">Registro de conductores</h1>
        <p class="mb-6 text-sm text-slate-500 dark:text-gray-400">Tu solicitud quedará pendiente de aprobación. La foto de la licencia se guarda de forma privada.</p>

        <form method="POST" action="{{ route('driver.register.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="first_name" :value="__('Nombre')" />
                    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required autofocus />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="last_name" :value="__('Apellido paterno')" />
                    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="maternal_last_name" :value="__('Apellido materno')" />
                    <x-text-input id="maternal_last_name" name="maternal_last_name" type="text" class="mt-1 block w-full" :value="old('maternal_last_name')" required />
                    <x-input-error :messages="$errors->get('maternal_last_name')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="email" :value="__('Correo electrónico')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="phone" :value="__('Teléfono')" />
                    <x-text-input id="phone" name="phone" type="text" inputmode="numeric" class="mt-1 block w-full" :value="old('phone')" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="license_number" :value="__('Número de licencia')" />
                    <x-text-input id="license_number" name="license_number" type="text" class="mt-1 block w-full" :value="old('license_number')" required />
                    <x-input-error :messages="$errors->get('license_number')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="license_photo" :value="__('Foto de la licencia')" />
                <input id="license_photo" name="license_photo" type="file" accept="image/jpeg,image/png" required class="mt-1 block w-full text-sm text-slate-600 dark:text-gray-300">
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">JPG o PNG, máximo 2 MB.</p>
                <x-input-error :messages="$errors->get('license_photo')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="password" :value="__('Contraseña')" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                </div>
            </div>

            <div class="flex items-center justify-end">
                <x-primary-button>{{ __('Enviar solicitud') }}</x-primary-button>
            </div>
        </form>
    </section>
</x-guest-layout>
