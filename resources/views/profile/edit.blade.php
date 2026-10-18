@php
    $user = Auth::user();
    $attrs = $user->getAttributes();

    $firstField = collect(['first_name', 'nombres', 'nombre', 'name'])->first(fn ($c) => array_key_exists($c, $attrs)) ?? 'name';
    $lastField = collect(['last_name', 'apellidos', 'apellido', 'surname'])->first(fn ($c) => array_key_exists($c, $attrs));

    $firstName = $user->{$firstField};
    $lastName = $lastField ? $user->{$lastField} : '';
    $fullName = trim($firstName . ' ' . $lastName) ?: $user->name;

    $initials = collect(explode(' ', $fullName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $favoritesCount = $user->favoriteStops->count();

    $inputClass = 'w-full px-3 py-2.5 rounded-xl bg-white dark:bg-gray-700 border border-slate-200/80 dark:border-gray-600 text-sm text-slate-700 dark:text-gray-100 placeholder-slate-400 shadow-sm focus:ring-2 focus:ring-[#6b7aa8]/40 focus:border-transparent';
    $labelClass = 'block text-[11px] font-medium text-slate-500 dark:text-gray-400 mb-1';
    $cardClass = 'bg-white/75 dark:bg-gray-800/70 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-lg rounded-3xl';
    $primaryBtn = 'px-5 py-2.5 rounded-full bg-gradient-to-r from-[#6b7aa8] to-[#3f4c75] text-white text-[13px] font-semibold shadow-lg shadow-[#4b5a85]/30 hover:brightness-110 transition';
    $eye = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
@endphp

<x-passenger-layout title="Ubicaditos · Perfil">
    <div class="grid gap-4 lg:grid-cols-[280px_1fr] items-stretch">

        <aside class="{{ $cardClass }} p-6 flex flex-col items-center text-center">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#7d8bb8] to-[#3f4c75] text-white text-2xl font-bold flex items-center justify-center shadow-lg ring-4 ring-white/70">
                {{ $initials }}
            </div>
            <div class="mt-4 text-[17px] font-bold text-[#2f3a5f] dark:text-gray-100">{{ $fullName }}</div>
            <div class="text-[12px] text-slate-500 dark:text-gray-400">{{ $user->email }}</div>

            <span class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-[11px] font-medium text-[#4b5a85] dark:text-gray-200">
                <span class="w-1.5 h-1.5 rounded-full bg-[#4b5a85]"></span>
                Pasajero
            </span>

            <div class="w-full border-t border-slate-200/70 dark:border-white/10 my-5"></div>

            <div class="w-full space-y-3 text-left text-[12px] text-slate-600 dark:text-gray-300">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                    {{ $favoritesCount }} {{ $favoritesCount === 1 ? 'parada favorita' : 'paradas favoritas' }}
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                    5 calificaciones emitidas
                </div>
            </div>

            <div class="mt-auto pt-10 w-full">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-full border border-slate-300 dark:border-gray-600 bg-white/60 dark:bg-gray-700/60 text-[13px] font-medium text-[#4b5a85] dark:text-gray-200 hover:bg-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="space-y-4">

            <section class="{{ $cardClass }} p-6">
                <h2 class="text-[15px] font-semibold text-[#2f3a5f] dark:text-gray-100">Información del perfil</h2>
                <p class="text-[11px] text-slate-500 dark:text-gray-400 mb-4">Actualiza tu nombre, apellidos y correo electrónico.</p>

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="first_name" class="{{ $labelClass }}">Nombre</label>
                            <input id="first_name" name="{{ $firstField }}" type="text" value="{{ old($firstField, $firstName) }}" required autocomplete="given-name" class="{{ $inputClass }}">
                            @error($firstField)<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="last_name" class="{{ $labelClass }}">Apellidos</label>
                            <input id="last_name" @if($lastField) name="{{ $lastField }}" @endif type="text" value="{{ $lastField ? old($lastField, $lastName) : '' }}" placeholder="Apellidos" autocomplete="family-name" class="{{ $inputClass }}">
                            @if($lastField) @error($lastField)<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="email" class="{{ $labelClass }}">Correo</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="{{ $inputClass }}">
                            @error('email')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <button type="submit" class="{{ $primaryBtn }}">Guardar cambios</button>
                        @if(session('status') === 'profile-updated')
                            <span class="text-xs text-emerald-600">Guardado.</span>
                        @endif
                    </div>
                </form>
            </section>

            <section class="{{ $cardClass }} p-6">
                <h2 class="text-[15px] font-semibold text-[#2f3a5f] dark:text-gray-100">Actualizar contraseña</h2>
                <p class="text-[11px] text-slate-500 dark:text-gray-400 mb-4">Usa una contraseña larga y segura.</p>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="current_password" class="{{ $labelClass }}">Contraseña actual</label>
                            <div class="relative">
                                <input id="current_password" name="current_password" type="password" autocomplete="current-password" placeholder="••••••••" class="{{ $inputClass }} pr-10">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">{!! $eye !!}</span>
                            </div>
                            @if($errors->updatePassword->has('current_password'))<p class="mt-1 text-xs text-rose-500">{{ $errors->updatePassword->first('current_password') }}</p>@endif
                        </div>
                        <div>
                            <label for="password" class="{{ $labelClass }}">Nueva contraseña</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Nueva contraseña" class="{{ $inputClass }} pr-10">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">{!! $eye !!}</span>
                            </div>
                            @if($errors->updatePassword->has('password'))<p class="mt-1 text-xs text-rose-500">{{ $errors->updatePassword->first('password') }}</p>@endif
                        </div>
                        <div>
                            <label for="password_confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Repite la contraseña" class="{{ $inputClass }} pr-10">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">{!! $eye !!}</span>
                            </div>
                            @if($errors->updatePassword->has('password_confirmation'))<p class="mt-1 text-xs text-rose-500">{{ $errors->updatePassword->first('password_confirmation') }}</p>@endif
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <button type="submit" class="{{ $primaryBtn }}">Actualizar contraseña</button>
                        @if(session('status') === 'password-updated')
                            <span class="text-xs text-emerald-600">Guardado.</span>
                        @endif
                    </div>
                </form>
            </section>

            <section class="rounded-3xl bg-gradient-to-r from-rose-50/90 to-white/60 dark:from-rose-900/20 dark:to-gray-800/60 backdrop-blur-xl border border-rose-100 dark:border-rose-900/40 shadow-lg p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-[14px] font-semibold text-rose-700 dark:text-rose-300">Eliminar cuenta</h2>
                    <p class="text-[11px] text-slate-500 dark:text-gray-400">Esta acción es permanente y borrará tus favoritos y calificaciones.</p>
                </div>
                <button type="button" id="open-delete" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-rose-400 hover:bg-rose-500 text-white text-[13px] font-semibold shadow-lg shadow-rose-300/40 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Eliminar cuenta
                </button>
            </section>
        </div>
    </div>

    <dialog id="delete-dialog" class="w-full max-w-md rounded-3xl p-6 bg-white dark:bg-gray-800 shadow-2xl backdrop:bg-slate-900/40 backdrop:backdrop-blur-sm">
        <form method="POST" action="{{ route('profile.destroy') }}">
            @csrf
            @method('DELETE')

            <h2 class="text-[16px] font-semibold text-[#2f3a5f] dark:text-gray-100">¿Seguro que quieres eliminar tu cuenta?</h2>
            <p class="mt-1 text-[12px] text-slate-500 dark:text-gray-400">Se borrarán tus favoritos y calificaciones de forma permanente. Escribe tu contraseña para confirmar.</p>

            <div class="mt-4">
                <label for="delete_password" class="{{ $labelClass }}">Contraseña</label>
                <input id="delete_password" name="password" type="password" placeholder="Contraseña" class="{{ $inputClass }}">
                @if($errors->userDeletion->has('password'))<p class="mt-1 text-xs text-rose-500">{{ $errors->userDeletion->first('password') }}</p>@endif
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="close-delete" class="px-5 py-2.5 rounded-full border border-slate-300 dark:border-gray-600 text-[13px] font-medium text-[#4b5a85] dark:text-gray-200">Cancelar</button>
                <button type="submit" class="px-5 py-2.5 rounded-full bg-rose-500 hover:bg-rose-600 text-white text-[13px] font-semibold">Eliminar cuenta</button>
            </div>
        </form>
    </dialog>

    <script>
        const deleteDialog = document.getElementById('delete-dialog');
        document.getElementById('open-delete').addEventListener('click', () => deleteDialog.showModal());
        document.getElementById('close-delete').addEventListener('click', () => deleteDialog.close());
        @if($errors->userDeletion->isNotEmpty())
            deleteDialog.showModal();
        @endif
    </script>
</x-passenger-layout>