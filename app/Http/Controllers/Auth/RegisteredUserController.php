<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:35', 'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/'],
            'last_name' => ['required', 'string', 'max:35', 'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/'],
            'maternal_last_name' => ['required', 'string', 'max:35', 'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/'],
            'phone' => ['required', 'string', 'size:10', 'regex:/^\d{10}$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'],
        ], [
            'first_name.required' => 'El nombre es obligatorio',
            'first_name.regex' => 'El nombre solo puede contener letras',
            'first_name.max' => 'El nombre no puede tener más de 35 caracteres',
            'last_name.required' => 'El apellido paterno es obligatorio',
            'last_name.regex' => 'El apellido paterno solo puede contener letras',
            'last_name.max' => 'El apellido paterno no puede tener más de 35 caracteres',
            'maternal_last_name.required' => 'El apellido materno es obligatorio',
            'maternal_last_name.regex' => 'El apellido materno solo puede contener letras',
            'maternal_last_name.max' => 'El apellido materno no puede tener más de 35 caracteres',
            'phone.required' => 'El teléfono es obligatorio',
            'phone.size' => 'El teléfono debe tener exactamente 10 dígitos',
            'phone.regex' => 'El teléfono solo puede contener números',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.regex' => 'La contraseña debe contener letras, números y caracteres especiales',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'maternal_last_name' => $request->maternal_last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'passenger', // Usuarios registrados son pasajeros por defecto
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('passenger.menu');
    }

    /**
     * Check if email already exists.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (!$email) {
            return response()->json(['available' => true]);
        }

        $exists = User::where('email', $email)->exists();

        return response()->json(['available' => !$exists]);
    }
}
