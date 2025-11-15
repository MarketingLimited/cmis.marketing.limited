<?php

namespace App\Services\AdPlatforms\Twitter;

use App\Services\AdPlatforms\AbstractAdPlatform;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Twitter (X) Ads Platform Service
 *
 * Complete implementation of Twitter Ads API v11
 * Supports Promoted Tweets, Tailored Audiences, and comprehensive targeting
 *
 * @see https://developer.twitter.com/en/docs/twitter-ads-api
 */
class TwitterAdsPlatform extends AbstractAdPlatform
{
    protected string $accountId;

    protected function getConfig(): array
    {
        return [
            'api_version' => 'v11',
            'api_base_url' => 'https://ads-api.x.com',
        ];
    }

    protected function getPlatformName(): string
    {
        return 'twitter';
    }

    /**
     * Initialize platform with account ID from integration
     */
    public function __construct(\App\Models\Core\Integration $integration)
    {
        parent::__construct($integration);
        $this->accountId = $integration->metadata['account_id'] ?? '';
    }

    /**
     * Create a new Twitter campaign
     *
     * Supported objectives: TWEET_ENGAGEMENTS, FOLLOWERS, WEBSITE_CLICKS, AWARENESS,
     *                       APP_INSTALLS, APP_ENGAGEMENTS, VIDEO_VIEWS, REACH
     *
     * @param array $data Campaign data including:
     *   - name: Campaign name
     *   - objective: Campaign objective
     *   - funding_instrument_id: Funding source ID
     *   - daily_budget_amount_local_micro: Daily budget in micros
     *   - total_budget_amount_local_micro: Total budget in micros
     *   - start_time: Campaign start time (ISO 8601)
     *   - end_time: Campaign end time (ISO 8601, optional)
     * @return array Response with campaign details or error
     */
    public function createCampaign(array $data): array
    {
        try {
            $payload = [
                'name' => $data['name'],
                'funding_instrument_id' => $data['funding_instrument_id'],
                'objective' => $this->mapObjective($data['objective']),
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
            ];

            // Daily budget (in micros - $1.00 = 1,000,000 micros)
            if (isset($data['daily_budget'])) {
                $payload['daily_budget_amount_local_micro'] = (int) ($data['daily_budget'] * 1000000);
            }

            // Total budget
            if (isset($data['total_budget'])) {
                $payload['total_budget_amount_local_micro'] = (int) ($data['total_budget'] * 1000000);
            }

            // Start time
            if (isset($data['start_time'])) {
                $payload['start_time'] = $data['start_time'];
            }

            // End time
            if (isset($data['end_time'])) {
                $payload['end_time'] = $data['end_time'];
            }

            // Frequency cap
            if (isset($data['frequency_cap'])) {
                $payload['frequency_cap'] = $data['frequency_cap'];
            }

            // Standard delivery
            $payload['standard_delivery'] = $data['standard_delivery'] ?? true;

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/campaigns");
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'campaign_id' => $response['data']['id'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to create campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter createCampaign failed', [
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
     * Update an existing Twitter campaign
     *
     * @param string $externalId Campaign ID
     * @param array $data Updated campaign data
     * @return array Response with updated campaign details or error
     */
    public function updateCampaign(string $externalId, array $data): array
    {
        try {
            $payload = [];

            if (isset($data['name'])) {
                $payload['name'] = $data['name'];
            }

            if (isset($data['status'])) {
                $payload['status'] = $this->mapStatus($data['status']);
            }

            if (isset($data['daily_budget'])) {
                $payload['daily_budget_amount_local_micro'] = (int) ($data['daily_budget'] * 1000000);
            }

            if (isset($data['total_budget'])) {
                $payload['total_budget_amount_local_micro'] = (int) ($data['total_budget'] * 1000000);
            }

            if (isset($data['end_time'])) {
                $payload['end_time'] = $data['end_time'];
            }

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/campaigns/{$externalId}");
            $response = $this->makeRequest('PUT', $url, $payload);

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to update campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter updateCampaign failed', [
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
            $url = $this->buildUrl("/11/accounts/{$this->accountId}/campaigns/{$externalId}");
            $response = $this->makeRequest('GET', $url);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Campaign not found',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter getCampaign failed', [
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
     * Delete a Twitter campaign
     *
     * @param string $externalId Campaign ID
     * @return array Response indicating success or error
     */
    public function deleteCampaign(string $externalId): array
    {
        try {
            $url = $this->buildUrl("/11/accounts/{$this->accountId}/campaigns/{$externalId}");
            $response = $this->makeRequest('DELETE', $url);

            if (isset($response['data']['deleted']) && $response['data']['deleted']) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to delete campaign',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter deleteCampaign failed', [
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
            $params = [
                'count' => $filters['page_size'] ?? 100,
            ];

            if (isset($filters['status'])) {
                $params['campaign_ids'] = null; // Get all campaigns
                $params['with_deleted'] = false;
            }

            if (isset($filters['cursor'])) {
                $params['cursor'] = $filters['cursor'];
            }

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/campaigns");
            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'campaigns' => $response['data'],
                    'next_cursor' => $response['next_cursor'] ?? null,
                    'total_count' => $response['total_count'] ?? count($response['data']),
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to fetch campaigns',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter fetchCampaigns failed', [
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
     * Get campaign analytics for a specific date range
     *
     * @param string $externalId Campaign ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Metrics data or error
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        try {
            $params = [
                'entity' => 'CAMPAIGN',
                'entity_ids' => $externalId,
                'start_time' => $startDate . 'T00:00:00Z',
                'end_time' => $endDate . 'T23:59:59Z',
                'granularity' => 'DAY',
                'metric_groups' => implode(',', [
                    'ENGAGEMENT',
                    'BILLING',
                    'VIDEO',
                    'WEB_CONVERSION',
                    'MOBILE_CONVERSION',
                ]),
                'placement' => 'ALL_ON_TWITTER',
            ];

            $url = $this->buildUrl("/11/stats/accounts/{$this->accountId}");
            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['data'])) {
                $metrics = $this->aggregateMetrics($response['data']);

                return [
                    'success' => true,
                    'metrics' => $metrics,
                    'daily_breakdown' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to fetch metrics',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter getCampaignMetrics failed', [
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
     * Create a Line Item (Ad Group in Twitter terminology)
     *
     * @param string $campaignExternalId Parent campaign ID
     * @param array $data Line item data including:
     *   - name: Line item name
     *   - product_type: Product type (PROMOTED_TWEETS, etc.)
     *   - placements: Array of placements
     *   - objective: Optimization objective
     *   - bid_amount_local_micro: Bid amount in micros
     *   - targeting: Targeting criteria
     * @return array Response with line item ID or error
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $payload = [
                'campaign_id' => $campaignExternalId,
                'name' => $data['name'],
                'product_type' => $data['product_type'] ?? 'PROMOTED_TWEETS',
                'placements' => $data['placements'] ?? ['ALL_ON_TWITTER'],
                'objective' => $this->mapObjective($data['objective'] ?? 'TWEET_ENGAGEMENTS'),
                'bid_type' => $data['bid_type'] ?? 'AUTO',
                'automatically_select_bid' => $data['auto_bid'] ?? true,
            ];

            // Bid amount (if manual bidding)
            if (isset($data['bid_amount']) && $payload['automatically_select_bid'] === false) {
                $payload['bid_amount_local_micro'] = (int) ($data['bid_amount'] * 1000000);
            }

            // Daily budget for line item
            if (isset($data['daily_budget'])) {
                $payload['total_budget_amount_local_micro'] = (int) ($data['daily_budget'] * 1000000);
            }

            // Targeting (will be set via targeting criteria API separately)
            $payload['status'] = $this->mapStatus($data['status'] ?? 'PAUSED');

            // Start and end time
            if (isset($data['start_time'])) {
                $payload['start_time'] = $data['start_time'];
            }
            if (isset($data['end_time'])) {
                $payload['end_time'] = $data['end_time'];
            }

            // Optimization
            if (isset($data['optimization'])) {
                $payload['optimization'] = $data['optimization'];
            }

            // Charge by
            if (isset($data['charge_by'])) {
                $payload['charge_by'] = $data['charge_by'];
            }

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/line_items");
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['data']['id'])) {
                $lineItemId = $response['data']['id'];

                // Apply targeting criteria if provided
                if (isset($data['targeting'])) {
                    $this->applyTargeting($lineItemId, $data['targeting']);
                }

                return [
                    'success' => true,
                    'line_item_id' => $lineItemId,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to create line item',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter createAdSet failed', [
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
     * Create a Promoted Tweet
     *
     * @param string $adSetExternalId Parent line item ID
     * @param array $data Promoted tweet data including:
     *   - tweet_id: Existing tweet ID to promote
     *   - OR tweet_content: Content to create new tweet and promote
     * @return array Response with promoted tweet ID or error
     */
    public function createAd(string $adSetExternalId, array $data): array
    {
        try {
            // Option 1: Promote existing tweet
            if (isset($data['tweet_id'])) {
                return $this->promoteExistingTweet($adSetExternalId, $data['tweet_id']);
            }

            // Option 2: Create new tweet and promote it
            if (isset($data['tweet_content'])) {
                $tweetResult = $this->createTweet($data['tweet_content']);

                if (!$tweetResult['success']) {
                    return $tweetResult;
                }

                return $this->promoteExistingTweet($adSetExternalId, $tweetResult['tweet_id']);
            }

            return [
                'success' => false,
                'error' => 'Either tweet_id or tweet_content must be provided',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter createAd failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Promote an existing tweet
     *
     * @param string $lineItemId Line item ID
     * @param string $tweetId Tweet ID to promote
     * @return array Promoted tweet details or error
     */
    protected function promoteExistingTweet(string $lineItemId, string $tweetId): array
    {
        try {
            $payload = [
                'line_item_id' => $lineItemId,
                'tweet_ids' => [$tweetId],
            ];

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/promoted_tweets");
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['data'][0]['id'])) {
                return [
                    'success' => true,
                    'promoted_tweet_id' => $response['data'][0]['id'],
                    'tweet_id' => $tweetId,
                    'data' => $response['data'][0],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to promote tweet',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter promoteExistingTweet failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new tweet (requires Twitter API v2)
     *
     * @param array $content Tweet content
     * @return array Tweet ID or error
     */
    protected function createTweet(array $content): array
    {
        try {
            $payload = [
                'text' => $content['text'],
            ];

            // Media (images, videos, GIFs)
            if (isset($content['media_ids'])) {
                $payload['media'] = [
                    'media_ids' => $content['media_ids'],
                ];
            }

            // Poll
            if (isset($content['poll'])) {
                $payload['poll'] = $content['poll'];
            }

            // Reply settings
            if (isset($content['reply_settings'])) {
                $payload['reply_settings'] = $content['reply_settings'];
            }

            $url = 'https://api.twitter.com/2/tweets';
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'tweet_id' => $response['data']['id'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to create tweet',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter createTweet failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Apply targeting criteria to a line item
     *
     * @param string $lineItemId Line item ID
     * @param array $targeting Targeting options
     * @return array Success or error
     */
    protected function applyTargeting(string $lineItemId, array $targeting): array
    {
        try {
            $targetingCriteria = [];

            // Location targeting
            if (isset($targeting['locations'])) {
                foreach ($targeting['locations'] as $location) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'LOCATION',
                        'targeting_value' => $location,
                    ];
                }
            }

            // Gender targeting
            if (isset($targeting['genders'])) {
                foreach ($targeting['genders'] as $gender) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'GENDER',
                        'targeting_value' => $this->mapGender($gender),
                    ];
                }
            }

            // Age targeting
            if (isset($targeting['age_ranges'])) {
                foreach ($targeting['age_ranges'] as $ageRange) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'AGE',
                        'targeting_value' => $ageRange,
                    ];
                }
            }

            // Language targeting
            if (isset($targeting['languages'])) {
                foreach ($targeting['languages'] as $language) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'LANGUAGE',
                        'targeting_value' => $language,
                    ];
                }
            }

            // Interest targeting
            if (isset($targeting['interests'])) {
                foreach ($targeting['interests'] as $interest) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'INTEREST',
                        'targeting_value' => $interest,
                    ];
                }
            }

            // Keyword targeting
            if (isset($targeting['keywords'])) {
                foreach ($targeting['keywords'] as $keyword) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'KEYWORD',
                        'targeting_value' => $keyword,
                    ];
                }
            }

            // Follower lookalikes
            if (isset($targeting['followers_of_users'])) {
                foreach ($targeting['followers_of_users'] as $userId) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'FOLLOWERS_OF_USER',
                        'targeting_value' => $userId,
                    ];
                }
            }

            // Tailored audiences
            if (isset($targeting['tailored_audiences'])) {
                foreach ($targeting['tailored_audiences'] as $audienceId) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'TAILORED_AUDIENCE',
                        'targeting_value' => $audienceId,
                    ];
                }
            }

            // Device targeting
            if (isset($targeting['devices'])) {
                foreach ($targeting['devices'] as $device) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'DEVICE',
                        'targeting_value' => $device,
                    ];
                }
            }

            // Platform targeting
            if (isset($targeting['platforms'])) {
                foreach ($targeting['platforms'] as $platform) {
                    $targetingCriteria[] = [
                        'line_item_id' => $lineItemId,
                        'targeting_type' => 'PLATFORM',
                        'targeting_value' => $platform,
                    ];
                }
            }

            // Create targeting criteria
            if (!empty($targetingCriteria)) {
                $url = $this->buildUrl("/11/accounts/{$this->accountId}/targeting_criteria");
                $payload = ['targeting_criteria' => $targetingCriteria];
                $response = $this->makeRequest('POST', $url, $payload);

                if (isset($response['data'])) {
                    return [
                        'success' => true,
                        'targeting_criteria' => $response['data'],
                    ];
                }
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Twitter applyTargeting failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a Tailored Audience (Custom Audience)
     *
     * @param array $data Audience data including:
     *   - name: Audience name
     *   - list_type: EMAIL, TWITTER_ID, MOBILE_ADVERTISING_ID, etc.
     *   - users: Array of user identifiers
     * @return array Audience ID or error
     */
    public function createTailoredAudience(array $data): array
    {
        try {
            $payload = [
                'name' => $data['name'],
                'list_type' => $data['list_type'] ?? 'EMAIL',
            ];

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/tailored_audiences");
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['data']['id'])) {
                $audienceId = $response['data']['id'];

                // Upload users to audience if provided
                if (isset($data['users']) && !empty($data['users'])) {
                    $this->uploadUsersToTailoredAudience($audienceId, $data['users'], $data['list_type']);
                }

                return [
                    'success' => true,
                    'audience_id' => $audienceId,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to create tailored audience',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter createTailoredAudience failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload users to a tailored audience
     *
     * @param string $audienceId Audience ID
     * @param array $users User identifiers
     * @param string $listType List type
     * @return array Success or error
     */
    protected function uploadUsersToTailoredAudience(string $audienceId, array $users, string $listType): array
    {
        try {
            // Prepare user data based on list type
            $inputFilePath = $this->prepareTailoredAudienceFile($users, $listType);

            $url = $this->buildUrl("/11/accounts/{$this->accountId}/tailored_audiences/{$audienceId}/users");

            $payload = [
                'input_file_path' => $inputFilePath,
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to upload users',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter uploadUsersToTailoredAudience failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare tailored audience file
     *
     * @param array $users User list
     * @param string $listType List type
     * @return string File path
     */
    protected function prepareTailoredAudienceFile(array $users, string $listType): string
    {
        // This is a simplified implementation
        // In production, you would hash emails/phones according to Twitter's requirements
        $tempFile = tempnam(sys_get_temp_dir(), 'twitter_audience_');

        $data = implode("\n", array_map(function ($user) use ($listType) {
            if ($listType === 'EMAIL') {
                return hash('sha256', strtolower(trim($user)));
            }
            return $user;
        }, $users));

        file_put_contents($tempFile, $data);

        return $tempFile;
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
            'TWEET_ENGAGEMENTS' => 'تفاعلات التغريدة',
            'VIDEO_VIEWS' => 'مشاهدات الفيديو',
            'FOLLOWERS' => 'المتابعون',
            'APP_INSTALLS' => 'تثبيت التطبيق',
            'WEBSITE_CLICKS' => 'نقرات الموقع',
            'REACH' => 'الوصول',
            'APP_ENGAGEMENTS' => 'تفاعلات التطبيق',
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
            'ALL_ON_TWITTER' => 'Twitter (All Placements)',
            'PUBLISHER_NETWORK' => 'Twitter Audience Platform',
        ];
    }

    /**
     * Get available product types
     *
     * @return array List of product types
     */
    public function getAvailableProductTypes(): array
    {
        return [
            'PROMOTED_TWEETS' => 'Promoted Tweets',
            'PROMOTED_ACCOUNT' => 'Promoted Accounts',
        ];
    }

    /**
     * Sync account data from Twitter
     *
     * @return array Sync results or error
     */
    public function syncAccount(): array
    {
        try {
            $url = $this->buildUrl("/11/accounts/{$this->accountId}");
            $response = $this->makeRequest('GET', $url);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'account' => [
                        'id' => $response['data']['id'],
                        'name' => $response['data']['name'] ?? '',
                        'timezone' => $response['data']['timezone'] ?? '',
                        'timezone_switch_at' => $response['data']['timezone_switch_at'] ?? '',
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Failed to sync account',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter syncAccount failed', [
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
            // Twitter uses OAuth 1.0a which doesn't have refresh tokens
            // Access tokens don't expire unless explicitly revoked
            // This method is here for interface compatibility

            return [
                'success' => true,
                'message' => 'Twitter OAuth 1.0a tokens do not expire',
                'access_token' => $this->accessToken,
            ];
        } catch (\Exception $e) {
            Log::error('Twitter refreshAccessToken failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper: Aggregate metrics from daily breakdown
     *
     * @param array $data Daily metrics data
     * @return array Aggregated metrics
     */
    protected function aggregateMetrics(array $data): array
    {
        $totals = [
            'impressions' => 0,
            'clicks' => 0,
            'spend' => 0,
            'engagements' => 0,
            'retweets' => 0,
            'replies' => 0,
            'likes' => 0,
            'follows' => 0,
            'video_views' => 0,
            'video_completions' => 0,
            'url_clicks' => 0,
            'app_clicks' => 0,
        ];

        foreach ($data as $row) {
            $metrics = $row['id_data'][0]['metrics'] ?? [];

            $totals['impressions'] += $metrics['impressions'][0] ?? 0;
            $totals['clicks'] += $metrics['clicks'][0] ?? 0;
            $totals['spend'] += ($metrics['billed_charge_local_micro'][0] ?? 0) / 1000000;
            $totals['engagements'] += $metrics['engagements'][0] ?? 0;
            $totals['retweets'] += $metrics['retweets'][0] ?? 0;
            $totals['replies'] += $metrics['replies'][0] ?? 0;
            $totals['likes'] += $metrics['likes'][0] ?? 0;
            $totals['follows'] += $metrics['follows'][0] ?? 0;
            $totals['video_views'] += $metrics['video_views'][0] ?? 0;
            $totals['video_completions'] += $metrics['video_total_views'][0] ?? 0;
            $totals['url_clicks'] += $metrics['url_clicks'][0] ?? 0;
            $totals['app_clicks'] += $metrics['app_clicks'][0] ?? 0;
        }

        // Calculate rates
        $totals['ctr'] = $totals['impressions'] > 0 ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
        $totals['cpc'] = $totals['clicks'] > 0 ? $totals['spend'] / $totals['clicks'] : 0;
        $totals['cpm'] = $totals['impressions'] > 0 ? ($totals['spend'] / $totals['impressions']) * 1000 : 0;
        $totals['engagement_rate'] = $totals['impressions'] > 0 ? ($totals['engagements'] / $totals['impressions']) * 100 : 0;

        return $totals;
    }

    /**
     * Helper: Map objective to Twitter format
     */
    protected function mapObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'AWARENESS', 'REACH' => 'AWARENESS',
            'TWEET_ENGAGEMENTS', 'ENGAGEMENT' => 'TWEET_ENGAGEMENTS',
            'VIDEO_VIEWS' => 'VIDEO_VIEWS',
            'FOLLOWERS', 'FOLLOWER' => 'FOLLOWERS',
            'APP_INSTALLS', 'INSTALL' => 'APP_INSTALLS',
            'WEBSITE_CLICKS', 'TRAFFIC', 'LINK_CLICKS' => 'WEBSITE_CLICKS',
            'APP_ENGAGEMENTS' => 'APP_ENGAGEMENTS',
            default => 'TWEET_ENGAGEMENTS',
        };
    }

    /**
     * Helper: Map gender to Twitter format
     */
    protected function mapGender(string $gender): string
    {
        return match (strtoupper($gender)) {
            'MALE', 'M', '1' => '1',
            'FEMALE', 'F', '2' => '2',
            default => '0', // All genders
        };
    }

    /**
     * Helper: Map status to Twitter format
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
