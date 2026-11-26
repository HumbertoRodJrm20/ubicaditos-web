<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RNF-02: declara explícitamente qué roles pueden consumir un endpoint.
 */
class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  roles permitidos (p. ej. roles:admin,driver,passenger)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($roles === [] || $user === null || ! in_array($user->role, $roles, true)) {
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}
