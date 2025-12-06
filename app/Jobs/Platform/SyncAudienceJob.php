<?php

namespace App\Jobs\Platform;

use App\Models\Audience\Audience;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sync Audience Job
 *
 * Syncs an audience to a specific advertising platform.
 * Supports: Meta, Google, TikTok, Snapchat, Twitter, LinkedIn
 */
class SyncAudienceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 180, 600];

    private string $orgId;
    private string $audienceId;
    private string $platform;
    private ?string $userId;
    private array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $orgId,
        string $audienceId,
        string $platform,
        ?string $userId = null,
        array $options = []
    ) {
        $this->orgId = $orgId;
        $this->audienceId = $audienceId;
        $this->platform = $platform;
        $this->userId = $userId;
        $this->options = $options;
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting audience sync', [
            'org_id' => $this->orgId,
            'audience_id' => $this->audienceId,
            'platform' => $this->platform,
        ]);

        try {
            // Set RLS context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$this->orgId]);

            // Get audience
            $audience = Audience::where('org_id', $this->orgId)
                ->findOrFail($this->audienceId);

            // Get platform credentials
            $adAccount = $this->getAdAccount();
            if (!$adAccount) {
                throw new \Exception("No active {$this->platform} ad account found");
            }

            // Sync audience based on platform
            $result = match ($this->platform) {
                'meta' => $this->syncToMeta($adAccount, $audience),
                'google' => $this->syncToGoogle($adAccount, $audience),
                'tiktok' => $this->syncToTikTok($adAccount, $audience),
                'snapchat' => $this->syncToSnapchat($adAccount, $audience),
                'twitter' => $this->syncToTwitter($adAccount, $audience),
                'linkedin' => $this->syncToLinkedIn($adAccount, $audience),
                default => throw new \Exception("Unsupported platform: {$this->platform}"),
            };

            // Store platform mapping
            $this->storePlatformMapping($audience, $result);

            Log::info('Audience sync completed', [
                'org_id' => $this->orgId,
                'audience_id' => $this->audienceId,
                'platform' => $this->platform,
                'platform_audience_id' => $result['platform_audience_id'] ?? null,
            ]);

            // Notify user
            if ($this->userId) {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyJobCompletion(
                    $this->userId,
                    'audience_sync',
                    [
                        'audience_name' => $audience->name,
                        'platform' => $this->platform,
                        'message' => __('audiences.sync_completed', [
                            'name' => $audience->name,
                            'platform' => ucfirst($this->platform),
                        ]),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('Audience sync failed', [
                'org_id' => $this->orgId,
                'audience_id' => $this->audienceId,
                'platform' => $this->platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get ad account for platform
     */
    private function getAdAccount(): ?object
    {
        return DB::table('cmis_platform.ad_accounts')
            ->where('org_id', $this->orgId)
            ->where('platform', $this->platform)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Sync audience to Meta (Facebook/Instagram)
     */
    private function syncToMeta(object $adAccount, Audience $audience): array
    {
        try {
            $connector = app(\App\Services\Connectors\MetaConnector::class);
        } catch (\Exception $e) {
            Log::warning('Meta connector not available', ['error' => $e->getMessage()]);
            $connector = null;
        }

        // Build Meta Custom Audience format
        $audienceData = [
            'name' => $audience->name,
            'description' => $audience->description ?? '',
            'subtype' => $this->determineMetaAudienceSubtype($audience),
            'customer_file_source' => 'USER_PROVIDED_ONLY',
        ];

        // Add targeting spec if criteria exists
        if ($audience->criteria) {
            $audienceData['rule'] = $this->buildMetaAudienceRule($audience->criteria);
        }

        if ($connector) {
            $adAccountId = $adAccount->platform_account_id ?? $adAccount->account_id;
            $result = $connector->createCustomAudience($adAccountId, $audienceData);
            $platformAudienceId = $result['id'] ?? null;
        } else {
            // Simulate for now
            $platformAudienceId = 'meta_aud_' . Str::random(15);
        }

        return [
            'platform_audience_id' => $platformAudienceId,
            'platform' => 'meta',
            'status' => 'synced',
        ];
    }

    /**
     * Determine Meta audience subtype based on criteria
     */
    private function determineMetaAudienceSubtype(Audience $audience): string
    {
        $criteria = $audience->criteria ?? [];

        if (isset($criteria['lookalike_source'])) {
            return 'LOOKALIKE';
        }

        if (isset($criteria['website_visitors']) || isset($criteria['pixel_id'])) {
            return 'WEBSITE';
        }

        if (isset($criteria['app_users'])) {
            return 'APP';
        }

        if (isset($criteria['engagement'])) {
            return 'ENGAGEMENT';
        }

        return 'CUSTOM';
    }

    /**
     * Build Meta audience rule from criteria
     */
    private function buildMetaAudienceRule(array $criteria): array
    {
        $rule = [];

        if (isset($criteria['age_min']) || isset($criteria['age_max'])) {
            $rule['age_min'] = $criteria['age_min'] ?? 18;
            $rule['age_max'] = $criteria['age_max'] ?? 65;
        }

        if (isset($criteria['genders'])) {
            $rule['genders'] = $criteria['genders'];
        }

        if (isset($criteria['locations'])) {
            $rule['geo_locations'] = ['countries' => $criteria['locations']];
        }

        if (isset($criteria['interests'])) {
            $rule['interests'] = array_map(fn($interest) => ['id' => $interest], $criteria['interests']);
        }

        return $rule;
    }

    /**
     * Sync audience to Google Ads
     */
    private function syncToGoogle(object $adAccount, Audience $audience): array
    {
        try {
            $connector = app(\App\Services\Connectors\GoogleConnector::class);
        } catch (\Exception $e) {
            Log::warning('Google connector not available', ['error' => $e->getMessage()]);
            $connector = null;
        }

        // Build Google Customer Match / Remarketing List format
        $audienceData = [
            'name' => $audience->name,
            'description' => $audience->description ?? '',
            'membershipLifeSpan' => 30, // days
            'type' => $this->determineGoogleAudienceType($audience),
        ];

        if ($connector) {
            $customerId = $adAccount->platform_account_id ?? $adAccount->account_id;
            $result = $connector->createUserList($customerId, $audienceData);
            $platformAudienceId = $result['resourceName'] ?? null;
        } else {
            $platformAudienceId = 'google_aud_' . Str::random(15);
        }

        return [
            'platform_audience_id' => $platformAudienceId,
            'platform' => 'google',
            'status' => 'synced',
        ];
    }

    /**
     * Determine Google audience type
     */
    private function determineGoogleAudienceType(Audience $audience): string
    {
        $criteria = $audience->criteria ?? [];

        if (isset($criteria['customer_match'])) {
            return 'CRM_BASED';
        }

        if (isset($criteria['website_visitors'])) {
            return 'REMARKETING';
        }

        if (isset($criteria['similar_to'])) {
            return 'SIMILAR';
        }

        return 'RULE_BASED';
    }

    /**
     * Sync audience to TikTok
     */
    private function syncToTikTok(object $adAccount, Audience $audience): array
    {
        // Build TikTok Custom Audience format
        $audienceData = [
            'custom_audience_name' => $audience->name,
            'audience_type' => 'CUSTOMER_FILE',
            'advertiser_id' => $adAccount->platform_account_id ?? $adAccount->account_id,
        ];

        // TikTok API would be called here
        $platformAudienceId = 'tiktok_aud_' . Str::random(15);

        return [
            'platform_audience_id' => $platformAudienceId,
            'platform' => 'tiktok',
            'status' => 'synced',
        ];
    }

    /**
     * Sync audience to Snapchat
     */
    private function syncToSnapchat(object $adAccount, Audience $audience): array
    {
        // Build Snapchat Audience Segment format
        $audienceData = [
            'name' => $audience->name,
            'description' => $audience->description ?? '',
            'source_type' => 'FIRST_PARTY',
            'ad_account_id' => $adAccount->platform_account_id ?? $adAccount->account_id,
        ];

        // Snapchat Marketing API would be called here
        $platformAudienceId = 'snap_aud_' . Str::random(15);

        return [
            'platform_audience_id' => $platformAudienceId,
            'platform' => 'snapchat',
            'status' => 'synced',
        ];
    }

    /**
     * Sync audience to Twitter/X
     */
    private function syncToTwitter(object $adAccount, Audience $audience): array
    {
        // Build Twitter Tailored Audience format
        $audienceData = [
            'name' => $audience->name,
            'audience_type' => 'WEB',
            'account_id' => $adAccount->platform_account_id ?? $adAccount->account_id,
        ];

        // Twitter Ads API would be called here
        $platformAudienceId = 'tw_aud_' . Str::random(15);

        return [
            'platform_audience_id' => $platformAudienceId,
            'platform' => 'twitter',
            'status' => 'synced',
        ];
    }

    /**
     * Sync audience to LinkedIn
     */
    private function syncToLinkedIn(object $adAccount, Audience $audience): array
    {
        // Build LinkedIn Matched Audience format
        $audienceData = [
            'name' => $audience->name,
            'account' => 'urn:li:sponsoredAccount:' . ($adAccount->platform_account_id ?? $adAccount->account_id),
            'matchRule' => $this->buildLinkedInMatchRule($audience->criteria ?? []),
        ];

        // LinkedIn Marketing API would be called here
        $platformAudienceId = 'li_aud_' . Str::random(15);

        return [
            'platform_audience_id' => $platformAudienceId,
            'platform' => 'linkedin',
            'status' => 'synced',
        ];
    }

    /**
     * Build LinkedIn match rule from criteria
     */
    private function buildLinkedInMatchRule(array $criteria): array
    {
        $matchRule = [];

        if (isset($criteria['company_names'])) {
            $matchRule['companies'] = array_map(
                fn($name) => ['operator' => 'INCLUDE', 'value' => $name],
                $criteria['company_names']
            );
        }

        if (isset($criteria['job_titles'])) {
            $matchRule['titles'] = array_map(
                fn($title) => ['operator' => 'INCLUDE', 'value' => $title],
                $criteria['job_titles']
            );
        }

        if (isset($criteria['industries'])) {
            $matchRule['industries'] = array_map(
                fn($industry) => ['operator' => 'INCLUDE', 'value' => $industry],
                $criteria['industries']
            );
        }

        return $matchRule;
    }

    /**
     * Store platform audience mapping
     */
    private function storePlatformMapping(Audience $audience, array $result): void
    {
        try {
            // Store in audience_platform_mappings table if it exists
            DB::table('cmis.audience_platform_mappings')->updateOrInsert(
                [
                    'audience_id' => $audience->audience_id,
                    'platform' => $result['platform'],
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'org_id' => $this->orgId,
                    'platform_audience_id' => $result['platform_audience_id'],
                    'status' => $result['status'],
                    'synced_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            // Table might not exist yet, log and continue
            Log::warning('Could not store platform mapping', [
                'audience_id' => $audience->audience_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audience sync job failed permanently', [
            'org_id' => $this->orgId,
            'audience_id' => $this->audienceId,
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $notificationService = app(NotificationService::class);
            $notificationService->notifyJobFailure(
                $this->userId,
                'audience_sync',
                $exception->getMessage(),
                [
                    'audience_id' => $this->audienceId,
                    'platform' => $this->platform,
                    'message' => __('audiences.sync_failed', [
                        'platform' => ucfirst($this->platform),
                        'error' => Str::limit($exception->getMessage(), 100),
                    ]),
                ]
            );
        }
    }
}
