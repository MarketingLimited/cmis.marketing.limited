<?php

namespace App\Services\AdPlatforms\Google\Services;

/**
 * Google Ads Ad Service
 *
 * Handles ad creation and management:
 * - Create responsive search ads
 * - Create display ads
 * - Create video ads
 * - Add topic targeting to ads
 *
 * Single Responsibility: Ad creation and management
 */
class GoogleAdService
{
    protected string $customerId;
    protected GoogleHelperService $helper;
    protected $makeRequestCallback;

    public function __construct(
        string $customerId,
        GoogleHelperService $helper,
        callable $makeRequestCallback
    ) {
        $this->customerId = $customerId;
        $this->helper = $helper;
        $this->makeRequestCallback = $makeRequestCallback;
    }

    /**
     * Create ad
     */
    public function createAd(string $adGroupExternalId, array $data): array
    {
        try {
            $url = $this->helper->buildUrl('/customers/{customer_id}/adGroupAds:mutate');

            $ad = $this->buildAd($data);

            $adGroupAd = [
                'adGroup' => "customers/{$this->customerId}/adGroups/{$adGroupExternalId}",
                'status' => 'ENABLED',
                'ad' => $ad,
            ];

            $payload = [
                'operations' => [
                    ['create' => $adGroupAd],
                ],
            ];

            $response = ($this->makeRequestCallback)('POST', $url, $payload);

            return [
                'success' => true,
                'external_id' => $this->helper->extractAdId($response['results'][0]['resourceName']),
                'resource_name' => $response['results'][0]['resourceName'],
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build ad based on type
     */
    protected function buildAd(array $data): array
    {
        $type = $data['type'] ?? 'responsive_search_ad';

        return match ($type) {
            'responsive_search_ad' => $this->buildResponsiveSearchAd($data),
            'display_ad' => $this->buildDisplayAd($data),
            'video_ad' => $this->buildVideoAd($data),
            default => $this->buildResponsiveSearchAd($data),
        };
    }

    /**
     * Build Responsive Search Ad
     */
    protected function buildResponsiveSearchAd(array $data): array
    {
        return [
            'responsiveSearchAd' => [
                'headlines' => $this->helper->buildAdTextAssets($data['headlines']),
                'descriptions' => $this->helper->buildAdTextAssets($data['descriptions']),
                'path1' => $data['path1'] ?? '',
                'path2' => $data['path2'] ?? '',
            ],
            'finalUrls' => [$data['final_url']],
        ];
    }

    /**
     * Build Display Ad
     */
    protected function buildDisplayAd(array $data): array
    {
        return [
            'responsiveDisplayAd' => [
                'headlines' => $this->helper->buildAdTextAssets($data['headlines']),
                'descriptions' => $this->helper->buildAdTextAssets($data['descriptions']),
                'businessName' => $data['business_name'],
                'marketingImages' => $data['marketing_images'] ?? [],
                'logoImages' => $data['logo_images'] ?? [],
            ],
            'finalUrls' => [$data['final_url']],
        ];
    }

    /**
     * Build Video Ad
     */
    protected function buildVideoAd(array $data): array
    {
        return [
            'videoAd' => [
                'video' => [
                    'youtubeVideoId' => $data['youtube_video_id'],
                ],
            ],
            'finalUrls' => [$data['final_url'] ?? ''],
        ];
    }
}
