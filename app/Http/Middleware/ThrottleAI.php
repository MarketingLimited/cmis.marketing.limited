<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAI
{
    /**
     * The rate limiter instance.
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = config('services.ai.rate_limit', 10); // 10 requests per minute
        $decayMinutes = 1; // 1 minute window

        // Check if too many attempts
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Too many AI requests. Please try again later.',
                'retry_after' => $retryAfter,
                'retry_after_human' => gmdate('i:s', $retryAfter),
            ], Response::HTTP_TOO_MANY_REQUESTS)
                ->withHeaders([
                    'X-RateLimit-Limit' => $maxAttempts,
                    'X-RateLimit-Remaining' => 0,
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
                ]);
        }

        // Increment the counter
        $this->limiter->hit($key, $decayMinutes * 60);

        // Calculate remaining attempts
        $remaining = $this->limiter->remaining($key, $maxAttempts);

        // Process the request
        $response = $next($request);

        // Add rate limit headers to response
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remaining - 1),
            'X-RateLimit-Reset' => now()->addMinutes($decayMinutes)->timestamp,
        ]);
    }

    /**
     * Resolve the request signature for rate limiting.
     *
     * Uses user ID if authenticated, otherwise uses IP address.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return 'ai-throttle:user:' . $user->getKey();
        }

        return 'ai-throttle:ip:' . $request->ip();
    }
}
