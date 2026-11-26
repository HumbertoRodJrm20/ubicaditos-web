<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Cierre de sesión seguro: antes de destruir la sesión se revocan los
        // enlaces temporales de ubicación que el usuario tuviera activos.
        $user = $request->user();

        if ($user !== null) {
            DB::transaction(function () use ($user): void {
                if ($user->isDriver()) {
                    $user->trips()
                        ->where('status', 'in_progress')
                        ->get()
                        ->each(function ($trip): void {
                            $trip->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            if ($trip->bus_id !== null) {
                                $trip->bus()->update([
                                    'driver_id' => null,
                                    'status' => 'inactive',
                                ]);
                            }
                        });
                }

                $user->locationShares()
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->update(['revoked_at' => now()]);
            });
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
