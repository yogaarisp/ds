<?php

// Suppress PHP deprecation warnings to prevent JSON corruption
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        // Pure API without CSRF protection
        $middleware->api();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
