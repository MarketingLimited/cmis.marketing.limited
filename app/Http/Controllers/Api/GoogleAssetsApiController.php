<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\PlatformConnection;
use App\Services\Platform\GoogleAssetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API controller for fetching Google platform assets via AJAX.
 * Supports progressive loading for better UX on pages with many assets.
 */
class GoogleAssetsApiController extends Controller
{
    use ApiResponse;

    public function __construct(private GoogleAssetsService $googleAssetsService)
    {
    }

    /**
     * Get the platform connection and access token.
     */
    private function getConnectionWithToken(string $org, string $connectionId): ?array
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->first();

        if (!$connection) {
            return null;
        }

        if (empty($connection->access_token)) {
            Log::error('Google access token is empty', ['connection_id' => $connectionId]);
            return null;
        }

        // Get valid access token (handles refresh if expired)
        $accessToken = $this->googleAssetsService->getValidAccessToken($connection);

        if (!$accessToken) {
            Log::error('Failed to get valid Google access token', ['connection_id' => $connectionId]);
            return null;
        }

        return [
            'connection' => $connection,
            'access_token' => $accessToken,
        ];
    }

    /**
     * Get YouTube channels.
     */
    public function getYouTubeChannels(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $result = $this->googleAssetsService->getYouTubeChannels(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            // Handle the new response format with scope-insufficient detection
            if (isset($result['needs_auth']) && $result['needs_auth']) {
                return $this->success([
                    'channels' => [],
                    'needs_auth' => true,
                    'scope_insufficient' => true,
                ], __('google_assets.errors.youtube_scope_required'));
            }

            // Support both old format (array of channels) and new format with channels key
            $channels = $result['channels'] ?? $result;

            // Store channels in connection metadata for use during profile sync
            // This ensures we use the same data user sees when creating profiles
            if (!empty($channels)) {
                $connection = $data['connection'];
                $metadata = $connection->account_metadata ?? [];
                $metadata['youtube_channels'] = $channels;
                $connection->update(['account_metadata' => $metadata]);

                Log::info('Stored YouTube channels in connection metadata', [
                    'connection_id' => $connectionId,
                    'channel_count' => count($channels),
                ]);
            }

            return $this->success($channels, __('google_assets.success.youtube_channels'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch YouTube channels', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.youtube_channels'));
        }
    }

    /**
     * Search YouTube channels by name.
     * Helps users find their Brand Account channels.
     */
    public function searchYouTubeChannels(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return $this->error(__('google_assets.errors.search_query_too_short'), 400);
        }

        try {
            $result = $this->googleAssetsService->searchYouTubeChannels(
                $data['access_token'],
                $query
            );

            if ($result['error']) {
                return $this->error($result['error'], 400);
            }

            return $this->success($result['channels'], __('google_assets.success.youtube_search'));
        } catch (\Exception $e) {
            Log::error('Failed to search YouTube channels', [
                'connection_id' => $connectionId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.youtube_search'));
        }
    }

    /**
     * Get YouTube channel by ID.
     * Used to validate and fetch details for manually added channels.
     */
    public function getYouTubeChannelById(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        $channelId = $request->input('channel_id', '');
        if (empty($channelId)) {
            return $this->error(__('google_assets.errors.channel_id_required'), 400);
        }

        try {
            $channel = $this->googleAssetsService->getYouTubeChannelById(
                $data['access_token'],
                $channelId
            );

            if (!$channel) {
                return $this->notFound(__('google_assets.errors.channel_not_found'));
            }

            return $this->success($channel, __('google_assets.success.youtube_channel_found'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch YouTube channel by ID', [
                'connection_id' => $connectionId,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.youtube_channel_lookup'));
        }
    }

    /**
     * Get Google Ads accounts.
     */
    public function getAdsAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $result = $this->googleAssetsService->getAdsAccounts(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            // Include error info if present
            return $this->success([
                'accounts' => $result['accounts'],
                'error' => $result['error'],
            ], __('google_assets.success.ads_accounts'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Ads accounts', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.ads_accounts'));
        }
    }

    /**
     * Get Google Analytics properties.
     */
    public function getAnalyticsProperties(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $properties = $this->googleAssetsService->getAnalyticsProperties(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($properties, __('google_assets.success.analytics'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Analytics properties', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.analytics'));
        }
    }

    /**
     * Get Google Business Profiles.
     */
    public function getBusinessProfiles(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $result = $this->googleAssetsService->getBusinessProfiles(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            // Store business profiles in connection metadata for use during profile sync
            // This ensures we use the same data user sees when creating profiles
            $profiles = $result['profiles'] ?? [];
            if (!empty($profiles)) {
                $connection = $data['connection'];
                $metadata = $connection->account_metadata ?? [];
                $metadata['business_profiles'] = $profiles;
                $connection->update(['account_metadata' => $metadata]);

                Log::info('Stored Google Business Profiles in connection metadata', [
                    'connection_id' => $connectionId,
                    'profile_count' => count($profiles),
                ]);
            }

            // Include error info if present
            return $this->success([
                'profiles' => $profiles,
                'error' => $result['error'],
            ], __('google_assets.success.business_profiles'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Business Profiles', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.business_profiles'));
        }
    }

    /**
     * Get Google Tag Manager containers.
     */
    public function getTagManagerContainers(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $containers = $this->googleAssetsService->getTagManagerContainers(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($containers, __('google_assets.success.tag_manager'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Tag Manager containers', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.tag_manager'));
        }
    }

    /**
     * Get Google Merchant Center accounts.
     */
    public function getMerchantCenterAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $result = $this->googleAssetsService->getMerchantCenterAccounts(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            // Include error info if present
            return $this->success([
                'accounts' => $result['accounts'],
                'error' => $result['error'],
            ], __('google_assets.success.merchant_center'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Merchant Center accounts', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.merchant_center'));
        }
    }

    /**
     * Get Google Search Console sites.
     */
    public function getSearchConsoleSites(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $sites = $this->googleAssetsService->getSearchConsoleSites(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($sites, __('google_assets.success.search_console'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Search Console sites', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.search_console'));
        }
    }

    /**
     * Get Google Calendars.
     */
    public function getCalendars(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $calendars = $this->googleAssetsService->getCalendars(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($calendars, __('google_assets.success.calendars'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Calendars', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.calendars'));
        }
    }

    /**
     * Get Google Drive folders.
     */
    public function getDriveFolders(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $folders = $this->googleAssetsService->getDriveFolders(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($folders, __('google_assets.success.drive'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Drive folders', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.drive'));
        }
    }

    /**
     * Refresh all assets (clear cache).
     */
    public function refreshAll(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $this->googleAssetsService->refreshAll($connectionId);

            return $this->success([
                'message' => __('google_assets.success.cache_cleared'),
                'refreshed_at' => now()->toIso8601String(),
            ], __('google_assets.success.cache_cleared'));
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google asset cache', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('google_assets.errors.cache_clear'));
        }
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(Request $request, string $org, string $connectionId): JsonResponse
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->first();

        if (!$connection) {
            return $this->notFound(__('google_assets.errors.connection_not_found'));
        }

        try {
            $status = $this->googleAssetsService->getCacheStatus($connectionId);

            return $this->success($status, __('google_assets.success.cache_status'));
        } catch (\Exception $e) {
            return $this->serverError(__('google_assets.errors.cache_status'));
        }
    }
}
