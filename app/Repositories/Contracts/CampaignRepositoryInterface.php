<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface CampaignRepositoryInterface
{
    /**
     * Create campaign with context safely
     */
    public function createCampaignWithContext(
        string $orgId,
        string $offeringId,
        string $segmentId,
        string $campaignName,
        string $framework,
        string $tone,
        array $tags
    ): Collection;

    /**
     * Find related campaigns
     */
    public function findRelatedCampaigns(string $campaignId, int $limit = 5): Collection;

    /**
     * Get campaign contexts
     */
    public function getCampaignContexts(string $campaignId, bool $includeInactive = false): Collection;

    /**
     * Analyze campaign performance
     */
    public function analyzeCampaignPerformance(string $campaignId): ?object;

    /**
     * Get campaign insights
     */
    public function getCampaignInsights(string $campaignId, ?string $focusArea = null): ?object;

    /**
     * Match campaigns to offerings
     */
    public function matchCampaignsToOfferings(string $orgId, int $limit = 10): Collection;
}
