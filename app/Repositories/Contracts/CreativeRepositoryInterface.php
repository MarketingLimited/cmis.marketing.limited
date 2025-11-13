<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface CreativeRepositoryInterface
{
    /**
     * Index creative assets
     */
    public function indexCreativeAssets(string $orgId): Collection;

    /**
     * Get asset recommendations
     */
    public function getAssetRecommendations(string $campaignId, int $limit = 5): Collection;

    /**
     * Analyze creative performance
     */
    public function analyzeCreativePerformance(string $assetId): ?object;
}
