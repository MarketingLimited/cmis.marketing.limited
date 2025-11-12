<?php

namespace App\Repositories\Analytics;

use Illuminate\Support\Facades\DB;

/**
 * Repository for CMIS AI Analytics Functions
 * Encapsulates PostgreSQL functions related to AI-powered analytics
 */
class AIAnalyticsRepository
{
    /**
     * Get AI-powered recommendations for focus areas
     * Corresponds to: cmis_ai_analytics.fn_recommend_focus()
     *
     * @return object|null JSON object containing recommendations
     */
    public function recommendFocus(): ?object
    {
        $results = DB::select('SELECT * FROM cmis_ai_analytics.fn_recommend_focus()');

        return $results[0]->recommendation ?? null;
    }
}
