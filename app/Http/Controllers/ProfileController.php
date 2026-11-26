<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsUpdateRequest;
use App\Services\DriverDeactivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'favoriteStops' => $user->favoriteStops()->with('routes')->get(),
            'favoriteRoutes' => $user->favoriteRoutes()->withCount('likes')->get(),
            'ratingsGivenCount' => $user->ratingsGiven()->count(),
            'likesGivenCount' => $user->likes()->count(),
            'incidentsCount' => $user->incidents()->count(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, DriverDeactivationService $driverDeactivation): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $isDriver = $user->role === 'driver';

        if ($isDriver) {
            if (! $driverDeactivation->deactivate($user)) {
                return Redirect::route('profile.edit')->withErrors([
                    'password' => 'No puedes desactivar tu cuenta mientras tengas un viaje activo.',
                ], 'userDeletion');
            }
        }

        Auth::logout();

        if (! $isDriver) {
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
