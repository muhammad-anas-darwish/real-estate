<?php

use App\Exceptions\CustomHandler;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            $modulesPath = base_path('Modules');

            if (is_dir($modulesPath)) {
                /**
                 * Search pattern for nested submodules:
                 * Modules/{Main}/SubModules/{Sub}/Routes/api.php
                 */
                $pattern = $modulesPath.'/*/SubModules/*/Routes/api.php';

                foreach (glob($pattern) as $file) {
                    Route::middleware('api')
                        ->group($file);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // أو لمجموعة web إذا كنت تستخدمها
        $middleware->web(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(new CustomHandler);
    })->create();
