<?php

namespace App\Services\Social;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Social Account Service
 *
 * Handles retrieval and formatting of connected social media accounts
 * across all platforms (Meta, Twitter, LinkedIn, TikTok, etc.)
 */
class SocialAccountService
{
    /**
     * Platform icon mapping
     */
    protected const PLATFORM_ICONS = [
        'facebook' => 'fab fa-facebook',
        'instagram' => 'fab fa-instagram',
        'twitter' => 'fab fa-twitter',
        'x' => 'fab fa-x-twitter',
        'linkedin' => 'fab fa-linkedin',
        'youtube' => 'fab fa-youtube',
        'tiktok' => 'fab fa-tiktok',
        'pinterest' => 'fab fa-pinterest',
        'reddit' => 'fab fa-reddit',
        'tumblr' => 'fab fa-tumblr',
        'google_business' => 'fab fa-google',
        'threads' => 'fab fa-threads',
    ];

    /**
     * Get all connected social accounts for an organization
     *
     * @param string $orgId Organization UUID
     * @return array Connected accounts with metadata
     * @throws \Exception
     */
    public function getConnectedAccounts(string $orgId): array
    {
        try {
            // Set RLS context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            // Get all active platform connections
            $connections = PlatformConnection::where('org_id', $orgId)
                ->where('status', 'active')
                ->get();

            if ($connections->isEmpty()) {
                return [
                    'accounts' => [],
                    'total' => 0,
                    'message' => 'No platform connections found. Please connect your social media accounts.',
                ];
            }

            $accounts = [];

            // Process each platform connection
            foreach ($connections as $connection) {
                $platform = strtolower($connection->platform);
                $metadata = $connection->account_metadata ?? [];

                // Meta platform requires special handling for Pages & Instagram
                if ($platform === 'meta' || $platform === 'facebook') {
                    $this->addMetaAccounts($connection, $accounts);
                    continue;
                }

                // Other platforms: single account per connection
                $accounts[] = $this->formatAccount($connection, $platform, $metadata);
            }

            return [
                'accounts' => $accounts,
                'total' => count($accounts),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get connected accounts', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Format a single account from a platform connection
     *
     * @param PlatformConnection $connection
     * @param string $platform
     * @param array $metadata
     * @return array Formatted account data
     */
    protected function formatAccount(PlatformConnection $connection, string $platform, array $metadata): array
    {
        return [
            'id' => $platform . '_' . $connection->connection_id,
            'type' => $platform,
            'platformId' => $connection->account_id ?? $connection->connection_id,
            'name' => $connection->account_name ?? ucfirst($platform) . ' Account',
            'picture' => $metadata['profile_picture_url'] ?? $metadata['picture'] ?? null,
            'username' => $metadata['username'] ?? $metadata['screen_name'] ?? null,
            'connectionId' => $connection->connection_id,
            'icon' => self::PLATFORM_ICONS[$platform] ?? 'fas fa-share-alt',
            'lastSync' => $connection->last_sync_at?->diffForHumans(),
        ];
    }

    /**
     * Add Meta (Facebook/Instagram) accounts
     *
     * Meta connections can include multiple Pages and Instagram Business accounts.
     * This method fetches fresh data from the Graph API.
     *
     * @param PlatformConnection $connection
     * @param array &$accounts Reference to accounts array
     * @return void
     */
    protected function addMetaAccounts(PlatformConnection $connection, array &$accounts): void
    {
        $accessToken = $connection->access_token;
        $metadata = $connection->account_metadata ?? [];
        $selectedAssets = $metadata['selected_assets'] ?? [];

        // Get selected asset IDs
        $selectedPageIds = $selectedAssets['pages'] ?? $selectedAssets['page'] ?? [];
        $selectedInstagramIds = $selectedAssets['instagram_accounts'] ?? $selectedAssets['instagram_account'] ?? [];

        // Fetch Facebook Pages
        $this->addFacebookPages($selectedPageIds, $accessToken, $connection->connection_id, $accounts);

        // Fetch Instagram accounts
        $this->addInstagramAccounts($selectedInstagramIds, $selectedPageIds, $accessToken, $connection->connection_id, $accounts);
    }

    /**
     * Add Facebook Pages to accounts array
     *
     * @param array $pageIds
     * @param string $accessToken
     * @param string $connectionId
     * @param array &$accounts
     * @return void
     */
    protected function addFacebookPages(array $pageIds, string $accessToken, string $connectionId, array &$accounts): void
    {
        foreach ($pageIds as $pageId) {
            try {
                $pageResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,picture{url},category',
                ]);

                if ($pageResponse->successful()) {
                    $page = $pageResponse->json();
                    $accounts[] = [
                        'id' => 'facebook_' . $page['id'],
                        'type' => 'facebook',
                        'platformId' => $page['id'],
                        'name' => $page['name'] ?? 'Facebook Page',
                        'picture' => $page['picture']['data']['url'] ?? null,
                        'category' => $page['category'] ?? null,
                        'connectionId' => $connectionId,
                        'icon' => 'fab fa-facebook',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Facebook page details', [
                    'page_id' => $pageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Add Instagram Business accounts to accounts array
     *
     * @param array $instagramIds
     * @param array $pageIds
     * @param string $accessToken
     * @param string $connectionId
     * @param array &$accounts
     * @return void
     */
    protected function addInstagramAccounts(array $instagramIds, array $pageIds, string $accessToken, string $connectionId, array &$accounts): void
    {
        foreach ($instagramIds as $igId) {
            try {
                $igResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$igId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,username,name,profile_picture_url,followers_count',
                ]);

                if ($igResponse->successful()) {
                    $igData = $igResponse->json();

                    // Find connected Facebook Page
                    $connectedPage = $this->findConnectedPage($igId, $pageIds, $accessToken);

                    $accounts[] = [
                        'id' => 'instagram_' . $igId,
                        'type' => 'instagram',
                        'platformId' => $igId,
                        'name' => '@' . ($igData['username'] ?? 'instagram'),
                        'username' => $igData['username'] ?? null,
                        'picture' => $igData['profile_picture_url'] ?? null,
                        'followers' => $igData['followers_count'] ?? 0,
                        'connectedPageId' => $connectedPage['id'] ?? null,
                        'connectedPageName' => $connectedPage['name'] ?? null,
                        'connectionId' => $connectionId,
                        'icon' => 'fab fa-instagram',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Instagram account details', [
                    'instagram_id' => $igId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Find the Facebook Page connected to an Instagram Business account
     *
     * @param string $instagramId
     * @param array $pageIds
     * @param string $accessToken
     * @return array|null Page data or null
     */
    protected function findConnectedPage(string $instagramId, array $pageIds, string $accessToken): ?array
    {
        foreach ($pageIds as $pageId) {
            try {
                $pageResponse = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,instagram_business_account',
                ]);

                if ($pageResponse->successful()) {
                    $pageData = $pageResponse->json();
                    $pageIgId = $pageData['instagram_business_account']['id'] ?? null;

                    if ($pageIgId === $instagramId) {
                        return $pageData;
                    }
                }
            } catch (\Exception $e) {
                // Continue checking other pages
                continue;
            }
        }

        return null;
    }

    /**
     * Get platform icon by name
     *
     * @param string $platform
     * @return string Font Awesome icon class
     */
    public function getPlatformIcon(string $platform): string
    {
        return self::PLATFORM_ICONS[strtolower($platform)] ?? 'fas fa-share-alt';
    }

    /**
     * Get all supported platforms
     *
     * @return array Platform names and icons
     */
    public function getSupportedPlatforms(): array
    {
        return array_map(function ($platform, $icon) {
            return [
                'name' => $platform,
                'icon' => $icon,
                'label' => ucfirst(str_replace('_', ' ', $platform)),
            ];
        }, array_keys(self::PLATFORM_ICONS), self::PLATFORM_ICONS);
    }
}
