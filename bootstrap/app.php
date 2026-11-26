<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\DriverMiddleware;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\PassengerMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'driver' => DriverMiddleware::class,
            'passenger' => PassengerMiddleware::class,
            'roles' => EnsureUserHasRole::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
            ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
