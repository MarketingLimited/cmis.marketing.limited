<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * AI Rate Limit Middleware
 *
 * Enforces rate limiting on AI operations to prevent abuse
 * and protect against DoS attacks.
 *
 * Limits:
 * - Per User: Based on tier (free/pro/enterprise)
 * - Per IP: Fallback for unauthenticated requests
 * - Per Organization: Aggregate limits
 *
 * This is an additional layer on top of quota system.
 */
class AiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $aiService  'gpt'|'embeddings'|'image_gen'
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $aiService = 'gpt'): Response
    {
        // Get rate limit configuration
        $limits = config('ai-quotas.rate_limits.' . $aiService, [
            'per_minute' => 10,
            'per_hour' => 100,
        ]);

        // Generate rate limit key
        $key = $this->getRateLimitKey($request, $aiService);

        // Check per-minute limit
        $perMinuteLimit = $this->getUserTierLimit($request, $limits['per_minute']);
        $executed = RateLimiter::attempt(
            $key . ':per_minute',
            $perMinuteLimit,
            function () {},
            60 // 1 minute decay
        );

        if (!$executed) {
            Log::warning('AI rate limit exceeded (per-minute)', [
                'key' => $key,
                'service' => $aiService,
                'limit' => $perMinuteLimit,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return $this->rateLimitResponse($perMinuteLimit, 'minute');
        }

        // Check per-hour limit
        $perHourLimit = $this->getUserTierLimit($request, $limits['per_hour']);
        $executed = RateLimiter::attempt(
            $key . ':per_hour',
            $perHourLimit,
            function () {},
            3600 // 1 hour decay
        );

        if (!$executed) {
            Log::warning('AI rate limit exceeded (per-hour)', [
                'key' => $key,
                'service' => $aiService,
                'limit' => $perHourLimit,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return $this->rateLimitResponse($perHourLimit, 'hour');
        }

        // Add rate limit headers to response
        $response = $next($request);

        $this->addRateLimitHeaders($response, $key, $perMinuteLimit, $perHourLimit);

        return $response;
    }

    /**
     * Generate rate limit key based on user/IP
     *
     * @param Request $request
     * @param string $aiService
     * @return string
     */
    protected function getRateLimitKey(Request $request, string $aiService): string
    {
        // Prefer user-based rate limiting
        if (auth()->check()) {
            return 'ai_rate_limit:' . $aiService . ':user:' . auth()->id();
        }

        // Fallback to IP-based for unauthenticated requests
        return 'ai_rate_limit:' . $aiService . ':ip:' . $request->ip();
    }

    /**
     * Adjust rate limit based on user tier
     *
     * @param Request $request
     * @param int $baseLimit
     * @return int
     */
    protected function getUserTierLimit(Request $request, int $baseLimit): int
    {
        if (!auth()->check()) {
            return $baseLimit; // Use base limit for unauthenticated
        }

        // Get user's organization tier
        $user = auth()->user();
        $tier = $user->organization->subscription_tier ?? 'free';

        // Adjust limits based on tier
        $multipliers = [
            'free' => 1.0,
            'pro' => 2.0,      // 2x the base limit
            'enterprise' => 5.0, // 5x the base limit
        ];

        $multiplier = $multipliers[$tier] ?? 1.0;

        return (int) ceil($baseLimit * $multiplier);
    }

    /**
     * Generate rate limit exceeded response
     *
     * @param int $limit
     * @param string $period
     * @return Response
     */
    protected function rateLimitResponse(int $limit, string $period): Response
    {
        $message = "Too many AI requests. Limit: {$limit} per {$period}. Please try again later.";

        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => $message,
                'limit' => $limit,
                'period' => $period,
                'retry_after' => $period === 'minute' ? 60 : 3600,
            ], 429);
        }

        return response()->view('errors.rate-limit', [
            'message' => $message,
            'limit' => $limit,
            'period' => $period,
        ], 429);
    }

    /**
     * Add rate limit headers to response
     *
     * @param Response $response
     * @param string $key
     * @param int $perMinuteLimit
     * @param int $perHourLimit
     * @return void
     */
    protected function addRateLimitHeaders(
        Response $response,
        string $key,
        int $perMinuteLimit,
        int $perHourLimit
    ): void {
        // Get remaining attempts
        $minuteRemaining = RateLimiter::remaining($key . ':per_minute', $perMinuteLimit);
        $hourRemaining = RateLimiter::remaining($key . ':per_hour', $perHourLimit);

        // Add standard rate limit headers
        $response->headers->set('X-RateLimit-Limit-Minute', $perMinuteLimit);
        $response->headers->set('X-RateLimit-Remaining-Minute', max(0, $minuteRemaining));

        $response->headers->set('X-RateLimit-Limit-Hour', $perHourLimit);
        $response->headers->set('X-RateLimit-Remaining-Hour', max(0, $hourRemaining));

        // Add retry-after if approaching limit
        if ($minuteRemaining === 0) {
            $response->headers->set('Retry-After', 60);
        }
    }
}
