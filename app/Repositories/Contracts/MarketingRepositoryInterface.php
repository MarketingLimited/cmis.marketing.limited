<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface MarketingRepositoryInterface
{
    /**
     * Generate creative content
     */
    public function generateCreativeContent(
        string $campaignId,
        string $contentType,
        ?array $parameters = null
    ): ?object;

    /**
     * Generate creative variants
     */
    public function generateCreativeVariants(
        string $baseContentId,
        int $variantCount = 3
    ): Collection;

    /**
     * Analyze audience insights
     */
    public function analyzeAudienceInsights(string $orgId): ?object;

    /**
     * Get campaign recommendations
     */
    public function getCampaignRecommendations(
        string $orgId,
        ?string $objective = null
    ): Collection;
}
