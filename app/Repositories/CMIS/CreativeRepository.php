<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Creative Functions
 * Encapsulates PostgreSQL functions related to creative briefs and assets
 */
class CreativeRepository
{
    /**
     * Generate a brief summary
     * Corresponds to: cmis.generate_brief_summary(p_brief_id)
     *
     * @param string $briefId Brief UUID
     * @return object|null JSON object containing brief summary
     */
    public function generateBriefSummary(string $briefId): ?object
    {
        $results = DB::select(
            'SELECT cmis.generate_brief_summary(?) as summary',
            [$briefId]
        );

        return $results[0]->summary ?? null;
    }

    /**
     * Validate brief structure
     * Corresponds to: cmis.validate_brief_structure(p_brief)
     *
     * @param array $brief Brief data as array (will be converted to JSONB)
     * @return bool True if brief structure is valid
     */
    public function validateBriefStructure(array $brief): bool
    {
        $result = DB::select(
            'SELECT cmis.validate_brief_structure(?::jsonb) as is_valid',
            [json_encode($brief)]
        );

        return $result[0]->is_valid ?? false;
    }

    /**
     * Link a brief to content
     * Corresponds to: cmis.link_brief_to_content(p_brief_id, p_content_id)
     *
     * @param string $briefId Brief UUID
     * @param string $contentId Content UUID
     * @return bool Success status
     */
    public function linkBriefToContent(string $briefId, string $contentId): bool
    {
        return DB::statement(
            'SELECT cmis.link_brief_to_content(?, ?)',
            [$briefId, $contentId]
        );
    }

    /**
     * Refresh creative index
     * Corresponds to: cmis.refresh_creative_index()
     *
     * @return bool Success status
     */
    public function refreshCreativeIndex(): bool
    {
        return DB::statement('SELECT cmis.refresh_creative_index()');
    }

    /**
     * Auto delete unapproved assets (older than 7 days)
     * Corresponds to: cmis.auto_delete_unapproved_assets()
     *
     * @return bool Success status
     */
    public function autoDeleteUnapprovedAssets(): bool
    {
        return DB::statement('SELECT cmis.auto_delete_unapproved_assets()');
    }
}
