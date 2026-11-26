<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Aplica la verificación de correo sólo cuando está habilitada por configuración.
 */
class EnsureEmailIsVerifiedIfEnabled extends EnsureEmailIsVerified
{
    /**
     * @param  Request  $request
     * @param  string|null  $redirectToRoute
     * @return Response|RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! config('auth.email_verification_enabled')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
