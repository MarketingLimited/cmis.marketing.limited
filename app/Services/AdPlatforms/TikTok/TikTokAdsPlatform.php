<?php

namespace App\Services\AdPlatforms\TikTok;

use App\Services\AdPlatforms\AbstractAdPlatform;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TikTok Ads Platform Service
 *
 * Complete implementation of TikTok Marketing API v1.3
 * Supports all campaign objectives, targeting options, and ad formats
 *
 * @see https://business-api.tiktok.com/portal/docs
 */
class TikTokAdsPlatform extends AbstractAdPlatform
{
    protected string $advertiserId;
    protected string $accessToken;

    protected function getConfig(): array
    {
        return [
            'api_version' => config('services.tiktok.api_version', 'v1.3'),
            'api_base_url' => config('services.tiktok.base_url', 'https://business-api.tiktok.com'),
        ];
    }

    protected function getPlatformName(): string
    {
        return 'tiktok';
    }

    /**
     * Initialize platform with advertiser ID from integration
     */
    public function __construct(\App\Models\Core\Integration $integration)
    {
        parent::__construct($integration);

        // Validate and extract advertiser ID
        if (empty($integration->metadata['advertiser_id'])) {
            throw new \InvalidArgumentException('TikTok advertiser_id not configured in integration metadata');
        }
        $this->advertiserId = $integration->metadata['advertiser_id'];

        // Extract and decrypt access token
        if (empty($integration->access_token)) {
            throw new \InvalidArgumentException('TikTok integration not authenticated');
        }
        $this->accessToken = decrypt($integration->access_token);

        // Check token expiration and refresh if needed
        $this->ensureValidToken();
    }

    /**
     * Override to add TikTok-specific Access-Token header
     */
    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Access-Token' => $this->accessToken,
        ]);
    }

    /**
     * Ensure token is valid, refresh if expired
     */
    protected function ensureValidToken(): void
    {
        if ($this->integration->token_expires_at &&
            $this->integration->token_expires_at->isPast()) {
            Log::info('TikTok token expired, refreshing', [
                'integration_id' => $this->integration->integration_id,
            ]);

            $result = $this->refreshAccessToken();
            if ($result['success']) {
                $this->accessToken = $result['access_token'];
            } else {
                throw new \Exception('Failed to refresh TikTok access token: ' . ($result['error'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Create a new TikTok campaign
     *
     * Supported objectives: REACH, TRAFFIC, APP_INSTALL, VIDEO_VIEWS, CONVERSIONS, LEAD_GENERATION
     *
     * @param array $data Campaign data including:
     *   - name: Campaign name
     *   - objective: Campaign objective
     *   - budget: Daily or lifetime budget
     *   - budget_mode: BUDGET_MODE_DAY or BUDGET_MODE_TOTAL
     *   - status: Campaign status (ENABLE, DISABLE)
     * @return array Response with campaign details or error
     */
    public function createCampaign(array $data): array
    {
        try {
            $payload = [
                'advertiser_id' => $this->advertiserId,
                'campaign_name' => $data['name'],
                'objective_type' => $this->mapObjective($data['objective']),
                'budget_mode' => $data['budget_mode'] ?? 'BUDGET_MODE_DAY',
                'budget' => $data['budget'] * 100, // Convert to cents
                'operation_status' => $this->mapStatus($data['status'] ?? 'DISABLE'),
            ];

            // Special budget handling for different budget modes
            if ($payload['budget_mode'] === 'BUDGET_MODE_DAY') {
                $payload['budget'] = $data['daily_budget'] * 100;
            } else {
                $payload['budget'] = $data['lifetime_budget'] * 100;
            }

            $url = $this->buildUrl('/open_api/v1.3/campaign/create/');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'campaign_id' => $response['data']['campaign_id'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create campaign',
                'code' => $response['code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('TikTok createCampaign failed', [
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
     * Update an existing TikTok campaign
     *
     * @param string $externalId Campaign ID
     * @param array $data Updated campaign data
     * @return array Response with updated campaign details or error
     */
    public function updateCampaign(string $externalId, array $data): array
    {
        try {
            $payload = [
                'advertiser_id' => $this->advertiserId,
                'campaign_id' => $externalId,
            ];

            if (isset($data['name'])) {
                $payload['campaign_name'] = $data['name'];
            }

            if (isset($data['budget'])) {
                $payload['budget'] = $data['budget'] * 100;
            }

            if (isset($data['status'])) {
                $payload['operation_status'] = $this->mapStatus($data['status']);
            }

            $url = $this->buildUrl('/open_api/v1.3/campaign/update/');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to update campaign',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok updateCampaign failed', [
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
            $url = $this->buildUrl('/open_api/v1.3/campaign/get/');
            $response = $this->makeRequest('GET', $url, [
                'advertiser_id' => $this->advertiserId,
                'campaign_ids' => json_encode([$externalId]),
            ]);

            if (isset($response['code']) && $response['code'] == 0 && !empty($response['data']['list'])) {
                return [
                    'success' => true,
                    'data' => $response['data']['list'][0],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Campaign not found',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok getCampaign failed', [
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
     * Delete (disable) a TikTok campaign
     * Note: TikTok doesn't support permanent deletion, only disabling
     *
     * @param string $externalId Campaign ID
     * @return array Response indicating success or error
     */
    public function deleteCampaign(string $externalId): array
    {
        return $this->updateCampaignStatus($externalId, 'DISABLE');
    }

    /**
     * Fetch all campaigns with optional filters
     *
     * @param array $filters Optional filters (status, objective_type, etc.)
     * @return array List of campaigns or error
     */
    public function fetchCampaigns(array $filters = []): array
    {
        try {
            $params = [
                'advertiser_id' => $this->advertiserId,
                'page' => $filters['page'] ?? 1,
                'page_size' => $filters['page_size'] ?? 100,
            ];

            if (isset($filters['status'])) {
                $params['primary_status'] = $this->mapStatus($filters['status']);
            }

            if (isset($filters['objective_type'])) {
                $params['objective_type'] = $this->mapObjective($filters['objective_type']);
            }

            $url = $this->buildUrl('/open_api/v1.3/campaign/get/');
            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'campaigns' => $response['data']['list'] ?? [],
                    'pagination' => [
                        'page' => $response['data']['page_info']['page'] ?? 1,
                        'page_size' => $response['data']['page_info']['page_size'] ?? 100,
                        'total_number' => $response['data']['page_info']['total_number'] ?? 0,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch campaigns',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok fetchCampaigns failed', [
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
     * Get campaign metrics for a specific date range
     *
     * @param string $externalId Campaign ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Metrics data or error
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        try {
            $url = $this->buildUrl('/open_api/v1.3/report/integrated/get/');

            $params = [
                'advertiser_id' => $this->advertiserId,
                'report_type' => 'BASIC',
                'data_level' => 'AUCTION_CAMPAIGN',
                'dimensions' => json_encode(['campaign_id']),
                'metrics' => json_encode([
                    'spend',
                    'impressions',
                    'clicks',
                    'ctr',
                    'cpc',
                    'cpm',
                    'conversion',
                    'cost_per_conversion',
                    'reach',
                    'video_views',
                    'video_watched_2s',
                    'video_watched_6s',
                    'average_video_play',
                    'engagement_rate',
                ]),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'filters' => json_encode([
                    [
                        'field' => 'campaign_id',
                        'operator' => 'IN',
                        'values' => [$externalId],
                    ],
                ]),
            ];

            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['code']) && $response['code'] == 0) {
                $metrics = $response['data']['list'][0]['metrics'] ?? [];

                return [
                    'success' => true,
                    'metrics' => [
                        'spend' => $metrics['spend'] ?? 0,
                        'impressions' => $metrics['impressions'] ?? 0,
                        'clicks' => $metrics['clicks'] ?? 0,
                        'ctr' => $metrics['ctr'] ?? 0,
                        'cpc' => $metrics['cpc'] ?? 0,
                        'cpm' => $metrics['cpm'] ?? 0,
                        'conversions' => $metrics['conversion'] ?? 0,
                        'cost_per_conversion' => $metrics['cost_per_conversion'] ?? 0,
                        'reach' => $metrics['reach'] ?? 0,
                        'video_views' => $metrics['video_views'] ?? 0,
                        'video_views_2s' => $metrics['video_watched_2s'] ?? 0,
                        'video_views_6s' => $metrics['video_watched_6s'] ?? 0,
                        'avg_video_play' => $metrics['average_video_play'] ?? 0,
                        'engagement_rate' => $metrics['engagement_rate'] ?? 0,
                    ],
                    'daily_breakdown' => $response['data']['list'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch metrics',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok getCampaignMetrics failed', [
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
     * @param string $status New status (ENABLE, DISABLE)
     * @return array Response indicating success or error
     */
    public function updateCampaignStatus(string $externalId, string $status): array
    {
        try {
            $url = $this->buildUrl('/open_api/v1.3/campaign/status/update/');

            $response = $this->makeRequest('POST', $url, [
                'advertiser_id' => $this->advertiserId,
                'campaign_id' => $externalId,
                'operation_status' => $this->mapStatus($status),
            ]);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to update status',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok updateCampaignStatus failed', [
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
     * Create an Ad Group (Ad Set in TikTok terminology)
     *
     * @param string $campaignExternalId Parent campaign ID
     * @param array $data Ad group data including:
     *   - name: Ad group name
     *   - placement_type: PLACEMENT_TYPE_AUTOMATIC or PLACEMENT_TYPE_NORMAL
     *   - placements: Array of placement IDs
     *   - budget: Daily budget
     *   - schedule_type: SCHEDULE_START_END or SCHEDULE_FROM_NOW
     *   - targeting: Targeting options (location, age, gender, interests, etc.)
     * @return array Response with ad group ID or error
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $payload = [
                'advertiser_id' => $this->advertiserId,
                'campaign_id' => $campaignExternalId,
                'adgroup_name' => $data['name'],
                'placement_type' => $data['placement_type'] ?? 'PLACEMENT_TYPE_AUTOMATIC',
                'budget_mode' => 'BUDGET_MODE_DAY',
                'budget' => $data['budget'] * 100,
                'schedule_type' => $data['schedule_type'] ?? 'SCHEDULE_FROM_NOW',
                'operation_status' => $this->mapStatus($data['status'] ?? 'DISABLE'),
            ];

            // Placements
            if (isset($data['placements'])) {
                $payload['placements'] = $data['placements'];
            } else {
                $payload['placements'] = ['PLACEMENT_TIKTOK'];
            }

            // Bidding and optimization
            if (isset($data['optimization_goal'])) {
                $payload['optimization_goal'] = $this->mapOptimizationGoal($data['optimization_goal']);
            }

            if (isset($data['bid_type'])) {
                $payload['bid_type'] = $this->mapBidType($data['bid_type']);
            }

            if (isset($data['bid_price'])) {
                $payload['bid_price'] = $data['bid_price'] * 100;
            }

            // Scheduling
            if (isset($data['schedule_start_time'])) {
                $payload['schedule_start_time'] = $data['schedule_start_time'];
            }
            if (isset($data['schedule_end_time'])) {
                $payload['schedule_end_time'] = $data['schedule_end_time'];
            }

            // Targeting
            $this->addTargeting($payload, $data['targeting'] ?? []);

            $url = $this->buildUrl('/open_api/v1.3/adgroup/create/');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'adgroup_id' => $response['data']['adgroup_id'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create ad group',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok createAdSet failed', [
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
     * Create a TikTok Ad
     *
     * @param string $adSetExternalId Parent ad group ID
     * @param array $data Ad data including:
     *   - name: Ad name
     *   - creative_type: CREATIVE_TYPE_IMAGE, CREATIVE_TYPE_VIDEO, etc.
     *   - image_ids: Array of image IDs (for image ads)
     *   - video_id: Video ID (for video ads)
     *   - ad_text: Ad text
     *   - call_to_action: CTA button
     *   - landing_page_url: Destination URL
     * @return array Response with ad ID or error
     */
    public function createAd(string $adSetExternalId, array $data): array
    {
        try {
            $payload = [
                'advertiser_id' => $this->advertiserId,
                'adgroup_id' => $adSetExternalId,
                'ad_name' => $data['name'],
                'ad_format' => $data['ad_format'] ?? 'SINGLE_VIDEO',
                'ad_text' => $data['ad_text'] ?? '',
                'call_to_action' => $data['call_to_action'] ?? 'LEARN_MORE',
            ];

            // Creative based on type
            if (isset($data['video_id'])) {
                $payload['creatives'] = [
                    [
                        'video_id' => $data['video_id'],
                        'ad_name' => $data['name'],
                        'ad_text' => $data['ad_text'] ?? '',
                        'call_to_action' => $data['call_to_action'] ?? 'LEARN_MORE',
                    ],
                ];
            } elseif (isset($data['image_ids'])) {
                $payload['creatives'] = array_map(function ($imageId) use ($data) {
                    return [
                        'image_id' => $imageId,
                        'ad_name' => $data['name'],
                        'ad_text' => $data['ad_text'] ?? '',
                        'call_to_action' => $data['call_to_action'] ?? 'LEARN_MORE',
                    ];
                }, $data['image_ids']);
            }

            // Landing page
            if (isset($data['landing_page_url'])) {
                $payload['landing_page_url'] = $data['landing_page_url'];
            }

            // Display name
            if (isset($data['display_name'])) {
                $payload['display_name'] = $data['display_name'];
            }

            // Tracking
            if (isset($data['tracking_pixel_id'])) {
                $payload['tracking_pixel_id'] = $data['tracking_pixel_id'];
            }

            $url = $this->buildUrl('/open_api/v1.3/ad/create/');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'ad_ids' => $response['data']['ad_ids'] ?? [],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create ad',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok createAd failed', [
                'error' => $e->getMessage(),
                'adgroup_id' => $adSetExternalId,
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
            'REACH' => 'الوصول',
            'TRAFFIC' => 'زيارات الموقع',
            'VIDEO_VIEWS' => 'مشاهدات الفيديو',
            'LEAD_GENERATION' => 'جذب العملاء المحتملين',
            'CONVERSIONS' => 'التحويلات',
            'APP_PROMOTION' => 'ترويج التطبيق',
            'ENGAGEMENT' => 'التفاعل',
            'PRODUCT_SALES' => 'مبيعات المنتج',
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
            'PLACEMENT_TIKTOK' => 'TikTok',
            'PLACEMENT_PANGLE' => 'Pangle',
            'PLACEMENT_GLOBAL_APP_BUNDLE' => 'Global App Bundle',
        ];
    }

    /**
     * Get available optimization goals
     *
     * @return array List of optimization goals
     */
    public function getAvailableOptimizationGoals(): array
    {
        return [
            'CLICK' => 'Clicks',
            'CONVERSION' => 'Conversions',
            'REACH' => 'Reach',
            'VIDEO_VIEW' => 'Video Views',
            'INSTALL' => 'App Installs',
            'LEAD' => 'Leads',
            'ENGAGEMENT' => 'Engagement',
        ];
    }

    /**
     * Get available bid types
     *
     * @return array List of bid types
     */
    public function getAvailableBidTypes(): array
    {
        return [
            'BID_TYPE_NO_BID' => 'Automatic Bidding',
            'BID_TYPE_CUSTOM' => 'Manual Bidding',
        ];
    }

    /**
     * Get available call-to-action options
     *
     * @return array List of CTAs
     */
    public function getAvailableCallToActions(): array
    {
        return [
            'LEARN_MORE' => 'Learn More',
            'SHOP_NOW' => 'Shop Now',
            'SIGN_UP' => 'Sign Up',
            'DOWNLOAD' => 'Download',
            'APPLY_NOW' => 'Apply Now',
            'BOOK_NOW' => 'Book Now',
            'CONTACT_US' => 'Contact Us',
            'GET_QUOTE' => 'Get Quote',
            'SUBSCRIBE' => 'Subscribe',
            'WATCH_NOW' => 'Watch Now',
        ];
    }

    /**
     * Sync account data from TikTok
     *
     * @return array Sync results or error
     */
    public function syncAccount(): array
    {
        try {
            // Get advertiser info
            $url = $this->buildUrl('/open_api/v1.3/advertiser/info/');
            $response = $this->makeRequest('GET', $url, [
                'advertiser_ids' => json_encode([$this->advertiserId]),
            ]);

            if (isset($response['code']) && $response['code'] == 0) {
                $advertiserInfo = $response['data']['list'][0] ?? null;

                if ($advertiserInfo) {
                    return [
                        'success' => true,
                        'account' => [
                            'id' => $advertiserInfo['advertiser_id'],
                            'name' => $advertiserInfo['advertiser_name'],
                            'currency' => $advertiserInfo['currency'],
                            'timezone' => $advertiserInfo['timezone'],
                            'status' => $advertiserInfo['status'],
                            'balance' => $advertiserInfo['balance'] / 100,
                        ],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to sync account',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok syncAccount failed', [
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
            // Get encrypted refresh token from integration
            if (empty($this->integration->refresh_token)) {
                return [
                    'success' => false,
                    'error' => 'No refresh token available',
                ];
            }

            $refreshToken = decrypt($this->integration->refresh_token);
            $url = 'https://business-api.tiktok.com/open_api/v1.3/oauth2/refresh_token/';

            // Use Http facade directly (not makeRequest) to avoid circular dependency
            $response = Http::asForm()->post($url, [
                'app_id' => config('services.tiktok.client_key'),
                'secret' => config('services.tiktok.client_secret'),
                'refresh_token' => $refreshToken,
            ]);

            if ($response->failed()) {
                throw new \Exception('Token refresh request failed: ' . $response->body());
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] == 0) {
                $newAccessToken = $data['data']['access_token'];
                $newRefreshToken = $data['data']['refresh_token'];
                $expiresIn = $data['data']['expires_in'];

                // Update integration with new encrypted tokens
                $this->integration->update([
                    'access_token' => encrypt($newAccessToken),
                    'refresh_token' => encrypt($newRefreshToken),
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'token_refreshed_at' => now(),
                ]);

                Log::info('TikTok token refreshed successfully', [
                    'integration_id' => $this->integration->integration_id,
                    'expires_at' => now()->addSeconds($expiresIn),
                ]);

                return [
                    'success' => true,
                    'access_token' => $newAccessToken,
                    'expires_in' => $expiresIn,
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Failed to refresh token',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok refreshAccessToken failed', [
                'error' => $e->getMessage(),
                'integration_id' => $this->integration->integration_id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload video creative
     *
     * @param string $videoPath Path to video file
     * @param array $options Upload options
     * @return array Video ID or error
     */
    public function uploadVideo(string $videoPath, array $options = []): array
    {
        try {
            if (!file_exists($videoPath)) {
                return [
                    'success' => false,
                    'error' => 'Video file not found',
                ];
            }

            $url = $this->buildUrl('/open_api/v1.3/file/video/ad/upload/');

            $response = $this->makeRequest('POST', $url, [
                'advertiser_id' => $this->advertiserId,
                'video_file' => new \CURLFile($videoPath),
                'video_signature' => md5_file($videoPath),
                'upload_type' => $options['upload_type'] ?? 'UPLOAD_BY_FILE',
            ]);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'video_id' => $response['data']['video_id'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to upload video',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok uploadVideo failed', [
                'error' => $e->getMessage(),
                'video_path' => $videoPath,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload image creative
     *
     * @param string $imagePath Path to image file
     * @param array $options Upload options
     * @return array Image ID or error
     */
    public function uploadImage(string $imagePath, array $options = []): array
    {
        try {
            if (!file_exists($imagePath)) {
                return [
                    'success' => false,
                    'error' => 'Image file not found',
                ];
            }

            $url = $this->buildUrl('/open_api/v1.3/file/image/ad/upload/');

            $response = $this->makeRequest('POST', $url, [
                'advertiser_id' => $this->advertiserId,
                'image_file' => new \CURLFile($imagePath),
                'image_signature' => md5_file($imagePath),
                'upload_type' => $options['upload_type'] ?? 'UPLOAD_BY_FILE',
            ]);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'image_id' => $response['data']['image_id'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to upload image',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok uploadImage failed', [
                'error' => $e->getMessage(),
                'image_path' => $imagePath,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get interest categories for targeting
     *
     * @return array List of interest categories
     */
    public function getInterestCategories(): array
    {
        try {
            $url = $this->buildUrl('/open_api/v1.3/targeting/category/get/');

            $response = $this->makeRequest('GET', $url, [
                'advertiser_id' => $this->advertiserId,
            ]);

            if (isset($response['code']) && $response['code'] == 0) {
                return [
                    'success' => true,
                    'categories' => $response['data']['list'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch interest categories',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok getInterestCategories failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper: Add targeting options to ad group payload
     *
     * @param array &$payload Ad group payload (passed by reference)
     * @param array $targeting Targeting options
     */
    protected function addTargeting(array &$payload, array $targeting): void
    {
        // Location targeting
        if (isset($targeting['locations'])) {
            $payload['location_ids'] = $targeting['locations'];
        }

        // Age targeting
        if (isset($targeting['age_groups'])) {
            $payload['age_groups'] = $targeting['age_groups'];
        }

        // Gender targeting
        if (isset($targeting['gender'])) {
            $payload['gender'] = $this->mapGender($targeting['gender']);
        }

        // Language targeting
        if (isset($targeting['languages'])) {
            $payload['languages'] = $targeting['languages'];
        }

        // Interest targeting
        if (isset($targeting['interests'])) {
            $payload['interest_category_ids'] = $targeting['interests'];
        }

        // Device targeting
        if (isset($targeting['operating_systems'])) {
            $payload['operating_systems'] = $targeting['operating_systems'];
        }

        // Minimum iOS version
        if (isset($targeting['min_ios_version'])) {
            $payload['min_ios_version'] = $targeting['min_ios_version'];
        }

        // Minimum Android version
        if (isset($targeting['min_android_version'])) {
            $payload['min_android_version'] = $targeting['min_android_version'];
        }

        // Network type
        if (isset($targeting['network_types'])) {
            $payload['network_types'] = $targeting['network_types'];
        }

        // Carrier targeting
        if (isset($targeting['carrier_ids'])) {
            $payload['carrier_ids'] = $targeting['carrier_ids'];
        }

        // Device model targeting
        if (isset($targeting['device_model_ids'])) {
            $payload['device_model_ids'] = $targeting['device_model_ids'];
        }
    }

    /**
     * Helper: Map objective to TikTok format
     */
    protected function mapObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'AWARENESS', 'REACH' => 'REACH',
            'TRAFFIC', 'LINK_CLICKS' => 'TRAFFIC',
            'VIDEO_VIEWS' => 'VIDEO_VIEWS',
            'LEAD_GENERATION', 'LEADS' => 'LEAD_GENERATION',
            'CONVERSIONS', 'CONVERSIONS' => 'CONVERSIONS',
            'APP_INSTALLS', 'APP_PROMOTION' => 'APP_PROMOTION',
            'ENGAGEMENT' => 'ENGAGEMENT',
            'PRODUCT_SALES' => 'PRODUCT_SALES',
            default => 'TRAFFIC',
        };
    }

    /**
     * Helper: Map optimization goal to TikTok format
     */
    protected function mapOptimizationGoal(string $goal): string
    {
        return match (strtoupper($goal)) {
            'CLICKS', 'CLICK' => 'CLICK',
            'CONVERSIONS', 'CONVERSION' => 'CONVERSION',
            'REACH' => 'REACH',
            'VIDEO_VIEWS', 'VIDEO_VIEW' => 'VIDEO_VIEW',
            'INSTALLS', 'INSTALL' => 'INSTALL',
            'LEADS', 'LEAD' => 'LEAD',
            'ENGAGEMENT' => 'ENGAGEMENT',
            default => 'CLICK',
        };
    }

    /**
     * Helper: Map bid type to TikTok format
     */
    protected function mapBidType(string $bidType): string
    {
        return match (strtoupper($bidType)) {
            'AUTO', 'AUTOMATIC', 'NO_BID' => 'BID_TYPE_NO_BID',
            'MANUAL', 'CUSTOM' => 'BID_TYPE_CUSTOM',
            default => 'BID_TYPE_NO_BID',
        };
    }

    /**
     * Helper: Map gender to TikTok format
     */
    protected function mapGender(string $gender): string
    {
        return match (strtoupper($gender)) {
            'MALE', 'M' => 'GENDER_MALE',
            'FEMALE', 'F' => 'GENDER_FEMALE',
            'ALL', 'UNLIMITED' => 'GENDER_UNLIMITED',
            default => 'GENDER_UNLIMITED',
        };
    }

    /**
     * Helper: Map status to TikTok format
     */
    protected function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE', 'ENABLED', 'ENABLE' => 'ENABLE',
            'PAUSED', 'DISABLED', 'DISABLE' => 'DISABLE',
            default => 'DISABLE',
        };
    }
}
