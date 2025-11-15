<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Vector Embeddings v2.0 API Routes
            Route::middleware(['api'])
                ->prefix('api')
                ->group(base_path('routes/vector-embeddings-v2.php'));

            // Vector Embeddings Web Routes
            Route::middleware(['web'])
                ->group(base_path('routes/vector-embeddings-web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases
        $middleware->alias([
            'set.db.context' => \App\Http\Middleware\SetDatabaseContext::class,
            'validate.org.access' => \App\Http\Middleware\ValidateOrgAccess::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
