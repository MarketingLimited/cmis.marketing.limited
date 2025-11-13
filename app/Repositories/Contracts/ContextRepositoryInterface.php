<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface ContextRepositoryInterface
{
    /**
     * Search contexts
     */
    public function searchContexts(
        string $searchTerm,
        ?string $orgId = null,
        int $limit = 20
    ): Collection;

    /**
     * Get context details
     */
    public function getContextDetails(string $contextId): ?object;

    /**
     * Link context to campaign
     */
    public function linkContextToCampaign(string $contextId, string $campaignId): bool;
}
