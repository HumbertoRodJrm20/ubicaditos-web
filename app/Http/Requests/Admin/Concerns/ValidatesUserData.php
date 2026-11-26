<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\User;

/**
 * Reglas compartidas por el alta y la edición administrativa de usuarios.
 */
trait ValidatesUserData
{
    /**
     * @return array<string, mixed>
     */
    protected function userRules(?User $user = null, bool $isStore = false): array
    {
        $emailRule = $user === null
            ? 'required|email|unique:users,email'
            : 'required|email|unique:users,email,'.$user->id;

        $passwordRules = $isStore
            ? [
                'nullable',
                'required_unless:role,driver',
                'min:8',
                'regex:/^.*(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[@$!%*#?&\-_+=\[\]{}|:;,.<>?~`^()]).+$/',
            ]
            : [
                'nullable',
                'min:8',
                'regex:/^.*(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[@$!%*#?&\-_+=\[\]{}|:;,.<>?~`^()]).+$/',
            ];

        $rules = [
            'first_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'last_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'maternal_last_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'email' => $emailRule,
            'password' => $passwordRules,
            'role' => 'required|in:admin,driver,passenger',
            'work_shift' => 'nullable|required_if:role,driver|in:morning,afternoon',
            'phone' => 'required|string|min:10|max:10|regex:/^[0-9]+$/',
        ];

        if (! $isStore) {
            $rules['is_active'] = 'boolean';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function userMessages(): array
    {
        return [
            'first_name.max' => 'El nombre no puede tener más de 35 caracteres',
            'first_name.regex' => 'El nombre solo puede contener letras',
            'last_name.max' => 'El apellido paterno no puede tener más de 35 caracteres',
            'last_name.regex' => 'El apellido paterno solo puede contener letras',
            'maternal_last_name.required' => 'El apellido materno es obligatorio',
            'maternal_last_name.max' => 'El apellido materno no puede tener más de 35 caracteres',
            'maternal_last_name.regex' => 'El apellido materno solo puede contener letras',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.regex' => 'La contraseña debe contener al menos una letra, un número y un carácter especial',
            'work_shift.required_if' => 'El horario laboral es obligatorio para conductores',
            'work_shift.in' => 'Debe seleccionar un horario laboral válido',
            'phone.required' => 'El teléfono es obligatorio',
            'phone.min' => 'El teléfono debe tener exactamente 10 dígitos',
            'phone.max' => 'El teléfono debe tener exactamente 10 dígitos',
            'phone.regex' => 'El teléfono solo puede contener números',
        ];
    }
}
