<?php

namespace App\Services\AdPlatforms\LinkedIn;

use App\Services\AdPlatforms\AbstractAdPlatform;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LinkedIn Ads Platform Service
 *
 * Complete implementation of LinkedIn Marketing API v2
 * Supports Sponsored Content, Lead Gen Forms, and comprehensive B2B targeting
 *
 * @see https://docs.microsoft.com/en-us/linkedin/marketing/
 */
class LinkedInAdsPlatform extends AbstractAdPlatform
{
    protected string $accountId;
    protected string $accountUrn;

    protected function getConfig(): array
    {
        return [
            'api_version' => 'v2',
            'api_base_url' => 'https://api.linkedin.com',
        ];
    }

    protected function getPlatformName(): string
    {
        return 'linkedin';
    }

    /**
     * Initialize platform with account ID from integration
     */
    public function __construct(\App\Models\Core\Integration $integration)
    {
        parent::__construct($integration);
        $this->accountId = $integration->metadata['account_id'] ?? '';
        $this->accountUrn = 'urn:li:sponsoredAccount:' . $this->accountId;
    }

    /**
     * Create a new LinkedIn campaign
     *
     * Supported objectives: BRAND_AWARENESS, WEBSITE_VISITS, ENGAGEMENT, VIDEO_VIEWS,
     *                       LEAD_GENERATION, WEBSITE_CONVERSIONS, JOB_APPLICANTS
     *
     * @param array $data Campaign data including:
     *   - name: Campaign name
     *   - objective: Campaign objective
     *   - cost_type: CPC or CPM
     *   - daily_budget: Daily budget in account currency
     *   - total_budget: Total campaign budget
     *   - start_date: Campaign start date (timestamp)
     *   - end_date: Campaign end date (timestamp, optional)
     * @return array Response with campaign details or error
     */
    public function createCampaign(array $data): array
    {
        try {
            $payload = [
                'account' => $this->accountUrn,
                'name' => $data['name'],
                'type' => 'SPONSORED_UPDATES',
                'costType' => $this->mapCostType($data['cost_type'] ?? 'CPC'),
                'objectiveType' => $this->mapObjective($data['objective']),
                'campaignGroup' => $this->accountUrn,
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'locale' => [
                    'country' => $data['locale_country'] ?? 'US',
                    'language' => $data['locale_language'] ?? 'en',
                ],
            ];

            // Daily budget
            if (isset($data['daily_budget'])) {
                $payload['dailyBudget'] = [
                    'amount' => (string) ($data['daily_budget'] * 100),
                    'currencyCode' => $data['currency'] ?? 'USD',
                ];
            }

            // Total budget
            if (isset($data['total_budget'])) {
                $payload['totalBudget'] = [
                    'amount' => (string) ($data['total_budget'] * 100),
                    'currencyCode' => $data['currency'] ?? 'USD',
                ];
            }

            // Run schedule
            if (isset($data['start_date'])) {
                $payload['runSchedule'] = [
                    'start' => $data['start_date'],
                ];

                if (isset($data['end_date'])) {
                    $payload['runSchedule']['end'] = $data['end_date'];
                }
            }

            // Audience expansion (for reach campaigns)
            if (isset($data['audience_expansion_enabled'])) {
                $payload['audienceExpansionEnabled'] = $data['audience_expansion_enabled'];
            }

            // Conversion tracking
            if (isset($data['conversion_rules'])) {
                $payload['associatedEntity'] = $data['conversion_rules'];
            }

            $url = $this->buildUrl('/rest/adCampaignsV2');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'campaign_id' => $this->extractIdFromUrn($response['id']),
                    'campaign_urn' => $response['id'],
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create campaign',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn createCampaign failed', [
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
     * Update an existing LinkedIn campaign
     *
     * @param string $externalId Campaign ID or URN
     * @param array $data Updated campaign data
     * @return array Response with updated campaign details or error
     */
    public function updateCampaign(string $externalId, array $data): array
    {
        try {
            $campaignUrn = $this->ensureUrn($externalId, 'sponsoredCampaign');
            $campaignId = $this->extractIdFromUrn($campaignUrn);

            $payload = [];

            if (isset($data['name'])) {
                $payload['name'] = $data['name'];
            }

            if (isset($data['status'])) {
                $payload['status'] = $this->mapStatus($data['status']);
            }

            if (isset($data['daily_budget'])) {
                $payload['dailyBudget'] = [
                    'amount' => (string) ($data['daily_budget'] * 100),
                    'currencyCode' => $data['currency'] ?? 'USD',
                ];
            }

            if (isset($data['total_budget'])) {
                $payload['totalBudget'] = [
                    'amount' => (string) ($data['total_budget'] * 100),
                    'currencyCode' => $data['currency'] ?? 'USD',
                ];
            }

            $url = $this->buildUrl("/rest/adCampaignsV2/{$campaignId}");
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to update campaign',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn updateCampaign failed', [
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
     * @param string $externalId Campaign ID or URN
     * @return array Campaign details or error
     */
    public function getCampaign(string $externalId): array
    {
        try {
            $campaignUrn = $this->ensureUrn($externalId, 'sponsoredCampaign');
            $campaignId = $this->extractIdFromUrn($campaignUrn);

            $url = $this->buildUrl("/rest/adCampaignsV2/{$campaignId}");
            $response = $this->makeRequest('GET', $url);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Campaign not found',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn getCampaign failed', [
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
     * Delete (archive) a LinkedIn campaign
     * Note: LinkedIn doesn't support permanent deletion, campaigns are archived
     *
     * @param string $externalId Campaign ID or URN
     * @return array Response indicating success or error
     */
    public function deleteCampaign(string $externalId): array
    {
        return $this->updateCampaignStatus($externalId, 'ARCHIVED');
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
                'q' => 'search',
                'search' => [
                    'account' => [
                        'values' => [$this->accountUrn],
                    ],
                ],
                'count' => $filters['page_size'] ?? 100,
                'start' => (($filters['page'] ?? 1) - 1) * ($filters['page_size'] ?? 100),
            ];

            if (isset($filters['status'])) {
                $params['search']['status'] = [
                    'values' => [$this->mapStatus($filters['status'])],
                ];
            }

            $url = $this->buildUrl('/rest/adCampaignsV2');
            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['elements'])) {
                return [
                    'success' => true,
                    'campaigns' => $response['elements'],
                    'pagination' => [
                        'total' => $response['paging']['total'] ?? 0,
                        'start' => $response['paging']['start'] ?? 0,
                        'count' => $response['paging']['count'] ?? 0,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch campaigns',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn fetchCampaigns failed', [
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
     * @param string $externalId Campaign ID or URN
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Metrics data or error
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        try {
            $campaignUrn = $this->ensureUrn($externalId, 'sponsoredCampaign');

            $params = [
                'q' => 'analytics',
                'pivot' => 'CAMPAIGN',
                'dateRange' => [
                    'start' => [
                        'year' => (int) date('Y', strtotime($startDate)),
                        'month' => (int) date('n', strtotime($startDate)),
                        'day' => (int) date('j', strtotime($startDate)),
                    ],
                    'end' => [
                        'year' => (int) date('Y', strtotime($endDate)),
                        'month' => (int) date('n', strtotime($endDate)),
                        'day' => (int) date('j', strtotime($endDate)),
                    ],
                ],
                'timeGranularity' => 'DAILY',
                'campaigns' => [$campaignUrn],
                'fields' => implode(',', [
                    'impressions',
                    'clicks',
                    'costInLocalCurrency',
                    'externalWebsiteConversions',
                    'externalWebsitePostClickConversions',
                    'externalWebsitePostViewConversions',
                    'oneClickLeads',
                    'videoViews',
                    'videoCompletions',
                    'reactions',
                    'comments',
                    'shares',
                    'follows',
                    'fullScreenPlays',
                ]),
            ];

            $url = $this->buildUrl('/rest/adAnalytics');
            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['elements'])) {
                $metrics = $this->aggregateMetrics($response['elements']);

                return [
                    'success' => true,
                    'metrics' => $metrics,
                    'daily_breakdown' => $response['elements'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch metrics',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn getCampaignMetrics failed', [
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
     * @param string $externalId Campaign ID or URN
     * @param string $status New status (ACTIVE, PAUSED, ARCHIVED)
     * @return array Response indicating success or error
     */
    public function updateCampaignStatus(string $externalId, string $status): array
    {
        return $this->updateCampaign($externalId, ['status' => $status]);
    }

    /**
     * Create a Creative (Ad Set in LinkedIn terminology)
     *
     * @param string $campaignExternalId Parent campaign ID or URN
     * @param array $data Creative data including:
     *   - type: SPONSORED_STATUS_UPDATE, SPONSORED_INMAILS, etc.
     *   - targeting: Targeting criteria
     *   - bid: Bid amount
     *   - creative_content: Content for the creative
     * @return array Response with creative ID or error
     */
    public function createAdSet(string $campaignExternalId, array $data): array
    {
        try {
            $campaignUrn = $this->ensureUrn($campaignExternalId, 'sponsoredCampaign');

            $payload = [
                'account' => $this->accountUrn,
                'campaign' => $campaignUrn,
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'type' => $data['type'] ?? 'SPONSORED_STATUS_UPDATE',
            ];

            // Targeting criteria
            if (isset($data['targeting'])) {
                $payload['targeting'] = $this->buildTargeting($data['targeting']);
            }

            // Bidding
            if (isset($data['bid_amount'])) {
                $payload['unitCost'] = [
                    'amount' => (string) ($data['bid_amount'] * 100),
                    'currencyCode' => $data['currency'] ?? 'USD',
                ];
            }

            // Creative reference (must be created separately)
            if (isset($data['creative_id'])) {
                $creativeUrn = $this->ensureUrn($data['creative_id'], 'sponsoredCreative');
                $payload['creative'] = $creativeUrn;
            }

            $url = $this->buildUrl('/rest/adCreativesV2');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'creative_id' => $this->extractIdFromUrn($response['id']),
                    'creative_urn' => $response['id'],
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create creative',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn createAdSet failed', [
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
     * Create Sponsored Content
     *
     * @param string $adSetExternalId Parent creative ID
     * @param array $data Sponsored content data including:
     *   - share_urn: LinkedIn share URN to sponsor
     *   - OR share_content: Content to create and sponsor
     * @return array Response with creative ID or error
     */
    public function createAd(string $adSetExternalId, array $data): array
    {
        try {
            // LinkedIn workflow: Create UGC Post -> Create Creative referencing the post

            // Option 1: Sponsor existing share
            if (isset($data['share_urn'])) {
                return $this->createSponsoredShare($data['share_urn'], $data);
            }

            // Option 2: Create new share and sponsor it
            if (isset($data['share_content'])) {
                $shareResult = $this->createShare($data['share_content']);

                if (!$shareResult['success']) {
                    return $shareResult;
                }

                return $this->createSponsoredShare($shareResult['share_urn'], $data);
            }

            return [
                'success' => false,
                'error' => 'Either share_urn or share_content must be provided',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn createAd failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a LinkedIn Share (UGC Post)
     *
     * @param array $content Share content
     * @return array Share URN or error
     */
    protected function createShare(array $content): array
    {
        try {
            $payload = [
                'author' => $this->accountUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $content['text'] ?? '',
                        ],
                        'shareMediaCategory' => $content['media_category'] ?? 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];

            // Add media if provided
            if (isset($content['media'])) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = $content['media'];
            }

            // Add article if provided
            if (isset($content['article'])) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $content['article']['url'],
                        'title' => [
                            'text' => $content['article']['title'] ?? '',
                        ],
                        'description' => [
                            'text' => $content['article']['description'] ?? '',
                        ],
                    ],
                ];
            }

            $url = $this->buildUrl('/rest/ugcPosts');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'share_urn' => $response['id'],
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create share',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn createShare failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create sponsored share creative
     *
     * @param string $shareUrn Share URN to sponsor
     * @param array $data Additional creative data
     * @return array Creative details or error
     */
    protected function createSponsoredShare(string $shareUrn, array $data): array
    {
        try {
            $payload = [
                'variables' => [
                    'data' => [
                        'com.linkedin.ads.SponsoredUpdateCreativeVariables' => [
                            'activity' => $shareUrn,
                        ],
                    ],
                ],
            ];

            // Call-to-action
            if (isset($data['call_to_action'])) {
                $payload['variables']['data']['com.linkedin.ads.SponsoredUpdateCreativeVariables']['directSponsoredContent'] = true;
                $payload['variables']['data']['com.linkedin.ads.SponsoredUpdateCreativeVariables']['callToAction'] = [
                    'labelType' => $data['call_to_action']['type'] ?? 'LEARN_MORE',
                    'landingPageUrl' => $data['call_to_action']['url'] ?? '',
                ];
            }

            $url = $this->buildUrl('/rest/creativeV2');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'creative_id' => $this->extractIdFromUrn($response['id']),
                    'creative_urn' => $response['id'],
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create sponsored creative',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn createSponsoredShare failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a Lead Gen Form
     *
     * @param array $data Form data including:
     *   - name: Form name
     *   - headline: Form headline
     *   - description: Form description
     *   - fields: Array of form fields
     *   - privacy_policy_url: Privacy policy URL
     * @return array Form ID or error
     */
    public function createLeadGenForm(array $data): array
    {
        try {
            $payload = [
                'account' => $this->accountUrn,
                'name' => $data['name'],
                'locale' => [
                    'country' => $data['locale_country'] ?? 'US',
                    'language' => $data['locale_language'] ?? 'en',
                ],
                'privacyPolicy' => [
                    'text' => $data['privacy_policy_text'] ?? 'Privacy Policy',
                    'url' => $data['privacy_policy_url'],
                ],
            ];

            // Form content
            $payload['content'] = [
                'headline' => $data['headline'] ?? '',
                'description' => $data['description'] ?? '',
                'submitButtonText' => $data['submit_button_text'] ?? 'Submit',
                'confirmationMessage' => $data['confirmation_message'] ?? 'Thank you!',
            ];

            // Form fields
            if (isset($data['fields'])) {
                $payload['fields'] = array_map(function ($field) {
                    return [
                        'type' => $field['type'], // EMAIL, FIRST_NAME, LAST_NAME, COMPANY, etc.
                        'required' => $field['required'] ?? true,
                    ];
                }, $data['fields']);
            } else {
                // Default fields
                $payload['fields'] = [
                    ['type' => 'FIRST_NAME', 'required' => true],
                    ['type' => 'LAST_NAME', 'required' => true],
                    ['type' => 'EMAIL', 'required' => true],
                ];
            }

            $url = $this->buildUrl('/rest/leadGenForms');
            $response = $this->makeRequest('POST', $url, $payload);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'form_id' => $this->extractIdFromUrn($response['id']),
                    'form_urn' => $response['id'],
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to create lead gen form',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn createLeadGenForm failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get lead form responses
     *
     * @param string $formId Lead gen form ID or URN
     * @return array Form responses or error
     */
    public function getLeadFormResponses(string $formId): array
    {
        try {
            $formUrn = $this->ensureUrn($formId, 'leadGenForm');

            $url = $this->buildUrl('/rest/leadFormResponses');
            $params = [
                'q' => 'owner',
                'owner' => $formUrn,
            ];

            $response = $this->makeRequest('GET', $url, $params);

            if (isset($response['elements'])) {
                return [
                    'success' => true,
                    'responses' => $response['elements'],
                    'total' => $response['paging']['total'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to fetch form responses',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn getLeadFormResponses failed', [
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
            'BRAND_AWARENESS' => 'الوعي بالعلامة التجارية',
            'WEBSITE_VISITS' => 'زيارات الموقع',
            'ENGAGEMENT' => 'التفاعل',
            'VIDEO_VIEWS' => 'مشاهدات الفيديو',
            'LEAD_GENERATION' => 'جذب العملاء المحتملين',
            'WEBSITE_CONVERSIONS' => 'تحويلات الموقع',
            'JOB_APPLICANTS' => 'المتقدمين للوظائف',
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
            'linkedin_feed' => 'LinkedIn Feed',
            'linkedin_right_rail' => 'LinkedIn Right Rail',
            'linkedin_messaging' => 'LinkedIn Messaging (InMail)',
        ];
    }

    /**
     * Get available ad formats
     *
     * @return array List of ad formats
     */
    public function getAvailableAdFormats(): array
    {
        return [
            'SPONSORED_STATUS_UPDATE' => 'Sponsored Content (Single Image)',
            'SPONSORED_VIDEO' => 'Sponsored Content (Video)',
            'SPONSORED_CAROUSEL' => 'Sponsored Content (Carousel)',
            'SPONSORED_INMAILS' => 'Message Ads (InMail)',
            'TEXT_AD' => 'Text Ads',
            'DYNAMIC_AD_FOLLOWER' => 'Dynamic Ads (Follower)',
            'DYNAMIC_AD_SPOTLIGHT' => 'Dynamic Ads (Spotlight)',
        ];
    }

    /**
     * Sync account data from LinkedIn
     *
     * @return array Sync results or error
     */
    public function syncAccount(): array
    {
        try {
            $url = $this->buildUrl("/rest/adAccounts/{$this->accountId}");
            $response = $this->makeRequest('GET', $url);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'account' => [
                        'id' => $this->extractIdFromUrn($response['id']),
                        'name' => $response['name'] ?? '',
                        'currency' => $response['currency'] ?? 'USD',
                        'status' => $response['status'] ?? '',
                        'type' => $response['type'] ?? '',
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to sync account',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn syncAccount failed', [
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

            $url = 'https://www.linkedin.com/oauth/v2/accessToken';

            $response = $this->makeRequest('POST', $url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
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
            Log::error('LinkedIn refreshAccessToken failed', [
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
     * @return array LinkedIn targeting structure
     */
    protected function buildTargeting(array $targeting): array
    {
        $criteria = [];

        // Location targeting (countries, regions, cities)
        if (isset($targeting['locations'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['locations'] = array_map(function ($loc) {
                return $this->ensureUrn($loc, 'geo');
            }, $targeting['locations']);
        }

        // Company size
        if (isset($targeting['company_sizes'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['companySizes'] = $targeting['company_sizes'];
        }

        // Industries
        if (isset($targeting['industries'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['industries'] = array_map(function ($ind) {
                return $this->ensureUrn($ind, 'industry');
            }, $targeting['industries']);
        }

        // Job titles
        if (isset($targeting['job_titles'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['titles'] = array_map(function ($title) {
                return $this->ensureUrn($title, 'title');
            }, $targeting['job_titles']);
        }

        // Job functions
        if (isset($targeting['job_functions'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['functions'] = array_map(function ($func) {
                return $this->ensureUrn($func, 'function');
            }, $targeting['job_functions']);
        }

        // Seniority levels
        if (isset($targeting['seniorities'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['seniorities'] = array_map(function ($sen) {
                return $this->ensureUrn($sen, 'seniority');
            }, $targeting['seniorities']);
        }

        // Skills
        if (isset($targeting['skills'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['skills'] = array_map(function ($skill) {
                return $this->ensureUrn($skill, 'skill');
            }, $targeting['skills']);
        }

        // Companies
        if (isset($targeting['companies'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['companies'] = array_map(function ($company) {
                return $this->ensureUrn($company, 'company');
            }, $targeting['companies']);
        }

        // Age ranges
        if (isset($targeting['age_ranges'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['ageRanges'] = $targeting['age_ranges'];
        }

        // Gender
        if (isset($targeting['genders'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['genders'] = $targeting['genders'];
        }

        // Matched audiences (custom audiences)
        if (isset($targeting['matched_audiences'])) {
            $criteria['includedTargetingFacets'] = $criteria['includedTargetingFacets'] ?? [];
            $criteria['includedTargetingFacets']['matchedAudiences'] = array_map(function ($aud) {
                return $this->ensureUrn($aud, 'audience');
            }, $targeting['matched_audiences']);
        }

        return $criteria;
    }

    /**
     * Helper: Aggregate metrics from daily breakdown
     *
     * @param array $elements Daily metrics elements
     * @return array Aggregated metrics
     */
    protected function aggregateMetrics(array $elements): array
    {
        $totals = [
            'impressions' => 0,
            'clicks' => 0,
            'spend' => 0,
            'conversions' => 0,
            'post_click_conversions' => 0,
            'post_view_conversions' => 0,
            'leads' => 0,
            'video_views' => 0,
            'video_completions' => 0,
            'reactions' => 0,
            'comments' => 0,
            'shares' => 0,
            'follows' => 0,
        ];

        foreach ($elements as $element) {
            $totals['impressions'] += $element['impressions'] ?? 0;
            $totals['clicks'] += $element['clicks'] ?? 0;
            $totals['spend'] += ($element['costInLocalCurrency'] ?? 0) / 100;
            $totals['conversions'] += $element['externalWebsiteConversions'] ?? 0;
            $totals['post_click_conversions'] += $element['externalWebsitePostClickConversions'] ?? 0;
            $totals['post_view_conversions'] += $element['externalWebsitePostViewConversions'] ?? 0;
            $totals['leads'] += $element['oneClickLeads'] ?? 0;
            $totals['video_views'] += $element['videoViews'] ?? 0;
            $totals['video_completions'] += $element['videoCompletions'] ?? 0;
            $totals['reactions'] += $element['reactions'] ?? 0;
            $totals['comments'] += $element['comments'] ?? 0;
            $totals['shares'] += $element['shares'] ?? 0;
            $totals['follows'] += $element['follows'] ?? 0;
        }

        // Calculate CTR and CPC
        $totals['ctr'] = $totals['impressions'] > 0 ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
        $totals['cpc'] = $totals['clicks'] > 0 ? $totals['spend'] / $totals['clicks'] : 0;
        $totals['cpm'] = $totals['impressions'] > 0 ? ($totals['spend'] / $totals['impressions']) * 1000 : 0;

        return $totals;
    }

    /**
     * Helper: Ensure value is a URN
     */
    protected function ensureUrn(string $value, string $type): string
    {
        if (str_starts_with($value, 'urn:li:')) {
            return $value;
        }

        return "urn:li:{$type}:{$value}";
    }

    /**
     * Helper: Extract ID from URN
     */
    protected function extractIdFromUrn(string $urn): string
    {
        $parts = explode(':', $urn);
        return end($parts);
    }

    /**
     * Helper: Map objective to LinkedIn format
     */
    protected function mapObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'AWARENESS', 'BRAND_AWARENESS' => 'BRAND_AWARENESS',
            'TRAFFIC', 'WEBSITE_VISITS' => 'WEBSITE_VISITS',
            'ENGAGEMENT' => 'ENGAGEMENT',
            'VIDEO_VIEWS' => 'VIDEO_VIEWS',
            'LEAD_GENERATION', 'LEADS' => 'LEAD_GENERATION',
            'CONVERSIONS', 'WEBSITE_CONVERSIONS' => 'WEBSITE_CONVERSIONS',
            'JOB_APPLICANTS', 'JOBS' => 'JOB_APPLICANTS',
            default => 'WEBSITE_VISITS',
        };
    }

    /**
     * Helper: Map cost type to LinkedIn format
     */
    protected function mapCostType(string $costType): string
    {
        return match (strtoupper($costType)) {
            'CPC', 'COST_PER_CLICK' => 'CPC',
            'CPM', 'COST_PER_IMPRESSION' => 'CPM',
            'CPS', 'COST_PER_SEND' => 'CPS',
            default => 'CPC',
        };
    }

    /**
     * Helper: Map status to LinkedIn format
     */
    protected function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE', 'ENABLED' => 'ACTIVE',
            'PAUSED', 'DISABLED' => 'PAUSED',
            'ARCHIVED', 'DELETED' => 'ARCHIVED',
            'DRAFT' => 'DRAFT',
            default => 'PAUSED',
        };
    }
}
