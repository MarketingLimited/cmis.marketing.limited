<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;

/**
 * Repository for CMIS Utility Functions
 * Internal utility functions used by other database operations
 */
class UtilityRepository
{
    /**
     * Immutable setweight function for tsvector
     * Corresponds to: cmis.cmis_immutable_setweight(vec, w)
     *
     * Sets the weight of a tsvector for full-text search ranking
     * This is an immutable version that can be used in functional indexes
     *
     * @param string $tsvector The tsvector value (as string)
     * @param string $weight Weight character ('A', 'B', 'C', or 'D')
     * @return string Modified tsvector
     */
    public function immutableSetweight(string $tsvector, string $weight): string
    {
        $result = DB::select(
            'SELECT cmis.cmis_immutable_setweight(?::tsvector, ?) as result',
            [$tsvector, $weight]
        );

        return $result[0]->result ?? '';
    }

    /**
     * Immutable to_tsvector function
     * Corresponds to: cmis.cmis_immutable_tsvector(cfg, txt)
     *
     * Creates a tsvector from text using specified configuration
     * This is an immutable version that can be used in functional indexes
     *
     * @param string $config Text search configuration (e.g., 'english', 'arabic')
     * @param string $text Text to convert to tsvector
     * @return string Resulting tsvector
     */
    public function immutableTsvector(string $config, string $text): string
    {
        $result = DB::select(
            'SELECT cmis.cmis_immutable_tsvector(?, ?) as result',
            [$config, $text]
        );

        return $result[0]->result ?? '';
    }
}
