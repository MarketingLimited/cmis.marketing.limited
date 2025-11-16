<?php

namespace App\Services;

use App\Models\Knowledge\KnowledgeIndex;
use App\Models\CMIS\KnowledgeItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * Knowledge Service
 *
 * Manages knowledge base items, semantic search, and embeddings
 */
class KnowledgeService
{
    public function __construct(
        protected CacheService $cache,
        protected EmbeddingService $embeddingService
    ) {}

    /**
     * Perform semantic search on knowledge base
     *
     * @param string $query The search query
     * @param string $orgId Organization ID to scope search
     * @param int $limit Maximum number of results
     * @param string|null $contentType Optional content type filter
     * @return Collection
     */
    public function semanticSearch(
        string $query,
        string $orgId,
        int $limit = 10,
        ?string $contentType = null
    ): Collection {
        try {
            // Generate embedding for the search query
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::warning('Failed to generate embedding for query', ['query' => $query]);
                return collect([]);
            }

            // Build query
            $vectorStr = '[' . implode(',', $queryEmbedding) . ']';

            $queryBuilder = KnowledgeIndex::query()
                ->where('org_id', $orgId)
                ->selectRaw('*, embedding <=> ?::vector as distance', [$vectorStr])
                ->orderBy('distance');

            // Filter by content type/category if specified
            if ($contentType) {
                $queryBuilder->where('category', $this->mapContentTypeToCategory($contentType));
            }

            $results = $queryBuilder->limit($limit)->get();

            // Update access metrics
            foreach ($results as $result) {
                $result->recordAccess();
            }

            // Format results for API response
            return $results->map(function ($item) {
                return [
                    'id' => $item->knowledge_id,
                    'title' => $item->title,
                    'content_type' => $this->mapCategoryToContentType($item->category),
                    'content' => $item->content,
                    'summary' => $item->content_summary,
                    'relevance_score' => $item->distance ? (1 - min($item->distance, 1)) : null,
                    'created_at' => $item->created_at?->toISOString(),
                    'tags' => $item->tags,
                ];
            });

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'query' => $query,
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Create a new knowledge item
     *
     * @param array $data
     * @param string $orgId
     * @param string $userId
     * @return KnowledgeIndex
     */
    public function create(array $data, string $orgId, string $userId): KnowledgeIndex
    {
        // Generate embedding for the content
        $embedding = null;
        if (!empty($data['content'])) {
            $embedding = $this->embeddingService->generateEmbedding($data['content']);
        }

        $knowledgeItem = KnowledgeIndex::create([
            'knowledge_id' => Str::uuid(),
            'org_id' => $orgId,
            'title' => $data['title'],
            'content' => $data['content'],
            'content_summary' => $data['summary'] ?? $this->generateSummary($data['content']),
            'category' => $this->mapContentTypeToCategory($data['content_type']),
            'source_type' => 'manual',
            'tags' => $data['tags'] ?? [],
            'embedding' => $embedding,
            'metadata' => [
                'created_by' => $userId,
                'source' => 'gpt_api',
            ],
            'indexed_at' => now(),
            'is_verified' => false,
        ]);

        // Invalidate org knowledge cache
        $this->cache->invalidate("org:{$orgId}:knowledge:*");

        Log::info('Knowledge item created', [
            'knowledge_id' => $knowledgeItem->knowledge_id,
            'org_id' => $orgId,
        ]);

        return $knowledgeItem;
    }

    /**
     * Update knowledge item
     *
     * @param KnowledgeIndex $knowledgeItem
     * @param array $data
     * @return KnowledgeIndex
     */
    public function update(KnowledgeIndex $knowledgeItem, array $data): KnowledgeIndex
    {
        // Regenerate embedding if content changed
        if (isset($data['content']) && $data['content'] !== $knowledgeItem->content) {
            $data['embedding'] = $this->embeddingService->generateEmbedding($data['content']);
        }

        $knowledgeItem->update($data);

        // Invalidate caches
        $this->cache->invalidate("knowledge:{$knowledgeItem->knowledge_id}:*");
        $this->cache->invalidate("org:{$knowledgeItem->org_id}:knowledge:*");

        return $knowledgeItem->fresh();
    }

    /**
     * Delete knowledge item
     *
     * @param KnowledgeIndex $knowledgeItem
     * @return bool
     */
    public function delete(KnowledgeIndex $knowledgeItem): bool
    {
        $knowledgeId = $knowledgeItem->knowledge_id;
        $orgId = $knowledgeItem->org_id;

        $deleted = $knowledgeItem->delete();

        if ($deleted) {
            $this->cache->invalidate("knowledge:{$knowledgeId}:*");
            $this->cache->invalidate("org:{$orgId}:knowledge:*");
        }

        return $deleted;
    }

    /**
     * Get knowledge item by ID
     *
     * @param string $knowledgeId
     * @return KnowledgeIndex|null
     */
    public function get(string $knowledgeId): ?KnowledgeIndex
    {
        $cacheKey = "knowledge:{$knowledgeId}";

        return $this->cache->remember(
            $cacheKey,
            CacheService::TTL_MEDIUM,
            function () use ($knowledgeId) {
                return KnowledgeIndex::find($knowledgeId);
            }
        );
    }

    /**
     * List knowledge items for organization
     *
     * @param string $orgId
     * @param array $filters
     * @return Collection
     */
    public function listByOrg(string $orgId, array $filters = []): Collection
    {
        $query = KnowledgeIndex::where('org_id', $orgId);

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['content_type'])) {
            $query->where('category', $this->mapContentTypeToCategory($filters['content_type']));
        }

        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        if (isset($filters['tags'])) {
            $query->whereJsonContains('tags', $filters['tags']);
        }

        return $query->latest()->get();
    }

    /**
     * Find similar knowledge items
     *
     * @param KnowledgeIndex $knowledgeItem
     * @param int $limit
     * @return Collection
     */
    public function findSimilar(KnowledgeIndex $knowledgeItem, int $limit = 5): Collection
    {
        if (!$knowledgeItem->embedding) {
            return collect([]);
        }

        $embedding = $knowledgeItem->embedding;
        $vectorStr = '[' . implode(',', $embedding) . ']';

        return KnowledgeIndex::query()
            ->where('org_id', $knowledgeItem->org_id)
            ->where('knowledge_id', '!=', $knowledgeItem->knowledge_id)
            ->selectRaw('*, embedding <=> ?::vector as distance', [$vectorStr])
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Verify knowledge item
     *
     * @param KnowledgeIndex $knowledgeItem
     * @param string $userId
     * @return KnowledgeIndex
     */
    public function verify(KnowledgeIndex $knowledgeItem, string $userId): KnowledgeIndex
    {
        $knowledgeItem->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);

        $this->cache->invalidate("knowledge:{$knowledgeItem->knowledge_id}:*");

        return $knowledgeItem->fresh();
    }

    /**
     * Get knowledge statistics for organization
     *
     * @param string $orgId
     * @return array
     */
    public function getStats(string $orgId): array
    {
        $cacheKey = "org:{$orgId}:knowledge_stats";

        return $this->cache->remember(
            $cacheKey,
            CacheService::TTL_MEDIUM,
            function () use ($orgId) {
                return [
                    'total' => KnowledgeIndex::where('org_id', $orgId)->count(),
                    'verified' => KnowledgeIndex::where('org_id', $orgId)->where('is_verified', true)->count(),
                    'by_category' => KnowledgeIndex::where('org_id', $orgId)
                        ->select('category', \DB::raw('COUNT(*) as count'))
                        ->groupBy('category')
                        ->pluck('count', 'category')
                        ->toArray(),
                    'total_accesses' => KnowledgeIndex::where('org_id', $orgId)->sum('access_count'),
                    'last_indexed' => KnowledgeIndex::where('org_id', $orgId)
                        ->latest('indexed_at')
                        ->value('indexed_at'),
                ];
            }
        );
    }

    /**
     * Reindex knowledge item (regenerate embeddings)
     *
     * @param KnowledgeIndex $knowledgeItem
     * @return KnowledgeIndex
     */
    public function reindex(KnowledgeIndex $knowledgeItem): KnowledgeIndex
    {
        if ($knowledgeItem->content) {
            $embedding = $this->embeddingService->generateEmbedding($knowledgeItem->content);

            $knowledgeItem->update([
                'embedding' => $embedding,
                'indexed_at' => now(),
            ]);

            $this->cache->invalidate("knowledge:{$knowledgeItem->knowledge_id}:*");

            Log::info('Knowledge item reindexed', [
                'knowledge_id' => $knowledgeItem->knowledge_id,
            ]);
        }

        return $knowledgeItem->fresh();
    }

    /**
     * Batch reindex knowledge items without embeddings
     *
     * @param string $orgId
     * @param int $limit
     * @return int Number of items reindexed
     */
    public function reindexPending(string $orgId, int $limit = 100): int
    {
        $items = KnowledgeIndex::where('org_id', $orgId)
            ->whereNull('embedding')
            ->limit($limit)
            ->get();

        $reindexed = 0;

        foreach ($items as $item) {
            try {
                $this->reindex($item);
                $reindexed++;
            } catch (\Exception $e) {
                Log::error('Failed to reindex knowledge item', [
                    'knowledge_id' => $item->knowledge_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reindexed;
    }

    /**
     * Map GPT content_type to database category
     *
     * @param string $contentType
     * @return string
     */
    protected function mapContentTypeToCategory(string $contentType): string
    {
        $mapping = [
            'brand_guideline' => 'brand',
            'market_research' => 'research',
            'competitor_analysis' => 'research',
            'campaign_brief' => 'marketing',
            'product_info' => 'product',
        ];

        return $mapping[$contentType] ?? 'general';
    }

    /**
     * Map database category to GPT content_type
     *
     * @param string $category
     * @return string
     */
    protected function mapCategoryToContentType(string $category): string
    {
        $mapping = [
            'brand' => 'brand_guideline',
            'research' => 'market_research',
            'marketing' => 'campaign_brief',
            'product' => 'product_info',
        ];

        return $mapping[$category] ?? 'general';
    }

    /**
     * Generate summary from content
     *
     * @param string $content
     * @return string
     */
    protected function generateSummary(string $content): string
    {
        // Simple truncation for now - could use AI to generate better summaries
        return Str::limit($content, 200);
    }
}
