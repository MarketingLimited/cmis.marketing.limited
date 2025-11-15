<?php

namespace App\Services\AdPlatforms\Snapchat;

use App\Services\AdPlatforms\AbstractAdPlatform;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Snapchat Ads Platform Service
 *
 * Complete implementation of Snapchat Marketing API v1
 * Supports Snap Ads, Story Ads, and comprehensive targeting
 *
 * @see https://marketingapi.snapchat.com/docs/
 */
class SnapchatAdsPlatform extends AbstractAdPlatform
{
    protected string $adAccountId;

    protected function getConfig(): array
    {
        return [
            'api_version' => 'v1',
            'api_base_url' => 'https://adsapi.snapchat.com',
        ];
    }

    protected function getPlatformName(): string
    {
        return 'snapchat';
    }

    /**
     * Initialize platform with ad account ID from integration
     */
    public function __construct(\App\Models\Core\Integration $integration)
    {
        parent::__construct($integration);
        $this->adAccountId = $integration->metadata['ad_account_id'] ?? '';
    }

    /**
     * Create a new Snapchat campaign
     *
     * Supported objectives: AWARENESS, APP_INSTALLS, DRIVE_TRAFFIC, VIDEO_VIEWS, LEAD_GENERATION
     *
     * @param array $data Campaign data including:
     *   - name: Campaign name
     *   - objective: Campaign objective
     *   - status: Campaign status (ACTIVE, PAUSED)
     *   - daily_budget_micro: Daily budget in micros
     *   - lifetime_spend_cap_micro: Lifetime budget in micros
     *   - start_time: Campaign start time (ISO 8601)
     *   - end_time: Campaign end time (ISO 8601, optional)
     * @return array Response with campaign details or error
     */
    public function createCampaign(array $data): array
    {
        try {
            $payload = [
                'campaigns' => [
                    [
                        'ad_account_id' => $this->adAccountId,
                        'name' => $data['name'],
                        'objective' => $this->mapObjective($data['objective']),
                        'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                    ],
                ],
            ];

            // Daily budget (in micros - $1.00 = 1,000,000 micros)
            if (isset($data['daily_budget'])) {
                $payload['campaigns'][0]['daily_budget_micro'] = (int) ($data['daily_budget'] * 1000000);
            }

            // Lifetime budget
            if (isset($data['lifetime_budget'])) {
                $payload['campaigns'][0]['lifetime_spend_cap_micro'] = (int) ($data['lifetime_budget'] * 1000000);
            }

            // Start time
            if (isset($data['start_time'])) {
                $payload['campaigns'][0]['start_time'] = $data['start_time'];
            }

            // End time
            if (isset($data['end_time'])) {
                $payload['campaigns'][0]['end_time'] = $data['end_time'];
            }

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/campaigns');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['campaigns'][0]['campaign']['id'])) {
                return [
                    'success' => true,
                    'campaign_id' => $response['campaigns'][0]['campaign']['id'],
                    'data' => $response['campaigns'][0]['campaign'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to create campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat createCampaign failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing Snapchat campaign
     *
     * @param string $externalId Campaign ID
     * @param array $data Updated campaign data
     * @return array Response with updated campaign details or error
     */
    public function updateCampaign(string $externalId, array $data): array
    {
        try {
            $payload = [
                'campaigns' => [
                    [
                        'id' => $externalId,
                    ],
                ],
            ];

            if (isset($data['name'])) {
                $payload['campaigns'][0]['name'] = $data['name'];
            }

            if (isset($data['status'])) {
                $payload['campaigns'][0]['status'] = $this->mapStatus($data['status']);
            }

            if (isset($data['daily_budget'])) {
                $payload['campaigns'][0]['daily_budget_micro'] = (int) ($data['daily_budget'] * 1000000);
            }

            if (isset($data['lifetime_budget'])) {
                $payload['campaigns'][0]['lifetime_spend_cap_micro'] = (int) ($data['lifetime_budget'] * 1000000);
            }

            if (isset($data['end_time'])) {
                $payload['campaigns'][0]['end_time'] = $data['end_time'];
            }

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/campaigns');
            $response = $this->makeRequest('PUT', $url, $payload);

            if (isset($response['campaigns'][0]['campaign'])) {
                return [
                    'success' => true,
                    'data' => $response['campaigns'][0]['campaign'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to update campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat updateCampaign failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign details
     *
     * @param string $externalId Campaign ID
     * @return array Campaign details or error
     */
    public function getCampaign(string $externalId): array
    {
        try {
            $url = $this->buildUrl("/v1/campaigns/{$externalId}");
            $response = $this->makeRequest('GET', $url);

            if (isset($response['campaigns'][0]['campaign'])) {
                return [
                    'success' => true,
                    'data' => $response['campaigns'][0]['campaign'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Campaign not found',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat getCampaign failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a Snapchat campaign
     *
     * @param string $externalId Campaign ID
     * @return array Response indicating success or error
     */
    public function deleteCampaign(string $externalId): array
    {
        try {
            $url = $this->buildUrl("/v1/campaigns/{$externalId}");
            $response = $this->makeRequest('DELETE', $url);

            if (isset($response['request_status']) && $response['request_status'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to delete campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat deleteCampaign failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch all campaigns with optional filters
     *
     * @param array $filters Optional filters (status, etc.)
     * @return array List of campaigns or error
     */
    public function fetchCampaigns(array $filters = []): array
    {
        try {
            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/campaigns');
            $response = $this->makeRequest('GET', $url);

            if (isset($response['campaigns'])) {
                $campaigns = array_map(function ($item) {
                    return $item['campaign'];
                }, $response['campaigns']);

                return [
                    'success' => true,
                    'campaigns' => $campaigns,
                    'total_count' => count($campaigns),
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to fetch campaigns',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat fetchCampaigns failed', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign stats for a specific date range
     *
     * @param string $externalId Campaign ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Stats data or error
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        try {
            $params = [
                'granularity' => 'DAY',
                'start_time' => $startDate . 'T00:00:00.000-00:00',
                'end_time' => $endDate . 'T23:59:59.999-00:00',
                'fields' => implode(',', [
                    'impressions',
                    'swipes',
                    'spend',
                    'conversion_purchases',
                    'conversion_save',
                    'conversion_start_checkout',
                    'conversion_add_cart',
                    'conversion_view_content',
                    'conversion_add_billing',
                    'conversion_sign_ups',
                    'conversion_searches',
                    'screen_time_millis',
                    'quartile_1',
                    'quartile_2',
                    'quartile_3',
                    'view_completion',
                    'attachment_total_view_time_millis',
                ]),
            ];

            $url = $this->buildUrl("/v1/campaigns/{$externalId}/stats");
            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['timeseries_stats'])) {
                $metrics = $this->aggregateMetrics($response['timeseries_stats']);

                return [
                    'success' => true,
                    'metrics' => $metrics,
                    'daily_breakdown' => $response['timeseries_stats'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to fetch metrics',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat getCampaignMetrics failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $externalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update campaign status
     *
     * @param string $externalId Campaign ID
     * @param string $status New status (ACTIVE, PAUSED)
     * @return array Response indicating success or error
     */
    public function updateCampaignStatus(string $externalId, string $status): array
    {
        return $this->updateCampaign($externalId, ['status' => $status]);
    }

    /**
     * Create an Ad Squad (Ad Set in Snapchat terminology)
     *
     * @param string $campaignExternalId Parent campaign ID
     * @param array $data Ad squad data including:
     *   - name: Ad squad name
     *   - type: Ad squad type (SNAP_ADS, STORY_ADS, etc.)
     *   - placement: Placement type (SNAP_ADS, USER_STORIES, CONTENT)
     *   - bid_micro: Bid amount in micros
     *   - daily_budget_micro: Daily budget in micros
     *   - targeting: Targeting criteria
     * @return array Response with ad squad ID or error
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $payload = [
                'adsquads' => [
                    [
                        'campaign_id' => $campaignExternalId,
                        'name' => $data['name'],
                        'type' => $data['type'] ?? 'SNAP_ADS',
                        'placement' => $data['placement'] ?? 'SNAP_ADS',
                        'billing_event' => $data['billing_event'] ?? 'IMPRESSION',
                        'auto_bid' => $data['auto_bid'] ?? true,
                        'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                    ],
                ],
            ];

            // Bid amount (if not auto-bidding)
            if (!$payload['adsquads'][0]['auto_bid'] && isset($data['bid_amount'])) {
                $payload['adsquads'][0]['bid_micro'] = (int) ($data['bid_amount'] * 1000000);
            }

            // Daily budget
            if (isset($data['daily_budget'])) {
                $payload['adsquads'][0]['daily_budget_micro'] = (int) ($data['daily_budget'] * 1000000);
            }

            // Lifetime budget
            if (isset($data['lifetime_budget'])) {
                $payload['adsquads'][0]['lifetime_spend_cap_micro'] = (int) ($data['lifetime_budget'] * 1000000);
            }

            // Start and end time
            if (isset($data['start_time'])) {
                $payload['adsquads'][0]['start_time'] = $data['start_time'];
            }
            if (isset($data['end_time'])) {
                $payload['adsquads'][0]['end_time'] = $data['end_time'];
            }

            // Optimization goal
            if (isset($data['optimization_goal'])) {
                $payload['adsquads'][0]['optimization_goal'] = $data['optimization_goal'];
            }

            // Targeting
            if (isset($data['targeting'])) {
                $payload['adsquads'][0]['targeting'] = $this->buildTargeting($data['targeting']);
            }

            // Pixel ID for conversion tracking
            if (isset($data['pixel_id'])) {
                $payload['adsquads'][0]['pixel_id'] = $data['pixel_id'];
            }

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/adsquads');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['adsquads'][0]['adsquad']['id'])) {
                return [
                    'success' => true,
                    'adsquad_id' => $response['adsquads'][0]['adsquad']['id'],
                    'data' => $response['adsquads'][0]['adsquad'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to create ad squad',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat createAdSet failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignExternalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a Snap Ad
     *
     * @param string $adSetExternalId Parent ad squad ID
     * @param array $data Ad data including:
     *   - name: Ad name
     *   - creative_id: Creative ID (must be created first)
     *   - type: Ad type (SNAP_AD, STORY_AD, COLLECTION_AD)
     * @return array Response with ad ID or error
     */
    public function createAd(string $adSetExternalId, array $data): array
    {
        try {
            $payload = [
                'ads' => [
                    [
                        'ad_squad_id' => $adSetExternalId,
                        'name' => $data['name'],
                        'type' => $data['type'] ?? 'SNAP_AD',
                        'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                    ],
                ],
            ];

            // Creative reference
            if (isset($data['creative_id'])) {
                $payload['ads'][0]['creative_id'] = $data['creative_id'];
            } else {
                return [
                    'success' => false,
                    'error' => 'creative_id is required',
                ];
            }

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/ads');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['ads'][0]['ad']['id'])) {
                return [
                    'success' => true,
                    'ad_id' => $response['ads'][0]['ad']['id'],
                    'data' => $response['ads'][0]['ad'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to create ad',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat createAd failed', [
                'error' => $e->getMessage(),
                'adsquad_id' => $adSetExternalId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a Creative
     *
     * @param array $data Creative data including:
     *   - name: Creative name
     *   - type: Creative type (WEB_VIEW, DEEP_LINK, APP_INSTALL, etc.)
     *   - headline: Ad headline
     *   - brand_name: Brand name
     *   - shareable: Whether the ad is shareable
     *   - top_snap_media_id: Top Snap media ID
     *   - top_snap_crop_position: Crop position (MIDDLE, TOP_LEFT, etc.)
     * @return array Creative ID or error
     */
    public function createCreative(array $data): array
    {
        try {
            $payload = [
                'creatives' => [
                    [
                        'ad_account_id' => $this->adAccountId,
                        'name' => $data['name'],
                        'type' => $data['type'] ?? 'WEB_VIEW',
                        'headline' => $data['headline'] ?? '',
                        'brand_name' => $data['brand_name'] ?? '',
                        'shareable' => $data['shareable'] ?? true,
                    ],
                ],
            ];

            // Top Snap media
            if (isset($data['top_snap_media_id'])) {
                $payload['creatives'][0]['top_snap_media_id'] = $data['top_snap_media_id'];
                $payload['creatives'][0]['top_snap_crop_position'] = $data['top_snap_crop_position'] ?? 'MIDDLE';
            }

            // Call to action
            if (isset($data['call_to_action'])) {
                $payload['creatives'][0]['call_to_action'] = $data['call_to_action'];
            }

            // Web view URL
            if (isset($data['web_view_url'])) {
                $payload['creatives'][0]['web_view_url'] = $data['web_view_url'];
            }

            // Deep link
            if (isset($data['deep_link_uri'])) {
                $payload['creatives'][0]['deep_link_uri'] = $data['deep_link_uri'];
            }

            // App install
            if (isset($data['app_install_ios_url'])) {
                $payload['creatives'][0]['app_install_ios_url'] = $data['app_install_ios_url'];
            }
            if (isset($data['app_install_android_url'])) {
                $payload['creatives'][0]['app_install_android_url'] = $data['app_install_android_url'];
            }

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/creatives');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['creatives'][0]['creative']['id'])) {
                return [
                    'success' => true,
                    'creative_id' => $response['creatives'][0]['creative']['id'],
                    'data' => $response['creatives'][0]['creative'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to create creative',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat createCreative failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload media (image or video)
     *
     * @param string $filePath Path to media file
     * @param array $options Upload options
     * @return array Media ID or error
     */
    public function uploadMedia(string $filePath, array $options = []): array
    {
        try {
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'Media file not found',
                ];
            }

            $mediaType = $options['media_type'] ?? 'IMAGE';

            $payload = [
                'media' => [
                    [
                        'ad_account_id' => $this->adAccountId,
                        'type' => $mediaType,
                        'name' => $options['name'] ?? basename($filePath),
                    ],
                ],
            ];

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/media');

            // First, create media entity
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['media'][0]['media']['id'])) {
                $mediaId = $response['media'][0]['media']['id'];
                $uploadUrl = $response['media'][0]['media']['upload_url'] ?? null;

                // Upload file to upload URL if provided
                if ($uploadUrl) {
                    $this->uploadFileToUrl($uploadUrl, $filePath);
                }

                return [
                    'success' => true,
                    'media_id' => $mediaId,
                    'data' => $response['media'][0]['media'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to upload media',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat uploadMedia failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload file to a URL
     *
     * @param string $url Upload URL
     * @param string $filePath File path
     */
    protected function uploadFileToUrl(string $url, string $filePath): void
    {
        $file = fopen($filePath, 'r');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $file);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($file);
    }

    /**
     * Create an Audience Segment (Custom Audience)
     *
     * @param array $data Audience data including:
     *   - name: Audience name
     *   - description: Audience description
     *   - retention_in_days: Retention period
     *   - source_type: Source type (ENGAGEMENT, PIXEL, etc.)
     * @return array Audience segment ID or error
     */
    public function createAudienceSegment(array $data): array
    {
        try {
            $payload = [
                'segments' => [
                    [
                        'ad_account_id' => $this->adAccountId,
                        'name' => $data['name'],
                        'description' => $data['description'] ?? '',
                        'retention_in_days' => $data['retention_in_days'] ?? 180,
                        'source_type' => $data['source_type'] ?? 'ENGAGEMENT',
                    ],
                ],
            ];

            $url = $this->buildUrl('/v1/adaccounts/' . $this->adAccountId . '/segments');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['segments'][0]['segment']['id'])) {
                return [
                    'success' => true,
                    'segment_id' => $response['segments'][0]['segment']['id'],
                    'data' => $response['segments'][0]['segment'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to create audience segment',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat createAudienceSegment failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available campaign objectives
     *
     * @return array List of available objectives
     */
    public function getAvailableObjectives(): array
    {
        return [
            'AWARENESS' => 'الوعي',
            'APP_INSTALLS' => 'تثبيت التطبيق',
            'DRIVE_TRAFFIC' => 'زيارات الموقع',
            'VIDEO_VIEWS' => 'مشاهدات الفيديو',
            'LEAD_GENERATION' => 'جذب العملاء المحتملين',
        ];
    }

    /**
     * Get available placements
     *
     * @return array List of available placements
     */
    public function getAvailablePlacements(): array
    {
        return [
            'SNAP_ADS' => 'Snap Ads',
            'USER_STORIES' => 'Story Ads',
            'CONTENT' => 'Discover Content',
        ];
    }

    /**
     * Get available ad types
     *
     * @return array List of ad types
     */
    public function getAvailableAdTypes(): array
    {
        return [
            'SNAP_AD' => 'Snap Ad',
            'STORY_AD' => 'Story Ad',
            'COLLECTION_AD' => 'Collection Ad',
            'AR_LENS' => 'AR Lens',
            'FILTER' => 'Filter',
        ];
    }

    /**
     * Sync account data from Snapchat
     *
     * @return array Sync results or error
     */
    public function syncAccount(): array
    {
        try {
            $url = $this->buildUrl("/v1/adaccounts/{$this->adAccountId}");
            $response = $this->makeRequest('GET', $url);

            if (isset($response['adaccounts'][0]['adaccount'])) {
                $account = $response['adaccounts'][0]['adaccount'];

                return [
                    'success' => true,
                    'account' => [
                        'id' => $account['id'],
                        'name' => $account['name'] ?? '',
                        'currency' => $account['currency'] ?? 'USD',
                        'timezone' => $account['timezone'] ?? '',
                        'status' => $account['status'] ?? '',
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['request_status'] ?? 'Failed to sync account',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat syncAccount failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh OAuth access token
     *
     * @return array New access token or error
     */
    public function refreshAccessToken(): array
    {
        try {
            $refreshToken = $this->integration->metadata['refresh_token'] ?? '';

            if (empty($refreshToken)) {
                return [
                    'success' => false,
                    'error' => 'No refresh token available',
                ];
            }

            $url = 'https://accounts.snapchat.com/login/oauth2/access_token';

            $response = $this->makeRequest('POST', $url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.snapchat.client_id'),
                'client_secret' => config('services.snapchat.client_secret'),
            ]);

            if (isset($response['access_token'])) {
                $newAccessToken = $response['access_token'];
                $expiresIn = $response['expires_in'];

                // Update integration with new token
                $metadata = $this->integration->metadata;
                $metadata['access_token'] = $newAccessToken;

                if (isset($response['refresh_token'])) {
                    $metadata['refresh_token'] = $response['refresh_token'];
                }

                $metadata['expires_at'] = now()->addSeconds($expiresIn)->toDateTimeString();

                $this->integration->update(['metadata' => $metadata]);
                $this->accessToken = $newAccessToken;

                return [
                    'success' => true,
                    'access_token' => $newAccessToken,
                    'expires_in' => $expiresIn,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error_description'] ?? 'Failed to refresh token',
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat refreshAccessToken failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper: Build targeting criteria
     *
     * @param array $targeting Targeting options
     * @return array Snapchat targeting structure
     */
    protected function buildTargeting(array $targeting): array
    {
        $criteria = [
            'geos' => [],
            'demographics' => [],
            'devices' => [],
            'interests' => [],
        ];

        // Location targeting (countries)
        if (isset($targeting['countries'])) {
            foreach ($targeting['countries'] as $country) {
                $criteria['geos'][] = [
                    'country_code' => $country,
                ];
            }
        }

        // Age targeting
        if (isset($targeting['min_age']) || isset($targeting['max_age'])) {
            $criteria['demographics'][] = [
                'min_age' => $targeting['min_age'] ?? 13,
                'max_age' => $targeting['max_age'] ?? 65,
            ];
        }

        // Gender targeting
        if (isset($targeting['genders'])) {
            $criteria['demographics'][0]['gender'] = $targeting['genders'];
        }

        // Language targeting
        if (isset($targeting['languages'])) {
            $criteria['demographics'][0]['languages'] = $targeting['languages'];
        }

        // Device targeting
        if (isset($targeting['os_types'])) {
            $criteria['devices'] = array_map(function ($os) {
                return [
                    'os_type' => $os,
                ];
            }, $targeting['os_types']);
        }

        // Interest targeting
        if (isset($targeting['interests'])) {
            $criteria['interests'] = $targeting['interests'];
        }

        // Audience segments (custom audiences)
        if (isset($targeting['segments'])) {
            $criteria['segments'] = array_map(function ($segmentId) {
                return [
                    'segment_id' => $segmentId,
                ];
            }, $targeting['segments']);
        }

        // Location categories
        if (isset($targeting['location_categories'])) {
            $criteria['location_categories'] = $targeting['location_categories'];
        }

        return $criteria;
    }

    /**
     * Helper: Aggregate metrics from daily breakdown
     *
     * @param array $timeseriesStats Timeseries stats data
     * @return array Aggregated metrics
     */
    protected function aggregateMetrics(array $timeseriesStats): array
    {
        $totals = [
            'impressions' => 0,
            'swipes' => 0,
            'spend' => 0,
            'conversions' => 0,
            'video_views' => 0,
            'screen_time_millis' => 0,
        ];

        foreach ($timeseriesStats as $stat) {
            $stats = $stat['stats'] ?? [];

            $totals['impressions'] += $stats['impressions'] ?? 0;
            $totals['swipes'] += $stats['swipes'] ?? 0;
            $totals['spend'] += ($stats['spend'] ?? 0) / 1000000;
            $totals['conversions'] += ($stats['conversion_purchases'] ?? 0) +
                                     ($stats['conversion_sign_ups'] ?? 0);
            $totals['video_views'] += $stats['view_completion'] ?? 0;
            $totals['screen_time_millis'] += $stats['screen_time_millis'] ?? 0;
        }

        // Calculate rates
        $totals['swipe_up_rate'] = $totals['impressions'] > 0 ? ($totals['swipes'] / $totals['impressions']) * 100 : 0;
        $totals['cost_per_swipe'] = $totals['swipes'] > 0 ? $totals['spend'] / $totals['swipes'] : 0;
        $totals['cpm'] = $totals['impressions'] > 0 ? ($totals['spend'] / $totals['impressions']) * 1000 : 0;
        $totals['avg_screen_time_seconds'] = $totals['impressions'] > 0 ? ($totals['screen_time_millis'] / $totals['impressions']) / 1000 : 0;

        return $totals;
    }

    /**
     * Helper: Map objective to Snapchat format
     */
    protected function mapObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'AWARENESS', 'REACH' => 'AWARENESS',
            'APP_INSTALLS', 'INSTALL' => 'APP_INSTALLS',
            'DRIVE_TRAFFIC', 'TRAFFIC', 'WEBSITE_CLICKS' => 'DRIVE_TRAFFIC',
            'VIDEO_VIEWS' => 'VIDEO_VIEWS',
            'LEAD_GENERATION', 'LEADS' => 'LEAD_GENERATION',
            default => 'AWARENESS',
        };
    }

    /**
     * Helper: Map status to Snapchat format
     */
    protected function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE', 'ENABLED' => 'ACTIVE',
            'PAUSED', 'DISABLED' => 'PAUSED',
            default => 'PAUSED',
        };
    }
}
