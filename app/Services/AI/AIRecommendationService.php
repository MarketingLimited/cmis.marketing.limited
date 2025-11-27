<?php

namespace App\Services\AI;

use App\Services\Embedding\EmbeddingOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * AI Recommendation Service (Phase 3 - Advanced AI Analytics)
 *
 * Provides intelligent content and campaign recommendations using:
 * - Vector similarity search (pgvector)
 * - Historical performance data
 * - Machine learning insights
 * - Collaborative filtering
 *
 * Features:
 * - Similar content recommendations
 * - Best performing content suggestions
 * - Campaign strategy recommendations
 * - Audience targeting suggestions
 * - Optimal posting time recommendations
 */
class AIRecommendationService
{
    protected EmbeddingOrchestrator $embeddingOrchestrator;

    public function __construct(EmbeddingOrchestrator $embeddingOrchestrator)
    {
        $this->embeddingOrchestrator = $embeddingOrchestrator;
    }

    /**
     * Get similar high-performing content based on a reference
     *
     * @param string $orgId Organization ID
     * @param string $referenceType Type: 'content', 'campaign', 'creative'
     * @param string $referenceId ID of the reference item
     * @param int $limit Number of recommendations
     * @return array
     */
    public function getSimilarHighPerformingContent(
        string $orgId,
        string $referenceType,
        string $referenceId,
        int $limit = 10
    ): array {
        try {
            // Get embedding for reference item
            $referenceEmbedding = $this->getEmbedding($referenceType, $referenceId);

            if (!$referenceEmbedding) {
                return [
                    'success' => false,
                    'message' => 'Reference item not found or has no embedding',
                    'recommendations' => [],
                ];
            }

            // Find similar items with good performance
            $recommendations = $this->findSimilarWithPerformance(
                $orgId,
                $referenceType,
                $referenceEmbedding,
                $limit
            );

            return [
                'success' => true,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'recommendations' => $recommendations,
                'count' => count($recommendations),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get similar high-performing content', [
                'error' => $e->getMessage(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate recommendations',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get content recommendations for a campaign
     *
     * @param string $campaignId Campaign ID
     * @param array $options Options: ['content_type', 'platform', 'limit']
     * @return array
     */
    public function getContentRecommendationsForCampaign(
        string $campaignId,
        array $options = []
    ): array {
        try {
            $limit = $options['limit'] ?? 10;

            // Get campaign details
            $campaign = DB::table('cmis.campaigns')
                ->where('campaign_id', $campaignId)
                ->first();

            if (!$campaign) {
                return ['success' => false, 'message' => 'Campaign not found'];
            }

            // Get campaign embedding
            $campaignEmbedding = $this->getEmbedding('campaign', $campaignId);

            if (!$campaignEmbedding) {
                return ['success' => false, 'message' => 'Campaign has no embedding'];
            }

            // Find similar successful content
            $vectorLiteral = '[' . implode(',', $campaignEmbedding) . ']';

            $query = "
                WITH similar_content AS (
                    SELECT
                        ce.content_id,
                        c.title,
                        c.content_type,
                        c.status,
                        1 - (ce.embedding <=> ?::vector) AS similarity,
                        -- Performance metrics
                        COALESCE(pm.impressions, 0) as impressions,
                        COALESCE(pm.clicks, 0) as clicks,
                        COALESCE(pm.engagement_rate, 0) as engagement_rate,
                        -- Calculate performance score
                        (COALESCE(pm.engagement_rate, 0) * 100 +
                         LOG(1 + COALESCE(pm.impressions, 0)) * 10) as performance_score
                    FROM cmis_ai.content_embeddings ce
                    JOIN cmis.content_items c ON ce.content_id = c.content_id
                    LEFT JOIN LATERAL (
                        SELECT
                            AVG(impressions) as impressions,
                            AVG(clicks) as clicks,
                            AVG(engagement_rate) as engagement_rate
                        FROM cmis.performance_metrics
                        WHERE entity_type = 'content'
                        AND entity_id = ce.content_id
                    ) pm ON true
                    WHERE c.org_id = ?
                    AND c.status = 'published'
                    AND 1 - (ce.embedding <=> ?::vector) >= 0.7
                )
                SELECT *
                FROM similar_content
                ORDER BY
                    (similarity * 0.4 + (performance_score / 100) * 0.6) DESC
                LIMIT ?
            ";

            $recommendations = DB::select($query, [
                $vectorLiteral,
                $campaign->org_id,
                $vectorLiteral,
                $limit
            ]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'recommendations' => array_map(function($rec) {
                    return [
                        'content_id' => $rec->content_id,
                        'title' => $rec->title,
                        'content_type' => $rec->content_type,
                        'similarity_score' => round($rec->similarity, 3),
                        'performance_score' => round($rec->performance_score, 2),
                        'metrics' => [
                            'impressions' => (int)$rec->impressions,
                            'clicks' => (int)$rec->clicks,
                            'engagement_rate' => round($rec->engagement_rate, 3),
                        ],
                    ];
                }, $recommendations),
                'count' => count($recommendations),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get content recommendations for campaign', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate recommendations',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get best performing content for reuse
     *
     * @param string $orgId Organization ID
     * @param array $filters Filters: ['content_type', 'platform', 'date_range', 'min_performance']
     * @param int $limit Number of results
     * @return array
     */
    public function getBestPerformingContent(
        string $orgId,
        array $filters = [],
        int $limit = 20
    ): array {
        try {
            $cacheKey = "best_performing_content:{$orgId}:" . md5(json_encode($filters));

            return Cache::remember($cacheKey, 3600, function () use ($orgId, $filters, $limit) {
                $query = "
                    SELECT
                        c.content_id,
                        c.title,
                        c.content_type,
                        c.body,
                        c.created_at,
                        -- Aggregate performance metrics
                        COUNT(DISTINCT pm.metric_id) as metric_count,
                        AVG(pm.impressions) as avg_impressions,
                        AVG(pm.clicks) as avg_clicks,
                        AVG(pm.engagement_rate) as avg_engagement_rate,
                        AVG(pm.conversion_rate) as avg_conversion_rate,
                        -- Calculate composite performance score
                        (
                            AVG(pm.engagement_rate) * 40 +
                            (AVG(pm.clicks) / NULLIF(AVG(pm.impressions), 0)) * 100 * 30 +
                            AVG(pm.conversion_rate) * 30
                        ) as performance_score
                    FROM cmis.content_items c
                    LEFT JOIN cmis.performance_metrics pm ON
                        pm.entity_type = 'content' AND
                        pm.entity_id = c.content_id
                    WHERE c.org_id = ?
                    AND c.status = 'published'
                ";

                $params = [$orgId];

                // Apply filters
                if (!empty($filters['content_type'])) {
                    $query .= " AND c.content_type = ?";
                    $params[] = $filters['content_type'];
                }

                if (!empty($filters['date_range'])) {
                    $query .= " AND c.created_at >= ?";
                    $params[] = $filters['date_range'];
                }

                $query .= "
                    GROUP BY c.content_id, c.title, c.content_type, c.body, c.created_at
                    HAVING COUNT(DISTINCT pm.metric_id) > 0
                    ORDER BY performance_score DESC NULLS LAST
                    LIMIT ?
                ";

                $params[] = $limit;

                $results = DB::select($query, $params);

                return [
                    'success' => true,
                    'content' => array_map(function($item) {
                        return [
                            'content_id' => $item->content_id,
                            'title' => $item->title,
                            'content_type' => $item->content_type,
                            'performance_score' => round($item->performance_score ?? 0, 2),
                            'metrics' => [
                                'avg_impressions' => round($item->avg_impressions ?? 0, 0),
                                'avg_clicks' => round($item->avg_clicks ?? 0, 0),
                                'avg_engagement_rate' => round($item->avg_engagement_rate ?? 0, 3),
                                'avg_conversion_rate' => round($item->avg_conversion_rate ?? 0, 3),
                            ],
                            'created_at' => $item->created_at,
                        ];
                    }, $results),
                    'count' => count($results),
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to get best performing content', [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve best performing content',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get optimal posting time recommendations
     *
     * @param string $orgId Organization ID
     * @param string $platform Platform name
     * @return array
     */
    public function getOptimalPostingTimes(string $orgId, string $platform = null): array
    {
        try {
            $cacheKey = "optimal_posting_times:{$orgId}:" . ($platform ?? 'all');

            return Cache::remember($cacheKey, 7200, function () use ($orgId, $platform) {
                $query = "
                    SELECT
                        EXTRACT(DOW FROM sp.scheduled_at) as day_of_week,
                        EXTRACT(HOUR FROM sp.scheduled_at) as hour_of_day,
                        COUNT(*) as post_count,
                        AVG(pm.engagement_rate) as avg_engagement_rate,
                        AVG(pm.clicks) as avg_clicks,
                        AVG(pm.impressions) as avg_impressions
                    FROM cmis_social.scheduled_posts sp
                    LEFT JOIN cmis.performance_metrics pm ON
                        pm.entity_type = 'post' AND
                        pm.entity_id = sp.post_id
                    WHERE sp.org_id = ?
                    AND sp.status = 'published'
                    AND sp.scheduled_at >= NOW() - INTERVAL '90 days'
                ";

                $params = [$orgId];

                if ($platform) {
                    $query .= " AND sp.platform = ?";
                    $params[] = $platform;
                }

                $query .= "
                    GROUP BY day_of_week, hour_of_day
                    HAVING COUNT(*) >= 3
                    ORDER BY avg_engagement_rate DESC NULLS LAST
                    LIMIT 20
                ";

                $results = DB::select($query, $params);

                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                return [
                    'success' => true,
                    'platform' => $platform ?? 'all',
                    'optimal_times' => array_map(function($item) use ($dayNames) {
                        return [
                            'day_of_week' => $dayNames[(int)$item->day_of_week] ?? 'Unknown',
                            'hour_of_day' => (int)$item->hour_of_day,
                            'time_slot' => sprintf('%02d:00 - %02d:59', (int)$item->hour_of_day, (int)$item->hour_of_day),
                            'post_count' => (int)$item->post_count,
                            'avg_engagement_rate' => round($item->avg_engagement_rate ?? 0, 3),
                            'avg_clicks' => round($item->avg_clicks ?? 0, 0),
                            'avg_impressions' => round($item->avg_impressions ?? 0, 0),
                        ];
                    }, $results),
                    'count' => count($results),
                    'recommendation' => count($results) > 0 ?
                        "Best time to post: {$dayNames[(int)$results[0]->day_of_week]} at {$results[0]->hour_of_day}:00" :
                        "Not enough data for recommendations",
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to get optimal posting times', [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to calculate optimal posting times',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get audience targeting recommendations
     *
     * @param string $campaignId Campaign ID
     * @return array
     */
    public function getAudienceTargetingRecommendations(string $campaignId): array
    {
        try {
            // Get campaign details
            $campaign = DB::table('cmis.campaigns')
                ->where('campaign_id', $campaignId)
                ->first();

            if (!$campaign) {
                return ['success' => false, 'message' => 'Campaign not found'];
            }

            // Analyze similar successful campaigns
            $query = "
                SELECT
                    t.age_min,
                    t.age_max,
                    t.genders,
                    t.locations,
                    t.interests,
                    t.languages,
                    AVG(pm.conversion_rate) as avg_conversion_rate,
                    AVG(pm.engagement_rate) as avg_engagement_rate,
                    COUNT(DISTINCT c.campaign_id) as campaign_count
                FROM cmis.campaigns c
                JOIN cmis.targeting t ON c.campaign_id = t.campaign_id
                LEFT JOIN cmis.performance_metrics pm ON
                    pm.entity_type = 'campaign' AND
                    pm.entity_id = c.campaign_id
                WHERE c.org_id = ?
                AND c.status IN ('active', 'completed')
                AND c.campaign_id != ?
                GROUP BY t.age_min, t.age_max, t.genders, t.locations, t.interests, t.languages
                HAVING AVG(pm.conversion_rate) > 0
                ORDER BY
                    (AVG(pm.conversion_rate) * 0.6 + AVG(pm.engagement_rate) * 0.4) DESC
                LIMIT 10
            ";

            $recommendations = DB::select($query, [$campaign->org_id, $campaignId]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'recommendations' => array_map(function($rec) {
                    return [
                        'targeting' => [
                            'age_range' => "{$rec->age_min}-{$rec->age_max}",
                            'genders' => json_decode($rec->genders ?? '[]'),
                            'locations' => json_decode($rec->locations ?? '[]'),
                            'interests' => json_decode($rec->interests ?? '[]'),
                            'languages' => json_decode($rec->languages ?? '[]'),
                        ],
                        'performance' => [
                            'avg_conversion_rate' => round($rec->avg_conversion_rate, 3),
                            'avg_engagement_rate' => round($rec->avg_engagement_rate, 3),
                            'campaign_count' => (int)$rec->campaign_count,
                        ],
                    ];
                }, $recommendations),
                'count' => count($recommendations),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get audience targeting recommendations', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate audience recommendations',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get embedding for an entity
     *
     * @param string $type Entity type
     * @param string $id Entity ID
     * @return array|null
     */
    private function getEmbedding(string $type, string $id): ?array
    {
        $table = match($type) {
            'content' => 'cmis_ai.content_embeddings',
            'campaign' => 'cmis_ai.campaign_embeddings',
            'creative' => 'cmis_ai.creative_embeddings',
            default => null,
        };

        if (!$table) {
            return null;
        }

        $column = $type . '_id';
        $result = DB::table($table)->where($column, $id)->first();

        if (!$result || !$result->embedding) {
            return null;
        }

        // Convert PostgreSQL array format to PHP array
        $embedding = $result->embedding;
        if (is_string($embedding)) {
            $embedding = trim($embedding, '[]');
            $embedding = array_map('floatval', explode(',', $embedding));
        }

        return $embedding;
    }

    /**
     * Find similar items with good performance
     *
     * @param string $orgId Organization ID
     * @param string $type Entity type
     * @param array $embedding Reference embedding
     * @param int $limit Number of results
     * @return array
     */
    private function findSimilarWithPerformance(
        string $orgId,
        string $type,
        array $embedding,
        int $limit
    ): array {
        $vectorLiteral = '[' . implode(',', $embedding) . ']';

        $table = match($type) {
            'content' => 'cmis_ai.content_embeddings',
            'campaign' => 'cmis_ai.campaign_embeddings',
            'creative' => 'cmis_ai.creative_embeddings',
            default => null,
        };

        if (!$table) {
            return [];
        }

        $mainTable = match($type) {
            'content' => 'cmis.content_items',
            'campaign' => 'cmis.campaigns',
            'creative' => 'cmis.creative_assets',
            default => null,
        };

        $idColumn = $type . '_id';

        $query = "
            SELECT
                e.{$idColumn},
                1 - (e.embedding <=> ?::vector) AS similarity,
                COALESCE(AVG(pm.engagement_rate), 0) as avg_engagement,
                COALESCE(SUM(pm.impressions), 0) as total_impressions
            FROM {$table} e
            JOIN {$mainTable} m ON e.{$idColumn} = m.{$idColumn}
            LEFT JOIN cmis.performance_metrics pm ON
                pm.entity_type = ? AND
                pm.entity_id = e.{$idColumn}
            WHERE m.org_id = ?
            AND 1 - (e.embedding <=> ?::vector) >= 0.7
            GROUP BY e.{$idColumn}, e.embedding
            ORDER BY
                (1 - (e.embedding <=> ?::vector)) * 0.5 +
                (COALESCE(AVG(pm.engagement_rate), 0) * 0.5) DESC
            LIMIT ?
        ";

        $results = DB::select($query, [
            $vectorLiteral,
            $type,
            $orgId,
            $vectorLiteral,
            $vectorLiteral,
            $limit
        ]);

        return array_map(function($item) use ($idColumn) {
            return [
                'id' => $item->{$idColumn},
                'similarity_score' => round($item->similarity, 3),
                'avg_engagement' => round($item->avg_engagement, 3),
                'total_impressions' => (int)$item->total_impressions,
            ];
        }, $results);
    }
}
