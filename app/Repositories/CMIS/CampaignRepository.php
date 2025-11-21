<?php

namespace App\Repositories\CMIS;

use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Campaign Functions
 * Encapsulates PostgreSQL functions related to campaigns and their management
 */
class CampaignRepository implements CampaignRepositoryInterface
{
    /**
     * Create a campaign with associated contexts safely
     * Corresponds to: cmis.create_campaign_and_context_safe()
     *
     * @param string $orgId Organization UUID
     * @param string $offeringId Offering UUID
     * @param string $segmentId Segment UUID
     * @param string $campaignName Name of the campaign
     * @param string $framework Framework type
     * @param string $tone Tone of voice
     * @param array $tags Array of tags
     * @return Collection Collection containing campaign_id, context_id, creative_context_id
     */
    public function createCampaignWithContext(
        string $orgId,
        string $offeringId,
        string $segmentId,
        string $campaignName,
        string $framework,
        string $tone,
        array $tags
    ): Collection {
        // Security: Use JSON binding instead of raw SQL string concatenation
        // Convert tags array to PostgreSQL array using json_array_elements
        $tagsJson = json_encode($tags);

        $results = DB::select(
            'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?,
                ARRAY(SELECT jsonb_array_elements_text(?::jsonb))
            )',
            [$orgId, $offeringId, $segmentId, $campaignName, $framework, $tone, $tagsJson]
        );

        return collect($results);
    }

    /**
     * Find related campaigns based on shared contexts
     * Corresponds to: cmis.find_related_campaigns(p_campaign_id, p_limit)
     *
     * @param string $campaignId Campaign UUID
     * @param int $limit Maximum number of results (default: 10)
     * @return Collection Collection of related campaigns with similarity scores
     */
    public function findRelatedCampaigns(string $campaignId, int $limit = 10): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis.find_related_campaigns(?, ?)',
            [$campaignId, $limit]
        );

        return collect($results);
    }

    /**
     * Get campaign contexts
     * Corresponds to: cmis.get_campaign_contexts(p_campaign_id, p_include_inactive)
     *
     * @param string $campaignId Campaign UUID
     * @param bool $includeInactive Include inactive contexts (default: false)
     * @return Collection Collection of campaign contexts
     */
    public function getCampaignContexts(string $campaignId, bool $includeInactive = false): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis.get_campaign_contexts(?, ?)',
            [$campaignId, $includeInactive]
        );

        return collect($results);
    }

    /**
     * Analyze campaign performance
     * Corresponds to: cmis.analyze_campaign_performance()
     *
     * @param string $campaignId Campaign UUID
     * @return object|null Performance analysis result
     */
    public function analyzeCampaignPerformance(string $campaignId): ?object
    {
        $result = DB::selectOne(
            'SELECT * FROM cmis.analyze_campaign_performance(?)',
            [$campaignId]
        );

        return $result;
    }

    /**
     * Get campaign insights
     * Corresponds to: cmis.get_campaign_insights()
     *
     * @param string $campaignId Campaign UUID
     * @param string|null $focusArea Optional focus area filter
     * @return object|null Campaign insights
     */
    public function getCampaignInsights(string $campaignId, ?string $focusArea = null): ?object
    {
        $result = DB::selectOne(
            'SELECT * FROM cmis.get_campaign_insights(?, ?)',
            [$campaignId, $focusArea]
        );

        return $result;
    }

    /**
     * Match campaigns to offerings
     * Corresponds to: cmis.match_campaigns_to_offerings()
     *
     * @param string $orgId Organization UUID
     * @param int $limit Maximum number of matches
     * @return Collection Collection of campaign-offering matches
     */
    public function matchCampaignsToOfferings(string $orgId, int $limit = 10): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis.match_campaigns_to_offerings(?, ?)',
            [$orgId, $limit]
        );

        return collect($results);
    }

    /**
     * Get all campaigns (automatically filtered by RLS)
     *
     * @return Collection Collection of campaigns for current organization
     */
    public function getAllCampaigns(): Collection
    {
        $results = DB::table('cmis.campaigns')
            ->whereNull('deleted_at')
            ->get();

        return collect($results);
    }
}
