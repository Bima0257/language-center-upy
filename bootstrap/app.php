<?php

use Illuminate\Auth\AuthenticationException;
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
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'verified.user' => \App\Http\Middleware\EnsureVerified::class,
            'exam.session.active' => \App\Http\Middleware\EnsureExamSessionActive::class,
            'exam.ready' => \App\Http\Middleware\EnsureExamReady::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->cookie('_logged') && $request->isMethod('GET')) {
                return redirect()->route('login')
                    ->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.')
                    ->withCookie(cookie()->forget('_logged'));
            }

            return redirect()->guest($e->redirectTo($request) ?? route('login'));
        });
    })->create();
