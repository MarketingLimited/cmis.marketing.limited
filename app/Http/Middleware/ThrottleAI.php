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
        $maxAttempts = $this->getMaxAttemptsForUser($request);
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

    /**
     * Get maximum AI requests allowed per minute based on user's subscription plan.
     *
     * Rate limits by plan:
     * - Starter: 10 requests/minute
     * - Professional: 30 requests/minute
     * - Enterprise: 100 requests/minute
     * - Unauthenticated: 5 requests/minute
     */
    protected function getMaxAttemptsForUser(Request $request): int
    {
        // Check for custom override in config
        $configLimit = config('services.ai.rate_limit');
        if ($configLimit !== null && $configLimit > 0) {
            // Allow config override for testing or special cases
            return (int) $configLimit;
        }

        // Get user's organization subscription plan
        $user = $request->user();

        if (!$user || !$user->organization) {
            // Unauthenticated or no organization - use minimal limit
            return 5;
        }

        $plan = $user->organization->subscription_plan ?? 'starter';

        // Return rate limit based on plan
        return match (strtolower($plan)) {
            'professional' => 30,
            'enterprise' => 100,
            'starter' => 10,
            default => 10, // Default to starter limits
        };
    }
}
