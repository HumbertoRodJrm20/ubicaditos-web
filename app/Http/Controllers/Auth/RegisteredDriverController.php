<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Registro público de conductores con licencia y aprobación posterior.
 */
class RegisteredDriverController extends Controller
{
    public function create(): View
    {
        return view('auth.register-driver');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:35', 'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/'],
            'last_name' => ['required', 'string', 'max:35', 'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/'],
            'maternal_last_name' => ['required', 'string', 'max:35', 'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/'],
            'phone' => ['required', 'string', 'size:10', 'regex:/^\d{10}$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'license_number' => ['required', 'string', 'max:50', 'unique:users,license_number'],
            'license_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'],
        ], [
            'license_number.unique' => 'Ese número de licencia ya está registrado',
            'license_photo.image' => 'La foto de licencia debe ser una imagen',
            'license_photo.mimes' => 'La foto debe ser JPG o PNG',
            'license_photo.max' => 'La foto no puede superar los 2 MB',
            'password.regex' => 'La contraseña debe contener letras, números y caracteres especiales',
        ]);

        $photoPath = $request->file('license_photo')->store('license-photos', 'local');

        $driver = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'maternal_last_name' => $validated['maternal_last_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'driver',
            'approval_status' => User::APPROVAL_PENDING,
            'license_number' => $validated['license_number'],
            'license_photo_path' => $photoPath,
            'email_verified_at' => now(),
        ]);

        event(new Registered($driver));

        return redirect()->route('login')
            ->with('success', 'Tu solicitud fue recibida. Un administrador revisará tu licencia.');
    }
}
