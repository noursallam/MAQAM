<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin*') ? '/admin/login' : '/login');
        $middleware->redirectUsersTo(fn (Request $request) => auth()->user()?->isAdmin() ? '/admin' : '/profile');
        $middleware->validateCsrfTokens(except: [
            'payment/kashier/webhook',
            'api/webhooks/kashier',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetWebLocale::class,
        ]);
        $middleware->throttleApi();
        $middleware->api(prepend: [
            \App\Http\Middleware\PrepareApiRequest::class,
        ]);
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureAccountActive::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.module' => \App\Http\Middleware\EnsureAdminModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/v1/*')) {
                return \App\Support\ApiExceptionRenderer::render($e);
            }
        });
    })->create();
