<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * API Rate Limiting Middleware
 *
 * Implements tiered rate limiting based on user authentication and API tier
 */
class ApiRateLimiting
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): SymfonyResponse
    {
        $key = $this->resolveRequestSignature($request, $tier);
        $limit = $this->getLimit($tier);
        $decay = $this->getDecay($tier);

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            return $this->buildRateLimitResponse($key, $limit);
        }

        $this->limiter->hit($key, $decay);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key, $limit);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, string $tier): string
    {
        // Use user ID if authenticated, otherwise IP address
        $identifier = auth()->check()
            ? 'user:' . auth()->id()
            : 'ip:' . $request->ip();

        return "rate_limit:{$tier}:{$identifier}";
    }

    /**
     * Get rate limit based on tier
     */
    protected function getLimit(string $tier): int
    {
        return match($tier) {
            'authenticated' => 1000,    // 1000 requests
            'guest' => 100,             // 100 requests
            'api' => 5000,              // 5000 requests (with API key)
            'webhook' => 10000,         // 10000 requests
            default => 200,             // Default limit
        };
    }

    /**
     * Get decay time in seconds
     */
    protected function getDecay(string $tier): int
    {
        return match($tier) {
            'authenticated' => 60,      // Per minute
            'guest' => 60,              // Per minute
            'api' => 60,                // Per minute
            'webhook' => 60,            // Per minute
            default => 60,              // Per minute
        };
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(string $key, int $limit): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => "Too many requests. Please try again in {$retryAfter} seconds.",
            'retry_after' => $retryAfter,
            'limit' => $limit,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(SymfonyResponse $response, string $key, int $limit): SymfonyResponse
    {
        $remaining = $this->limiter->remaining($key, $limit);
        $retryAfter = $this->limiter->availableIn($key);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));

        if ($remaining === 0) {
            $response->headers->set('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
        }

        return $response;
    }
}
