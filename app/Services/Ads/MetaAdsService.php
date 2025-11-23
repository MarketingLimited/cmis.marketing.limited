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
     * Create ad creative (placeholder - not implemented in connector)
     *
     * @param string $orgId Organization ID
     * @param array $data Creative data
     * @return array Result
     */
    public function createCreative(string $orgId, array $data): array
    {
        // TODO: Implement creative creation via Meta connector
        Log::warning('MetaAdsService::createCreative not yet implemented', [
            'org_id' => $orgId,
            'data' => $data,
        ]);

        return [
            'success' => false,
            'error' => 'Creative creation not yet implemented. Use MetaConnector directly.',
        ];
    }

    /**
     * Create lookalike audience (placeholder - not implemented in connector)
     *
     * @param string $orgId Organization ID
     * @param array $data Audience data
     * @return array Result
     */
    public function createLookalikeAudience(string $orgId, array $data): array
    {
        // TODO: Implement audience creation via Meta connector
        Log::warning('MetaAdsService::createLookalikeAudience not yet implemented', [
            'org_id' => $orgId,
            'data' => $data,
        ]);

        return [
            'success' => false,
            'error' => 'Audience creation not yet implemented.',
        ];
    }

    /**
     * Create ad set (placeholder - not implemented in connector)
     *
     * @param string $orgId Organization ID
     * @param array $data Ad set data
     * @return array Result
     */
    public function createAdSet(string $orgId, array $data): array
    {
        // TODO: Implement ad set creation via Meta connector
        Log::warning('MetaAdsService::createAdSet not yet implemented', [
            'org_id' => $orgId,
            'data' => $data,
        ]);

        return [
            'success' => false,
            'error' => 'Ad set creation not yet implemented.',
        ];
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
