<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Context Functions
 * Encapsulates PostgreSQL functions related to contexts (value contexts, creative contexts)
 */
class ContextRepository
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
    public function searchContexts(string $searchQuery, ?string $contextType = null, int $limit = 20): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis.search_contexts(?, ?, ?)',
            [$searchQuery, $contextType, $limit]
        );

        return collect($results);
    }
}
