<section>
    <header>
        <h2 class="text-[15px] font-semibold text-[#2f3a5f] dark:text-gray-100">
            Información del perfil
        </h2>

        <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
            Actualiza tus datos personales y tu correo electrónico.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="first_name" :value="__('Nombre')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full"
                    :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Apellido Paterno')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full"
                    :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>

            <div>
                <x-input-label for="maternal_last_name" :value="__('Apellido Materno')" />
                <x-text-input id="maternal_last_name" name="maternal_last_name" type="text" class="mt-1 block w-full"
                    :value="old('maternal_last_name', $user->maternal_last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('maternal_last_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="phone" :value="__('Teléfono')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" maxlength="10"
                :value="old('phone', $user->phone)" required autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-[11px] text-slate-600 dark:text-gray-300">
                        Tu correo aún no está verificado.

                        <button form="send-verification" class="rounded-md text-[11px] font-semibold text-[#4b5a85] underline focus:outline-none focus:ring-2 focus:ring-[#6b7bad]/30 dark:text-gray-200">
                            Reenviar el correo de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-[11px] font-medium text-emerald-600 dark:text-emerald-300">
                            Enviamos un nuevo enlace de verificación a tu correo.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-[11px] font-medium text-emerald-600 dark:text-emerald-300"
                >Guardado.</p>
            @endif
        </div>
    </form>
</section>
