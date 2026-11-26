<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica el idioma persistido del usuario al chrome de la interfaz.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && in_array($user->locale, User::LOCALES, true)) {
            App::setLocale($user->locale);
        }

        return $next($request);
    }
}
