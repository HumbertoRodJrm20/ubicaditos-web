<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Notifications\TemporaryPasswordNotification;
use App\Services\DriverDeactivationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(private readonly DriverDeactivationService $driverDeactivation) {}

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $validated['is_active'] = true;
        $temporaryPassword = null;

        if ($validated['role'] === 'driver') {
            $temporaryPassword = Str::password(16);
            $validated['password'] = Hash::make($temporaryPassword);
            $validated['must_change_password'] = true;
            $validated['approval_status'] = User::APPROVAL_APPROVED;
            $validated['email_verified_at'] = now();
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user = User::create($validated);

        if ($temporaryPassword !== null) {
            $user->notify(new TemporaryPasswordNotification($temporaryPassword));
        }

        return redirect()->route('admin.'.($validated['role'] === 'driver' ? 'drivers' : 'passengers'))
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.'.($user->role === 'driver' ? 'drivers' : 'passengers'))
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'No se puede eliminar un administrador');
        }

        if ($user->role === 'driver') {
            if (! $this->driverDeactivation->deactivate($user)) {
                return back()->with('error', 'No se puede desactivar al conductor mientras tenga un viaje activo.');
            }

            return back()->with('success', 'El conductor se desactivó y su historial se conservó.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado exitosamente');
    }
}
