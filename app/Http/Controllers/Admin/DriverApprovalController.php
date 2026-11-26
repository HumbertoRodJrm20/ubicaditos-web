<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

/**
 * Aprobación de licencias y ciclo de contraseña de conductores.
 */
class DriverApprovalController extends Controller
{
    public function approve(User $driver): RedirectResponse
    {
        $this->ensureDriver($driver);
        $driver->update(['approval_status' => User::APPROVAL_APPROVED]);

        return redirect()->route('admin.drivers')
            ->with('success', 'El conductor fue aprobado y ya puede operar.');
    }

    public function reject(User $driver): RedirectResponse
    {
        $this->ensureDriver($driver);
        $driver->update(['approval_status' => User::APPROVAL_REJECTED]);

        return redirect()->route('admin.drivers')
            ->with('success', 'La solicitud del conductor fue rechazada.');
    }

    public function sendPasswordReset(User $driver): RedirectResponse
    {
        $this->ensureDriver($driver);
        Password::sendResetLink(['email' => $driver->email]);

        return redirect()->route('admin.drivers')
            ->with('success', 'Se envió el correo de restablecimiento al conductor.');
    }

    private function ensureDriver(User $driver): void
    {
        abort_unless($driver->role === 'driver', 404);
    }
}
