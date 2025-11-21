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

            // Feature Toggle Routes (API & Admin)
            Route::middleware(['web'])
                ->group(base_path('routes/features.php'));

            // AI Quota Management Routes (Phase 1B - 2025-11-21)
            Route::middleware(['api'])
                ->prefix('api')
                ->group(base_path('routes/api-ai-quota.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply security headers globally to all requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Register middleware aliases
        $middleware->alias([
            'set.db.context' => \App\Http\Middleware\SetDatabaseContext::class,
            'validate.org.access' => \App\Http\Middleware\ValidateOrgAccess::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'refresh.tokens' => \App\Http\Middleware\RefreshExpiredTokens::class,
            'verify.webhook' => \App\Http\Middleware\VerifyWebhookSignature::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'throttle.ai' => \App\Http\Middleware\ThrottleAI::class,
            'throttle.platform' => \App\Http\Middleware\ThrottlePlatformRequests::class, // NEW: Week 2
            'feature.platform' => \App\Http\Middleware\CheckPlatformFeatureEnabled::class, // NEW: Feature Toggles
            'admin' => \App\Http\Middleware\AdminOnly::class, // NEW: Admin access control
            // Phase 1B: AI Quota & Rate Limiting (2025-11-21)
            'ai.rate.limit' => \App\Http\Middleware\AiRateLimitMiddleware::class,
            'check.ai.quota' => \App\Http\Middleware\CheckAiQuotaMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
