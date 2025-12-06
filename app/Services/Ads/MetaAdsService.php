<?php

namespace App\Services\Ads;

use App\Models\Platform\PlatformConnection;
use App\Services\Connectors\Providers\MetaConnector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Meta Ads API Integration Service
 *
 * Handles campaign creation and management on Meta (Facebook, Instagram) advertising platform
 * Delegates to MetaConnector for actual API calls
 */
class MetaAdsService
{
    protected MetaConnector $connector;

    public function __construct(MetaConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get active Meta connection for organization
     *
     * @param string $orgId
     * @return PlatformConnection
     * @throws Exception
     */
    protected function getConnection(string $orgId): PlatformConnection
    {
        // Set RLS context
        DB::statement('SELECT cmis.init_transaction_context(?)', [$orgId]);

        $connection = PlatformConnection::forPlatform('meta')
            ->active()
            ->where('org_id', $orgId)
            ->first();

        if (!$connection) {
            throw new Exception('No active Meta connection found for organization');
        }

        if ($connection->isTokenExpired()) {
            throw new Exception('Meta access token has expired. Please reconnect.');
        }

        return $connection;
    }

    /**
     * Create a new advertising campaign on Meta
     *
     * @param string $orgId Organization ID
     * @param array $data Campaign data (name, objective, budget, targeting, etc.)
     * @return array Result with campaign_id
     */
    public function createCampaign(string $orgId, array $data): array
    {
        try {
            $connection = $this->getConnection($orgId);

            // Prepare campaign data for Meta API
            $campaignData = [
                'campaign_name' => $data['name'] ?? $data['campaign_name'],
                'objective' => strtoupper($data['objective']), // Meta requires uppercase
                'status' => $data['status'] ?? 'PAUSED', // Start paused by default
                'special_ad_categories' => $data['special_ad_categories'] ?? [],
            ];

            // Add ad set data if provided
            if (isset($data['budget']) || isset($data['targeting'])) {
                $campaignData['adset'] = [
                    'name' => $data['adset_name'] ?? ($data['name'] . ' - Ad Set'),
                    'daily_budget' => isset($data['daily_budget']) ? $data['daily_budget'] * 100 : null, // Convert to cents
                    'lifetime_budget' => isset($data['lifetime_budget']) ? $data['lifetime_budget'] * 100 : null,
                    'billing_event' => $data['billing_event'] ?? 'IMPRESSIONS',
                    'optimization_goal' => $data['optimization_goal'] ?? 'LINK_CLICKS',
                    'targeting' => $data['targeting'] ?? [],
                ];
            }

            // Create campaign via connector
            $integration = $this->connectionToIntegration($connection);
            $result = $this->connector->createAdCampaign($integration, $campaignData);

            Log::info('Meta campaign created successfully', [
                'org_id' => $orgId,
                'campaign_id' => $result['campaign_id'],
            ]);

            return [
                'success' => true,
                'campaign_id' => $result['campaign_id'],
                'adset_id' => $result['adset_id'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Meta campaign', [
                'org_id' => $orgId,
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
     * Get advertising campaign metrics/performance
     *
     * @param string $orgId Organization ID
     * @param string $campaignId Meta campaign ID
     * @param array $options Date range and other options
     * @return array Metrics data
     */
    public function getMetrics(string $orgId, string $campaignId, array $options = []): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $metrics = $this->connector->getAdCampaignMetrics($integration, $campaignId, $options);

            $metricsData = $metrics->first() ?? [];

            return [
                'campaign_id' => $campaignId,
                'impressions' => (int) ($metricsData['impressions'] ?? 0),
                'clicks' => (int) ($metricsData['clicks'] ?? 0),
                'conversions' => (int) ($metricsData['conversions'] ?? 0),
                'spend' => (float) ($metricsData['spend'] ?? 0),
                'ctr' => (float) ($metricsData['ctr'] ?? 0),
                'cpc' => (float) ($metricsData['cpc'] ?? 0),
                'cpm' => (float) ($metricsData['cpm'] ?? 0),
                'reach' => (int) ($metricsData['reach'] ?? 0),
                'roas' => $this->calculateRoas($metricsData),
            ];

        } catch (Exception $e) {
            Log::error('Failed to fetch Meta campaign metrics', [
                'org_id' => $orgId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get detailed ad metrics (alias for getMetrics)
     *
     * @param string $orgId Organization ID
     * @param string $campaignId Meta campaign ID
     * @param array $options Options
     * @return array Detailed metrics data
     */
    public function getAdMetrics(string $orgId, string $campaignId, array $options = []): array
    {
        return $this->getMetrics($orgId, $campaignId, $options);
    }

    /**
     * Update campaign budget
     *
     * @param string $orgId Organization ID
     * @param string $campaignId Meta campaign ID
     * @param float $budget New budget amount
     * @param string $budgetType 'daily' or 'lifetime'
     * @return array Result
     */
    public function updateBudget(string $orgId, string $campaignId, float $budget, string $budgetType = 'daily'): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $updates = [];
            if ($budgetType === 'daily') {
                $updates['daily_budget'] = $budget * 100; // Convert to cents
            } else {
                $updates['lifetime_budget'] = $budget * 100;
            }

            $this->connector->updateAdCampaign($integration, $campaignId, $updates);

            return [
                'success' => true,
                'data' => [
                    'id' => $campaignId,
                    'budget' => $budget,
                    'budget_type' => $budgetType,
                ],
            ];

        } catch (Exception $e) {
            Log::error('Failed to update Meta campaign budget', [
                'org_id' => $orgId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
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
     * @param string $orgId Organization ID
     * @param string $campaignId Meta campaign ID
     * @param string $status ACTIVE, PAUSED, or DELETED
     * @return array Result
     */
    public function updateStatus(string $orgId, string $campaignId, string $status): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $this->connector->updateAdCampaign($integration, $campaignId, [
                'status' => strtoupper($status),
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $campaignId,
                    'status' => $status,
                ],
            ];

        } catch (Exception $e) {
            Log::error('Failed to update Meta campaign status', [
                'org_id' => $orgId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync campaigns from Meta to CMIS
     *
     * @param string $orgId Organization ID
     * @return array Sync result
     */
    public function syncCampaigns(string $orgId): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $campaigns = $this->connector->syncCampaigns($integration, [
                'ad_account_id' => $connection->account_metadata['ad_account_id'] ?? null,
            ]);

            return [
                'success' => true,
                'campaigns_synced' => $campaigns->count(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to sync Meta campaigns', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate Meta Ads API credentials
     *
     * @param string $orgId Organization ID
     * @return bool True if valid
     */
    public function validateCredentials(string $orgId): bool
    {
        try {
            $connection = $this->getConnection($orgId);

            // Connection exists and is active, credentials are valid
            return $connection->isActive();

        } catch (Exception $e) {
            Log::warning('Meta credentials validation failed', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create ad creative for Meta campaigns.
     *
     * @param string $orgId Organization ID
     * @param array $data Creative data:
     *   - name: Creative name (required)
     *   - link_url: Landing page URL (for link ads)
     *   - message: Ad copy text
     *   - image_url: Image URL (will be uploaded)
     *   - call_to_action: CTA type (LEARN_MORE, SHOP_NOW, etc.)
     *   - object_story_spec: Direct story spec (advanced)
     * @return array Result with creative_id
     */
    public function createCreative(string $orgId, array $data): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $creativeData = [
                'name' => $data['name'] ?? 'Creative ' . now()->format('Y-m-d H:i'),
            ];

            // If image URL provided, upload it first
            if (!empty($data['image_url'])) {
                $imageResult = $this->connector->uploadAdImage($integration, $data['image_url']);
                if ($imageResult['success'] && !empty($imageResult['image_hash'])) {
                    $creativeData['image_hash'] = $imageResult['image_hash'];
                }
            }

            // Pass through other creative data
            if (!empty($data['link_url'])) {
                $creativeData['link_url'] = $data['link_url'];
            }
            if (!empty($data['message'])) {
                $creativeData['message'] = $data['message'];
            }
            if (!empty($data['call_to_action'])) {
                $creativeData['call_to_action'] = $data['call_to_action'];
            }
            if (!empty($data['object_story_spec'])) {
                $creativeData['object_story_spec'] = $data['object_story_spec'];
            }
            if (!empty($data['asset_feed_spec'])) {
                $creativeData['asset_feed_spec'] = $data['asset_feed_spec'];
            }
            if (!empty($data['page_id'])) {
                $creativeData['page_id'] = $data['page_id'];
            }

            $result = $this->connector->createAdCreative($integration, $creativeData);

            Log::info('Meta ad creative created successfully', [
                'org_id' => $orgId,
                'creative_id' => $result['creative_id'],
            ]);

            return [
                'success' => true,
                'creative_id' => $result['creative_id'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Meta ad creative', [
                'org_id' => $orgId,
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
     * Create lookalike audience on Meta.
     *
     * @param string $orgId Organization ID
     * @param array $data Audience data:
     *   - name: Audience name (required)
     *   - source_audience_id: Custom audience ID to base lookalike on (required)
     *   - country: Target country code (default: US)
     *   - ratio: Lookalike ratio 0.01 to 0.20 (default: 0.01 = 1%)
     *   - optimization_goal: NONE, REACH, or SIMILARITY
     * @return array Result with audience_id
     */
    public function createLookalikeAudience(string $orgId, array $data): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Audience name is required');
            }
            if (empty($data['source_audience_id'])) {
                throw new Exception('Source audience ID is required for lookalike audience');
            }

            $audienceData = [
                'name' => $data['name'],
                'source_audience_id' => $data['source_audience_id'],
                'country' => $data['country'] ?? 'US',
                'ratio' => $data['ratio'] ?? 0.01,
            ];

            if (!empty($data['optimization_goal'])) {
                $audienceData['optimization_goal'] = $data['optimization_goal'];
            }

            $result = $this->connector->createLookalikeAudience($integration, $audienceData);

            Log::info('Meta lookalike audience created successfully', [
                'org_id' => $orgId,
                'audience_id' => $result['audience_id'],
            ]);

            return [
                'success' => true,
                'audience_id' => $result['audience_id'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Meta lookalike audience', [
                'org_id' => $orgId,
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
     * Create custom audience on Meta.
     *
     * @param string $orgId Organization ID
     * @param array $data Audience data:
     *   - name: Audience name (required)
     *   - description: Audience description
     *   - subtype: CUSTOM, WEBSITE, APP, OFFLINE_CONVERSION
     *   - customer_file_source: USER_PROVIDED_ONLY or BOTH_USER_AND_PARTNER_PROVIDED
     *   - pixel_id: For website audiences
     *   - retention_days: For website audiences (default: 30)
     * @return array Result with audience_id
     */
    public function createCustomAudience(string $orgId, array $data): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            if (empty($data['name'])) {
                throw new Exception('Audience name is required');
            }

            $result = $this->connector->createCustomAudience($integration, $data);

            Log::info('Meta custom audience created successfully', [
                'org_id' => $orgId,
                'audience_id' => $result['audience_id'],
            ]);

            return [
                'success' => true,
                'audience_id' => $result['audience_id'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Meta custom audience', [
                'org_id' => $orgId,
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
     * Create ad set for Meta campaigns.
     *
     * @param string $orgId Organization ID
     * @param array $data Ad set data:
     *   - name: Ad set name (required)
     *   - campaign_id: Parent campaign ID (required)
     *   - daily_budget: Daily budget in dollars (converted to cents)
     *   - lifetime_budget: Lifetime budget in dollars (converted to cents)
     *   - billing_event: IMPRESSIONS, LINK_CLICKS, etc.
     *   - optimization_goal: LINK_CLICKS, REACH, CONVERSIONS, etc.
     *   - targeting: Targeting specification
     *   - start_time: Schedule start (ISO 8601 or timestamp)
     *   - end_time: Schedule end (ISO 8601 or timestamp)
     *   - bid_amount: Bid cap in dollars (converted to cents)
     *   - bid_strategy: LOWEST_COST_WITHOUT_CAP, COST_CAP, etc.
     * @return array Result with adset_id
     */
    public function createAdSet(string $orgId, array $data): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Ad set name is required');
            }
            if (empty($data['campaign_id'])) {
                throw new Exception('Campaign ID is required');
            }

            // Convert dollars to cents for budgets
            $adSetData = [
                'name' => $data['name'],
                'campaign_id' => $data['campaign_id'],
                'status' => $data['status'] ?? 'PAUSED',
                'billing_event' => $data['billing_event'] ?? 'IMPRESSIONS',
                'optimization_goal' => $data['optimization_goal'] ?? 'LINK_CLICKS',
            ];

            if (!empty($data['daily_budget'])) {
                $adSetData['daily_budget'] = intval($data['daily_budget'] * 100);
            }
            if (!empty($data['lifetime_budget'])) {
                $adSetData['lifetime_budget'] = intval($data['lifetime_budget'] * 100);
            }
            if (!empty($data['bid_amount'])) {
                $adSetData['bid_amount'] = intval($data['bid_amount'] * 100);
            }

            // Pass through other fields
            if (!empty($data['targeting'])) {
                $adSetData['targeting'] = $data['targeting'];
            }
            if (!empty($data['start_time'])) {
                $adSetData['start_time'] = $data['start_time'];
            }
            if (!empty($data['end_time'])) {
                $adSetData['end_time'] = $data['end_time'];
            }
            if (!empty($data['bid_strategy'])) {
                $adSetData['bid_strategy'] = $data['bid_strategy'];
            }
            if (!empty($data['promoted_object'])) {
                $adSetData['promoted_object'] = $data['promoted_object'];
            }

            $result = $this->connector->createAdSet($integration, $adSetData);

            Log::info('Meta ad set created successfully', [
                'org_id' => $orgId,
                'adset_id' => $result['adset_id'],
            ]);

            return [
                'success' => true,
                'adset_id' => $result['adset_id'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Meta ad set', [
                'org_id' => $orgId,
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
     * Create ad (links creative to ad set).
     *
     * @param string $orgId Organization ID
     * @param array $data Ad data:
     *   - name: Ad name (required)
     *   - adset_id: Ad set ID (required)
     *   - creative_id: Creative ID (required)
     *   - status: PAUSED or ACTIVE
     * @return array Result with ad_id
     */
    public function createAd(string $orgId, array $data): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            if (empty($data['name']) || empty($data['adset_id']) || empty($data['creative_id'])) {
                throw new Exception('Ad name, ad set ID, and creative ID are required');
            }

            $result = $this->connector->createAd($integration, $data);

            Log::info('Meta ad created successfully', [
                'org_id' => $orgId,
                'ad_id' => $result['ad_id'],
            ]);

            return [
                'success' => true,
                'ad_id' => $result['ad_id'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Meta ad', [
                'org_id' => $orgId,
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
     * Upload an image to the ad account for use in creatives.
     *
     * @param string $orgId Organization ID
     * @param string $imagePath Path to local image or URL
     * @return array Result with image_hash
     */
    public function uploadAdImage(string $orgId, string $imagePath): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $result = $this->connector->uploadAdImage($integration, $imagePath);

            Log::info('Meta ad image uploaded successfully', [
                'org_id' => $orgId,
                'image_hash' => $result['image_hash'],
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to upload Meta ad image', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync metrics for campaign
     *
     * @param string $orgId Organization ID
     * @param string $campaignId Meta campaign ID
     * @return array Sync result
     */
    public function syncMetrics(string $orgId, string $campaignId): array
    {
        try {
            $metrics = $this->getMetrics($orgId, $campaignId);

            if (isset($metrics['error'])) {
                return [
                    'success' => false,
                    'error' => $metrics['error'],
                ];
            }

            // Store metrics in unified_metrics table
            DB::table('cmis.unified_metrics')->updateOrInsert(
                [
                    'platform' => 'meta',
                    'entity_type' => 'campaign',
                    'entity_id' => $campaignId,
                    'metric_date' => now()->toDateString(),
                ],
                [
                    'org_id' => $orgId,
                    'metric_data' => json_encode([
                        'impressions' => $metrics['impressions'],
                        'clicks' => $metrics['clicks'],
                        'conversions' => $metrics['conversions'],
                        'spend' => $metrics['spend'],
                        'ctr' => $metrics['ctr'],
                        'cpc' => $metrics['cpc'],
                        'cpm' => $metrics['cpm'],
                        'reach' => $metrics['reach'],
                    ]),
                    'updated_at' => now(),
                ]
            );

            return [
                'success' => true,
                'data' => $metrics,
            ];

        } catch (Exception $e) {
            Log::error('Failed to sync Meta campaign metrics', [
                'org_id' => $orgId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync Meta account details
     *
     * @param string $orgId Organization ID
     * @return array Sync result
     */
    public function syncAccount(string $orgId): array
    {
        try {
            $connection = $this->getConnection($orgId);
            $integration = $this->connectionToIntegration($connection);

            $metrics = $this->connector->getAccountMetrics($integration);

            return [
                'success' => true,
                'data' => $metrics->toArray(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to sync Meta account', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate ROAS (Return on Ad Spend)
     *
     * @param array $metrics Metrics data
     * @return float ROAS value
     */
    protected function calculateRoas(array $metrics): float
    {
        $spend = (float) ($metrics['spend'] ?? 0);
        $revenue = 0;

        // Extract revenue from action_values if available
        if (isset($metrics['action_values'])) {
            foreach ($metrics['action_values'] as $action) {
                if (in_array($action['action_type'] ?? '', ['purchase', 'omni_purchase'])) {
                    $revenue += (float) ($action['value'] ?? 0);
                }
            }
        }

        if ($spend > 0) {
            return round($revenue / $spend, 2);
        }

        return 0.0;
    }

    /**
     * Convert PlatformConnection to Integration model format
     * (MetaConnector expects Integration model)
     *
     * @param PlatformConnection $connection
     * @return object Integration-like object
     */
    protected function connectionToIntegration(PlatformConnection $connection): object
    {
        return (object) [
            'integration_id' => $connection->connection_id,
            'org_id' => $connection->org_id,
            'platform' => $connection->platform,
            'access_token' => $connection->access_token, // Already decrypted via accessor
            'refresh_token' => $connection->refresh_token,
            'token_expires_at' => $connection->token_expires_at,
            'settings' => array_merge($connection->account_metadata ?? [], [
                'ad_account_id' => $connection->account_metadata['ad_account_id'] ?? $connection->account_id,
                'page_id' => $connection->account_metadata['page_id'] ?? null,
            ]),
        ];
    }
}
