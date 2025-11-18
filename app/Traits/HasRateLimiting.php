<?php

namespace App\Traits;

use App\Services\RateLimiter\PlatformRateLimiter;
use Illuminate\Support\Facades\Log;

/**
 * Trait for services that need platform rate limiting
 *
 * Usage in Connector/Service classes:
 * class MetaConnector {
 *     use HasRateLimiting;
 *
 *     protected string $platform = 'meta';
 *
 *     public function makeApiCall() {
 *         if (!$this->checkRateLimit($this->integration->integration_id)) {
 *             throw new RateLimitException();
 *         }
 *         // Make API call
 *     }
 * }
 */
trait HasRateLimiting
{
    /**
     * Rate limiter instance
     */
    protected ?PlatformRateLimiter $rateLimiter = null;

    /**
     * Platform name for rate limiting
     */
    protected string $platform;

    /**
     * Get or create rate limiter instance
     */
    protected function getRateLimiter(): PlatformRateLimiter
    {
        if (!$this->rateLimiter) {
            $this->rateLimiter = app(PlatformRateLimiter::class);
        }

        return $this->rateLimiter;
    }

    /**
     * Check if request is allowed by rate limiter
     *
     * @param string $identifier Integration ID or Org ID
     * @return bool True if request allowed
     */
    protected function checkRateLimit(string $identifier): bool
    {
        $platform = $this->platform ?? 'unknown';

        return $this->getRateLimiter()->attempt($platform, $identifier);
    }

    /**
     * Get remaining requests info
     *
     * @param string $identifier
     * @return array ['remaining' => int, 'reset_at' => timestamp, 'limit' => int]
     */
    protected function getRateLimitInfo(string $identifier): array
    {
        $platform = $this->platform ?? 'unknown';

        return $this->getRateLimiter()->remaining($platform, $identifier);
    }

    /**
     * Wait until rate limit allows next request (blocking)
     *
     * @param string $identifier
     * @param int $maxWaitSeconds
     * @return bool True if can proceed
     */
    protected function waitForRateLimit(string $identifier, int $maxWaitSeconds = 60): bool
    {
        $platform = $this->platform ?? 'unknown';

        return $this->getRateLimiter()->waitUntilReady($platform, $identifier, $maxWaitSeconds);
    }

    /**
     * Execute API call with rate limiting
     *
     * @param string $identifier Integration/Org ID
     * @param callable $callback API call callback
     * @param bool $wait Whether to wait if rate limited
     * @return mixed Result from callback
     * @throws \Exception If rate limited and not waiting
     */
    protected function executeWithRateLimit(string $identifier, callable $callback, bool $wait = false)
    {
        $platform = $this->platform ?? 'unknown';

        // Check rate limit
        if (!$this->checkRateLimit($identifier)) {
            if ($wait) {
                // Wait until ready
                if (!$this->waitForRateLimit($identifier)) {
                    throw new \Exception("Rate limit wait timeout for platform: {$platform}");
                }
            } else {
                $info = $this->getRateLimitInfo($identifier);
                throw new \Exception(
                    "Rate limit exceeded for platform: {$platform}. " .
                    "Reset at: " . date('Y-m-d H:i:s', $info['reset_at'])
                );
            }
        }

        // Execute the callback
        try {
            return $callback();
        } catch (\Exception $e) {
            Log::error("API call failed for platform: {$platform}", [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
