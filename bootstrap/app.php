<?php

use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\EnsureSubscriptionIsActive;
use App\Http\Middleware\EnsureUserIsHost;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Http\Middleware\KeepFlashAcrossLayouts;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            KeepFlashAcrossLayouts::class,
        ]);

        $middleware->alias([
            'super-admin' => EnsureUserIsSuperAdmin::class,
            'host' => EnsureUserIsHost::class,
            'profile-complete' => EnsureProfileIsComplete::class,
            'subscribed' => EnsureSubscriptionIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
