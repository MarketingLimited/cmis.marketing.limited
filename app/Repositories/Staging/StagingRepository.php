<?php

namespace App\Repositories\Staging;

use Illuminate\Support\Facades\DB;

/**
 * Repository for CMIS Staging Functions (Legacy)
 * Encapsulates legacy PostgreSQL functions in the cmis_staging schema
 */
class StagingRepository
{
    /**
     * Generate brief summary (legacy version)
     * Corresponds to: cmis_staging.generate_brief_summary_legacy()
     *
     * @param string $briefId Brief UUID
     * @return object|null JSON object containing brief summary
     */
    public function generateBriefSummaryLegacy(string $briefId): ?object
    {
        $results = DB::select(
            'SELECT cmis_staging.generate_brief_summary_legacy(?) as summary',
            [$briefId]
        );

        return $results[0]->summary ?? null;
    }

    /**
     * Refresh creative index (legacy version)
     * Corresponds to: cmis_staging.refresh_creative_index_legacy()
     *
     * @return bool Success status
     */
    public function refreshCreativeIndexLegacy(): bool
    {
        return DB::statement('SELECT cmis_staging.refresh_creative_index_legacy()');
    }

    /**
     * Validate brief structure (legacy version)
     * Corresponds to: cmis_staging.validate_brief_structure_legacy()
     *
     * @param array $brief Brief data as array (will be converted to JSONB)
     * @return bool True if brief structure is valid
     */
    public function validateBriefStructureLegacy(array $brief): bool
    {
        $result = DB::select(
            'SELECT cmis_staging.validate_brief_structure_legacy(?::jsonb) as is_valid',
            [json_encode($brief)]
        );

        return $result[0]->is_valid ?? false;
    }
}
