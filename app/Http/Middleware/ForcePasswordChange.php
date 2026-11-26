<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a cambiar la contraseña temporal antes de usar el resto de la app.
 */
class ForcePasswordChange
{
    /** @var list<string> */
    private const ALLOWED_ROUTES = [
        'logout',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'password.update',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->requiresPasswordChange()
            && ! in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Debes actualizar tu contraseña antes de continuar.');
        }

        return $next($request);
    }
}
