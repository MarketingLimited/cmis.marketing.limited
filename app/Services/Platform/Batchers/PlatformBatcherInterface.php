<?php

namespace App\Services\Platform\Batchers;

use Illuminate\Support\Collection;

/**
 * PlatformBatcherInterface
 *
 * Interface for platform-specific batch execution strategies.
 * Each platform implements this to optimize API calls using platform-specific features:
 * - Meta: Field Expansion + Batch API (up to 50 requests per call)
 * - Google: SearchStream API (unlimited data per call)
 * - TikTok: Bulk endpoints (up to 100 advertisers per call)
 * - LinkedIn: Batch decoration (multiple resources per call)
 * - Twitter: Batch lookup (up to 100 users per call)
 * - Snapchat: Bulk operations (up to 2000 per call)
 */
interface PlatformBatcherInterface
{
    /**
     * Execute a batch of queued requests
     *
     * @param string $connectionId The platform connection UUID
     * @param Collection $requests Collection of BatchRequestQueue models
     * @return array Results indexed by request ID [request_id => response_data]
     */
    public function executeBatch(string $connectionId, Collection $requests): array;

    /**
     * Get the batch type identifier
     *
     * Used for logging and analytics. Examples:
     * - 'field_expansion' (Meta nested queries)
     * - 'search_stream' (Google streaming)
     * - 'bulk' (TikTok bulk endpoints)
     * - 'standard' (default batching)
     *
     * @return string
     */
    public function getBatchType(): string;

    /**
     * Get maximum requests per batch
     *
     * Platform-specific limits:
     * - Meta: 50 (Batch API limit)
     * - Google: 1000 (SearchStream can return more)
     * - TikTok: 100 (Advertiser info limit)
     * - LinkedIn: 50 (Batch decoration limit)
     * - Twitter: 100 (User lookup limit)
     * - Snapchat: 2000 (Bulk operations limit)
     *
     * @return int
     */
    public function getMaxBatchSize(): int;

    /**
     * Get optimal flush interval in seconds
     *
     * Based on rate limits and typical usage patterns:
     * - Meta: 300s (5min) - 200/hour limit
     * - Google: 600s (10min) - 15000/day limit
     * - TikTok: 600s (10min) - 100/hour limit
     * - LinkedIn: 1800s (30min) - 100/day limit
     * - Twitter: 300s (5min) - 300/15min limit
     * - Snapchat: 600s (10min) - 100/hour limit
     *
     * @return int
     */
    public function getFlushInterval(): int;

    /**
     * Get the platform identifier
     *
     * @return string One of: meta, google, tiktok, linkedin, twitter, snapchat
     */
    public function getPlatform(): string;

    /**
     * Check if batcher can handle a specific request type
     *
     * @param string $requestType The request type (e.g., 'get_pages', 'get_metrics')
     * @return bool
     */
    public function canHandle(string $requestType): bool;

    /**
     * Get supported request types
     *
     * @return array List of request types this batcher can handle
     */
    public function getSupportedRequestTypes(): array;
}
