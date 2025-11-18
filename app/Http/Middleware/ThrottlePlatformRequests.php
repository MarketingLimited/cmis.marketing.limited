<?php

namespace App\Http\Middleware;

use App\Services\RateLimiter\PlatformRateLimiter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to throttle platform API requests
 *
 * Usage in routes:
 * Route::middleware(['throttle.platform:meta'])->group(...)
 */
class ThrottlePlatformRequests
{
    protected PlatformRateLimiter $rateLimiter;

    public function __construct(PlatformRateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $platform): Response
    {
        // Get identifier (org_id or integration_id from route)
        $identifier = $request->route('orgId')
            ?? $request->route('integration_id')
            ?? $request->user()?->org_id
            ?? 'global';

        // Check rate limit
        if (!$this->rateLimiter->attempt($platform, $identifier)) {
            $info = $this->rateLimiter->remaining($platform, $identifier);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "Too many requests to {$platform} API. Please try again later.",
                'retry_after' => $info['reset_at'] - now()->timestamp,
                'reset_at' => date('Y-m-d H:i:s', $info['reset_at']),
                'limit' => $info['limit'],
            ], 429)->header('Retry-After', $info['reset_at'] - now()->timestamp);
        }

        $response = $next($request);

        // Add rate limit headers to response
        $info = $this->rateLimiter->remaining($platform, $identifier);
        $response->headers->set('X-RateLimit-Limit', $info['limit']);
        $response->headers->set('X-RateLimit-Remaining', $info['remaining']);
        $response->headers->set('X-RateLimit-Reset', $info['reset_at']);

        return $response;
    }
}
