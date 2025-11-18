<?php

namespace App\Services\RateLimiter;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Platform API Rate Limiter
 *
 * Prevents exceeding platform API rate limits by tracking and throttling requests
 *
 * Supported Platforms:
 * - Meta (Facebook/Instagram): 200 calls/hour per user
 * - TikTok: 100 calls/hour
 * - LinkedIn: 100 calls/day per member
 * - Twitter: 300 calls/15-min per app
 * - Google Ads: 15000 operations/day
 */
class PlatformRateLimiter
{
    /**
     * Platform rate limit configurations
     * Format: [calls, period_in_seconds, burst_limit]
     */
    const RATE_LIMITS = [
        'meta' => [
            'calls' => 200,
            'period' => 3600, // 1 hour
            'burst' => 50, // Allow burst of 50 calls
        ],
        'facebook' => [
            'calls' => 200,
            'period' => 3600,
            'burst' => 50,
        ],
        'instagram' => [
            'calls' => 200,
            'period' => 3600,
            'burst' => 50,
        ],
        'tiktok' => [
            'calls' => 100,
            'period' => 3600,
            'burst' => 25,
        ],
        'linkedin' => [
            'calls' => 100,
            'period' => 86400, // 24 hours
            'burst' => 20,
        ],
        'twitter' => [
            'calls' => 300,
            'period' => 900, // 15 minutes
            'burst' => 100,
        ],
        'google' => [
            'calls' => 15000,
            'period' => 86400, // 24 hours
            'burst' => 500,
        ],
        'snapchat' => [
            'calls' => 100,
            'period' => 3600,
            'burst' => 25,
        ],
    ];

    /**
     * Check if a request is allowed for a platform
     *
     * @param string $platform Platform name (meta, tiktok, etc.)
     * @param string $identifier Unique identifier (integration_id or org_id)
     * @return bool True if request is allowed
     */
    public function attempt(string $platform, string $identifier): bool
    {
        if (!isset(self::RATE_LIMITS[$platform])) {
            Log::warning("No rate limit configured for platform: {$platform}");
            return true; // Allow if no limit configured
        }

        $config = self::RATE_LIMITS[$platform];
        $cacheKey = $this->getCacheKey($platform, $identifier);

        // Get current request count
        $requests = Cache::get($cacheKey, [
            'count' => 0,
            'reset_at' => now()->addSeconds($config['period'])->timestamp,
        ]);

        // Check if rate limit period has expired
        if ($requests['reset_at'] <= now()->timestamp) {
            // Reset the counter
            $requests = [
                'count' => 0,
                'reset_at' => now()->addSeconds($config['period'])->timestamp,
            ];
        }

        // Check if limit exceeded
        if ($requests['count'] >= $config['calls']) {
            Log::warning("Rate limit exceeded for {$platform}", [
                'identifier' => $identifier,
                'count' => $requests['count'],
                'limit' => $config['calls'],
                'reset_at' => date('Y-m-d H:i:s', $requests['reset_at']),
            ]);
            return false;
        }

        // Increment counter
        $requests['count']++;

        // Save to cache with TTL = period
        Cache::put($cacheKey, $requests, $config['period']);

        Log::debug("Rate limit check passed for {$platform}", [
            'identifier' => $identifier,
            'count' => $requests['count'],
            'limit' => $config['calls'],
            'remaining' => $config['calls'] - $requests['count'],
        ]);

        return true;
    }

    /**
     * Get remaining requests for a platform
     *
     * @param string $platform
     * @param string $identifier
     * @return array ['remaining' => int, 'reset_at' => timestamp, 'limit' => int]
     */
    public function remaining(string $platform, string $identifier): array
    {
        if (!isset(self::RATE_LIMITS[$platform])) {
            return [
                'remaining' => PHP_INT_MAX,
                'reset_at' => null,
                'limit' => PHP_INT_MAX,
            ];
        }

        $config = self::RATE_LIMITS[$platform];
        $cacheKey = $this->getCacheKey($platform, $identifier);

        $requests = Cache::get($cacheKey, [
            'count' => 0,
            'reset_at' => now()->addSeconds($config['period'])->timestamp,
        ]);

        // Check if period expired
        if ($requests['reset_at'] <= now()->timestamp) {
            return [
                'remaining' => $config['calls'],
                'reset_at' => now()->addSeconds($config['period'])->timestamp,
                'limit' => $config['calls'],
            ];
        }

        return [
            'remaining' => max(0, $config['calls'] - $requests['count']),
            'reset_at' => $requests['reset_at'],
            'limit' => $config['calls'],
            'count' => $requests['count'],
        ];
    }

    /**
     * Wait until rate limit allows next request (blocking)
     *
     * @param string $platform
     * @param string $identifier
     * @param int $maxWaitSeconds Maximum seconds to wait (default: 60)
     * @return bool True if can proceed, false if max wait exceeded
     */
    public function waitUntilReady(string $platform, string $identifier, int $maxWaitSeconds = 60): bool
    {
        $startTime = time();

        while (!$this->attempt($platform, $identifier)) {
            $elapsed = time() - $startTime;

            if ($elapsed >= $maxWaitSeconds) {
                Log::warning("Rate limit wait timeout for {$platform}", [
                    'identifier' => $identifier,
                    'max_wait' => $maxWaitSeconds,
                ]);
                return false;
            }

            $info = $this->remaining($platform, $identifier);
            $waitTime = min(5, max(1, ($info['reset_at'] - now()->timestamp) / 10)); // Wait 1-5 seconds

            Log::debug("Waiting for rate limit: {$platform}", [
                'identifier' => $identifier,
                'wait_seconds' => $waitTime,
                'remaining' => $info['remaining'],
            ]);

            sleep((int) $waitTime);
        }

        return true;
    }

    /**
     * Reset rate limit for a platform (admin/testing use)
     *
     * @param string $platform
     * @param string $identifier
     * @return void
     */
    public function reset(string $platform, string $identifier): void
    {
        $cacheKey = $this->getCacheKey($platform, $identifier);
        Cache::forget($cacheKey);

        Log::info("Rate limit reset for {$platform}", [
            'identifier' => $identifier,
        ]);
    }

    /**
     * Get cache key for rate limiting
     */
    protected function getCacheKey(string $platform, string $identifier): string
    {
        return "rate_limit:{$platform}:{$identifier}";
    }

    /**
     * Get all rate limits (for monitoring dashboard)
     */
    public static function getAllLimits(): array
    {
        return self::RATE_LIMITS;
    }
}
