<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'last_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'maternal_last_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:8',
                'regex:/^.*(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[@$!%*#?&\-_+=\[\]{}|:;,.<>?~`^()]).+$/'
            ],
            'role' => 'required|in:admin,driver,passenger',
            'work_shift' => 'nullable|required_if:role,driver|in:morning,afternoon',
            'phone' => 'required|string|min:10|max:10|regex:/^[0-9]+$/',
        ], [
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
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        User::create($validated);

        return redirect()->route('admin.' . ($validated['role'] === 'driver' ? 'drivers' : 'passengers'))
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'last_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'maternal_last_name' => 'required|string|max:35|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => [
                'nullable',
                'min:8',
                'regex:/^.*(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[@$!%*#?&\-_+=\[\]{}|:;,.<>?~`^()]).+$/'
            ],
            'role' => 'required|in:admin,driver,passenger',
            'work_shift' => 'nullable|required_if:role,driver|in:morning,afternoon',
            'phone' => 'required|string|min:10|max:10|regex:/^[0-9]+$/',
            'is_active' => 'boolean',
        ], [
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
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.' . ($user->role === 'driver' ? 'drivers' : 'passengers'))
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'No se puede eliminar un administrador');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado exitosamente');
    }
}
