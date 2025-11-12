<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Campaign Functions
 * Encapsulates PostgreSQL functions related to campaigns and their management
 */
class CampaignRepository
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
        $results = DB::select(
            'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?, ?)',
            [$orgId, $offeringId, $segmentId, $campaignName, $framework, $tone, DB::raw("ARRAY['" . implode("','", $tags) . "']")]
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
}
