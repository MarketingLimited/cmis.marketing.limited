<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;

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
    ->withSchedule(function (Schedule $schedule): void {
        // Import the Kernel's schedule method
        $kernel = app(\App\Console\Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->invoke($kernel, $schedule);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Use custom EncryptCookies middleware to exclude 'app_locale' from encryption
        $middleware->encryptCookies(except: [
            'app_locale', // Locale cookie must be readable as plain text
        ]);

        // Apply security headers globally to all requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Apply locale detection globally to all web requests
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Register middleware aliases
        $middleware->alias([
            // Multi-Tenancy & Organization Context (Phase 1 - Consolidated)
            'org.context' => \App\Http\Middleware\SetOrganizationContext::class,
            'resolve.active.org' => \App\Http\Middleware\ResolveActiveOrg::class, // NEW: Auto-resolve user's active org

            // Deprecated middleware (use 'org.context' instead)
            // These are kept for backward compatibility but will be removed in future
            'set.db.context' => \App\Http\Middleware\SetDatabaseContext::class,
            'set.rls.context' => \App\Http\Middleware\SetRLSContext::class,
            'set.org.context' => \App\Http\Middleware\SetOrgContextMiddleware::class,

            // Access Control & Permissions
            'validate.org.access' => \App\Http\Middleware\ValidateOrgAccess::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,

            // Security & Authentication
            'refresh.tokens' => \App\Http\Middleware\RefreshExpiredTokens::class,
            'verify.webhook' => \App\Http\Middleware\VerifyWebhookSignature::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,

            // Rate Limiting & Throttling
            'throttle.ai' => \App\Http\Middleware\ThrottleAI::class,
            'throttle.platform' => \App\Http\Middleware\ThrottlePlatformRequests::class,
            'ai.rate.limit' => \App\Http\Middleware\AiRateLimitMiddleware::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimiting::class,

            // Feature Toggles & Quotas
            'feature.platform' => \App\Http\Middleware\CheckPlatformFeatureEnabled::class,
            'check.ai.quota' => \App\Http\Middleware\CheckAiQuotaMiddleware::class,

            // Apps Marketplace
            'app.enabled' => \App\Http\Middleware\CheckAppEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
