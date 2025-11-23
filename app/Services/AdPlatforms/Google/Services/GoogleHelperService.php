<?php

namespace App\Services\AdPlatforms\Google\Services;

/**
 * Google Ads Helper Service
 *
 * Provides utility methods for:
 * - URL building
 * - ID extraction from resource names
 * - Status and type mapping
 * - Asset building
 *
 * Single Responsibility: Utility and helper operations
 */
class GoogleHelperService
{
    protected string $apiVersion;
    protected string $apiBaseUrl;
    protected string $customerId;

    public function __construct(string $apiVersion, string $apiBaseUrl, string $customerId)
    {
        $this->apiVersion = $apiVersion;
        $this->apiBaseUrl = $apiBaseUrl;
        $this->customerId = $customerId;
    }

    /**
     * Build URL with replacements
     */
    public function buildUrl(string $endpoint): string
    {
        $url = $this->apiBaseUrl . str_replace('{version}', $this->apiVersion, $endpoint);
        $url = str_replace('{customer_id}', $this->customerId, $url);

        return $url;
    }

    /**
     * Build ad text assets
     */
    public function buildAdTextAssets(array $texts): array
    {
        return array_map(fn($text) => ['text' => $text], $texts);
    }

    /**
     * Extract campaign ID from resource name
     */
    public function extractCampaignId(string $resourceName): string
    {
        preg_match('/campaigns\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract ad group ID from resource name
     */
    public function extractAdGroupId(string $resourceName): string
    {
        preg_match('/adGroups\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract ad ID from resource name
     */
    public function extractAdId(string $resourceName): string
    {
        preg_match('/ads\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract audience ID from resource name
     */
    public function extractAudienceId(string $resourceName): string
    {
        preg_match('/customAudiences\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract user list ID from resource name
     */
    public function extractUserListId(string $resourceName): string
    {
        preg_match('/userLists\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract bidding strategy ID from resource name
     */
    public function extractBiddingStrategyId(string $resourceName): string
    {
        preg_match('/biddingStrategies\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract conversion action ID from resource name
     */
    public function extractConversionActionId(string $resourceName): string
    {
        preg_match('/conversionActions\/(\d+)/', $resourceName, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Map campaign type
     */
    public function mapCampaignType(string $type): string
    {
        return match (strtolower($type)) {
            'search' => 'SEARCH',
            'display' => 'DISPLAY',
            'shopping' => 'SHOPPING',
            'video' => 'VIDEO',
            'performance_max' => 'PERFORMANCE_MAX',
            'discovery' => 'DISCOVERY',
            'app' => 'APP',
            'smart' => 'SMART',
            'local' => 'LOCAL',
            default => 'SEARCH',
        };
    }

    /**
     * Map keyword match type
     */
    public function mapKeywordMatchType(string $matchType): string
    {
        return match (strtolower($matchType)) {
            'exact' => 'EXACT',
            'phrase' => 'PHRASE',
            'broad' => 'BROAD',
            default => 'BROAD',
        };
    }

    /**
     * Map status
     */
    public function mapStatus(string $internalStatus): string
    {
        return match (strtolower($internalStatus)) {
            'active', 'enabled' => 'ENABLED',
            'paused' => 'PAUSED',
            'removed', 'deleted' => 'REMOVED',
            default => 'PAUSED',
        };
    }

    /**
     * Get available objectives
     */
    public function getAvailableObjectives(): array
    {
        return [
            'MAXIMIZE_CONVERSIONS',
            'TARGET_CPA',
            'TARGET_ROAS',
            'MAXIMIZE_CLICKS',
            'TARGET_IMPRESSION_SHARE',
            'TARGET_SPEND',
            'MANUAL_CPC',
            'ENHANCED_CPC',
        ];
    }

    /**
     * Get available campaign types
     */
    public function getAvailableCampaignTypes(): array
    {
        return [
            'SEARCH' => 'حملات البحث',
            'DISPLAY' => 'حملات الشبكة الإعلانية',
            'SHOPPING' => 'حملات التسوق',
            'VIDEO' => 'حملات الفيديو (YouTube)',
            'PERFORMANCE_MAX' => 'حملات الأداء الأقصى',
            'DISCOVERY' => 'حملات الاكتشاف',
            'APP' => 'حملات التطبيقات',
            'SMART' => 'الحملات الذكية',
            'LOCAL' => 'الحملات المحلية',
        ];
    }

    /**
     * Get available placements
     */
    public function getAvailablePlacements(): array
    {
        return [
            'google_search',
            'search_partners',
            'display_network',
            'youtube_videos',
            'youtube_search',
            'gmail',
            'discover',
        ];
    }
}
