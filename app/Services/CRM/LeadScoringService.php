<?php

namespace App\Services\CRM;

use App\Models\Lead\Lead;
use Illuminate\Http\Request;

/**
 * LeadScoringService
 *
 * Handles lead scoring calculations and score breakdown analysis.
 * Implements a comprehensive scoring algorithm based on multiple factors.
 */
class LeadScoringService
{
    // Scoring weights
    const WEIGHT_EMAIL_QUALITY = 15;
    const WEIGHT_PHONE = 10;
    const WEIGHT_COMPANY = 10;
    const WEIGHT_SOURCE = 20;
    const WEIGHT_ENGAGEMENT = 25;
    const WEIGHT_COMPLETENESS = 10;
    const WEIGHT_UTM_DATA = 10;

    // Source quality scores
    const SOURCE_SCORES = [
        'linkedin' => 20,
        'google-ads' => 18,
        'meta-ads' => 16,
        'tiktok-ads' => 15,
        'twitter-ads' => 14,
        'snapchat-ads' => 13,
        'referral' => 17,
        'organic' => 15,
        'direct' => 12,
        'other' => 8,
    ];

    /**
     * Calculate initial score for a new lead based on request data
     *
     * @param Request $request
     * @return int
     */
    public function calculateInitialScore(Request $request): int
    {
        $score = 0;

        // Email quality score (0-15 points)
        $score += $this->scoreEmailQuality($request->email);

        // Phone presence (0-10 points)
        if ($request->filled('phone')) {
            $score += self::WEIGHT_PHONE;
        }

        // Company presence (0-10 points)
        if ($request->filled('company')) {
            $score += self::WEIGHT_COMPANY;
        }

        // Source quality (0-20 points)
        $score += $this->scoreSource($request->input('source'));

        // UTM parameters presence (0-10 points)
        if ($request->filled('utm_parameters') && is_array($request->utm_parameters)) {
            $score += $this->scoreUtmParameters($request->utm_parameters);
        }

        // Data completeness (0-10 points)
        $score += $this->scoreDataCompleteness($request);

        return min($score, 100); // Cap at 100
    }

    /**
     * Calculate comprehensive score for an existing lead
     *
     * @param Lead $lead
     * @return int
     */
    public function calculateLeadScore(Lead $lead): int
    {
        $score = 0;

        // Email quality (0-15 points)
        $score += $this->scoreEmailQuality($lead->email);

        // Phone presence (0-10 points)
        if ($lead->phone) {
            $score += self::WEIGHT_PHONE;
        }

        // Company presence (0-10 points)
        if ($lead->company ?? null) {
            $score += self::WEIGHT_COMPANY;
        }

        // Source quality (0-20 points)
        $score += $this->scoreSource($lead->source);

        // Engagement score (0-25 points)
        $score += $this->scoreEngagement($lead);

        // Data completeness (0-10 points)
        $score += $this->scoreLeadCompleteness($lead);

        // UTM parameters (0-10 points)
        if ($lead->utm_parameters) {
            $score += $this->scoreUtmParameters($lead->utm_parameters);
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Get detailed score breakdown for a lead
     *
     * @param Lead $lead
     * @return array
     */
    public function getScoreBreakdown(Lead $lead): array
    {
        $breakdown = [
            'total_score' => 0,
            'components' => [],
        ];

        // Email quality
        $emailScore = $this->scoreEmailQuality($lead->email);
        $breakdown['components']['email_quality'] = [
            'score' => $emailScore,
            'max' => self::WEIGHT_EMAIL_QUALITY,
            'percentage' => round(($emailScore / self::WEIGHT_EMAIL_QUALITY) * 100, 2),
        ];

        // Phone
        $phoneScore = $lead->phone ? self::WEIGHT_PHONE : 0;
        $breakdown['components']['phone'] = [
            'score' => $phoneScore,
            'max' => self::WEIGHT_PHONE,
            'percentage' => round(($phoneScore / self::WEIGHT_PHONE) * 100, 2),
        ];

        // Company
        $companyScore = ($lead->company ?? null) ? self::WEIGHT_COMPANY : 0;
        $breakdown['components']['company'] = [
            'score' => $companyScore,
            'max' => self::WEIGHT_COMPANY,
            'percentage' => round(($companyScore / self::WEIGHT_COMPANY) * 100, 2),
        ];

        // Source
        $sourceScore = $this->scoreSource($lead->source);
        $breakdown['components']['source'] = [
            'score' => $sourceScore,
            'max' => self::WEIGHT_SOURCE,
            'percentage' => round(($sourceScore / self::WEIGHT_SOURCE) * 100, 2),
        ];

        // Engagement
        $engagementScore = $this->scoreEngagement($lead);
        $breakdown['components']['engagement'] = [
            'score' => $engagementScore,
            'max' => self::WEIGHT_ENGAGEMENT,
            'percentage' => round(($engagementScore / self::WEIGHT_ENGAGEMENT) * 100, 2),
        ];

        // Completeness
        $completenessScore = $this->scoreLeadCompleteness($lead);
        $breakdown['components']['completeness'] = [
            'score' => $completenessScore,
            'max' => self::WEIGHT_COMPLETENESS,
            'percentage' => round(($completenessScore / self::WEIGHT_COMPLETENESS) * 100, 2),
        ];

        // UTM parameters
        $utmScore = $lead->utm_parameters ? $this->scoreUtmParameters($lead->utm_parameters) : 0;
        $breakdown['components']['utm_tracking'] = [
            'score' => $utmScore,
            'max' => self::WEIGHT_UTM_DATA,
            'percentage' => round(($utmScore / self::WEIGHT_UTM_DATA) * 100, 2),
        ];

        $breakdown['total_score'] = min(
            $emailScore + $phoneScore + $companyScore + $sourceScore +
            $engagementScore + $completenessScore + $utmScore,
            100
        );

        return $breakdown;
    }

    /**
     * Score email quality based on domain and format
     *
     * @param string|null $email
     * @return int
     */
    private function scoreEmailQuality(?string $email): int
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 0;
        }

        $domain = substr(strrchr($email, "@"), 1);

        // Free email providers get lower scores
        $freeProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com'];
        if (in_array(strtolower($domain), $freeProviders)) {
            return 8; // 53% of max
        }

        // Corporate emails get higher scores
        return self::WEIGHT_EMAIL_QUALITY;
    }

    /**
     * Score based on lead source quality
     *
     * @param string|null $source
     * @return int
     */
    private function scoreSource(?string $source): int
    {
        if (!$source) {
            return 0;
        }

        $sourceLower = strtolower($source);

        // Check exact matches
        if (isset(self::SOURCE_SCORES[$sourceLower])) {
            return self::SOURCE_SCORES[$sourceLower];
        }

        // Check partial matches
        foreach (self::SOURCE_SCORES as $key => $score) {
            if (str_contains($sourceLower, $key)) {
                return $score;
            }
        }

        return self::SOURCE_SCORES['other'];
    }

    /**
     * Score engagement based on contacts and activity
     *
     * @param Lead $lead
     * @return int
     */
    private function scoreEngagement(Lead $lead): int
    {
        $score = 0;

        // Base engagement score
        $score += 5;

        // Has been contacted
        if ($lead->last_contacted_at) {
            $score += 8;

            // Recent contact (within 7 days)
            if ($lead->last_contacted_at->isAfter(now()->subDays(7))) {
                $score += 5;
            }
        }

        // Has campaign association
        if ($lead->campaign_id) {
            $score += 7;
        }

        return min($score, self::WEIGHT_ENGAGEMENT);
    }

    /**
     * Score data completeness for request
     *
     * @param Request $request
     * @return int
     */
    private function scoreDataCompleteness(Request $request): int
    {
        $requiredFields = ['name', 'email'];
        $optionalFields = ['phone', 'company', 'source', 'additional_data'];

        $filledRequired = 0;
        foreach ($requiredFields as $field) {
            if ($request->filled($field)) {
                $filledRequired++;
            }
        }

        $filledOptional = 0;
        foreach ($optionalFields as $field) {
            if ($request->filled($field)) {
                $filledOptional++;
            }
        }

        // Required fields: 5 points, Optional fields: 5 points
        $score = ($filledRequired / count($requiredFields)) * 5;
        $score += ($filledOptional / count($optionalFields)) * 5;

        return round($score);
    }

    /**
     * Score data completeness for existing lead
     *
     * @param Lead $lead
     * @return int
     */
    private function scoreLeadCompleteness(Lead $lead): int
    {
        $fields = [
            'name',
            'email',
            'phone',
            'source',
            'additional_data',
            'estimated_value',
        ];

        $filledCount = 0;
        foreach ($fields as $field) {
            if (!empty($lead->$field)) {
                $filledCount++;
            }
        }

        return round(($filledCount / count($fields)) * self::WEIGHT_COMPLETENESS);
    }

    /**
     * Score UTM parameter quality
     *
     * @param array $utmParameters
     * @return int
     */
    private function scoreUtmParameters(array $utmParameters): int
    {
        $requiredParams = ['utm_source', 'utm_medium', 'utm_campaign'];
        $optionalParams = ['utm_term', 'utm_content'];

        $score = 0;

        // Required parameters (6 points)
        foreach ($requiredParams as $param) {
            if (!empty($utmParameters[$param])) {
                $score += 2;
            }
        }

        // Optional parameters (4 points)
        foreach ($optionalParams as $param) {
            if (!empty($utmParameters[$param])) {
                $score += 2;
            }
        }

        return min($score, self::WEIGHT_UTM_DATA);
    }

    /**
     * Determine lead quality tier based on score
     *
     * @param int $score
     * @return string
     */
    public function getQualityTier(int $score): string
    {
        if ($score >= 80) {
            return 'hot';
        } elseif ($score >= 60) {
            return 'warm';
        } elseif ($score >= 40) {
            return 'cool';
        } else {
            return 'cold';
        }
    }

    /**
     * Check if lead should be auto-qualified based on score
     *
     * @param int $score
     * @return bool
     */
    public function shouldAutoQualify(int $score): bool
    {
        return $score >= 75;
    }
}
