<?php

namespace App\Repositories\CMIS;

use App\Repositories\Contracts\ContextRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Context Functions
 * Encapsulates PostgreSQL functions related to contexts (value contexts, creative contexts)
 */
class ContextRepository implements ContextRepositoryInterface
{
    /**
     * Search contexts with full-text search
     * Corresponds to: cmis.search_contexts(p_search_query, p_context_type, p_limit)
     *
     * @param string $searchQuery Search query text
     * @param string|null $contextType Type of context to filter (optional)
     * @param int $limit Maximum number of results (default: 20)
     * @return Collection Collection of search results with relevance scores
     */
    public function searchContexts(string $searchTerm, ?string $orgId = null, int $limit = 20): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis.search_contexts(?, ?, ?)',
            [$searchTerm, $orgId, $limit]
        );

        return collect($results);
    }

    /**
     * Get context details
     *
     * @param string $contextId Context UUID
     * @return object|null Context details object
     */
    public function getContextDetails(string $contextId): ?object
    {
        $results = DB::select(
            'SELECT * FROM cmis.get_context_details(?)',
            [$contextId]
        );

        return $results[0] ?? null;
    }

    /**
     * Link context to campaign
     *
     * @param string $contextId Context UUID
     * @param string $campaignId Campaign UUID
     * @return bool Success status
     */
    public function linkContextToCampaign(string $contextId, string $campaignId): bool
    {
        try {
            DB::select(
                'SELECT cmis.link_context_to_campaign(?, ?)',
                [$contextId, $campaignId]
            );
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to link context to campaign', [
                'context_id' => $contextId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
