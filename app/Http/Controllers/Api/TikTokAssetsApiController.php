<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\PlatformConnection;
use App\Services\Platform\TikTokAssetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API controller for fetching TikTok Business assets via AJAX.
 * Supports progressive loading for better UX on pages with hundreds of assets.
 */
class TikTokAssetsApiController extends Controller
{
    use ApiResponse;

    public function __construct(private TikTokAssetsService $tiktokAssetsService)
    {
    }

    /**
     * Get the platform connection and access token.
     */
    private function getConnectionWithToken(string $org, string $connectionId): ?array
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->whereIn('platform', ['tiktok_business', 'tiktok_ads'])
            ->first();

        if (!$connection) {
            return null;
        }

        if (empty($connection->access_token)) {
            Log::error('TikTok access token is empty', ['connection_id' => $connectionId]);
            return null;
        }

        return [
            'connection' => $connection,
            'access_token' => $connection->access_token,
            'advertiser_ids' => $connection->account_metadata['advertiser_ids'] ?? [],
        ];
    }

    /**
     * Get TikTok accounts for video publishing.
     */
    public function getTikTokAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        try {
            $forceRefresh = $request->boolean('refresh', false);
            $accounts = $this->tiktokAssetsService->getTikTokAccounts($org, $forceRefresh);

            return $this->success($accounts, __('settings.tiktok_accounts_loaded'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch TikTok accounts', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_load_tiktok_accounts'));
        }
    }

    /**
     * Delete a TikTok account connection.
     */
    public function deleteTikTokAccount(Request $request, string $org, string $connectionId, string $accountId): JsonResponse
    {
        try {
            $deleted = $this->tiktokAssetsService->deleteTikTokAccount($org, $accountId);

            if ($deleted) {
                return $this->success(null, __('settings.tiktok_account_deleted'));
            }

            return $this->notFound(__('settings.tiktok_account_not_found'));
        } catch (\Exception $e) {
            Log::error('Failed to delete TikTok account', [
                'org_id' => $org,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_delete_tiktok_account'));
        }
    }

    /**
     * Get advertiser (ad) accounts.
     */
    public function getAdvertisers(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('settings.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $advertisers = $this->tiktokAssetsService->getAdvertisers(
                $connectionId,
                $data['access_token'],
                $data['advertiser_ids'],
                $forceRefresh
            );

            return $this->success($advertisers, __('settings.tiktok_advertisers_loaded'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch TikTok advertisers', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_load_tiktok_advertisers'));
        }
    }

    /**
     * Get conversion pixels.
     */
    public function getPixels(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('settings.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $pixels = $this->tiktokAssetsService->getPixels(
                $connectionId,
                $data['access_token'],
                $data['advertiser_ids'],
                $forceRefresh
            );

            return $this->success($pixels, __('settings.tiktok_pixels_loaded'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch TikTok pixels', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_load_tiktok_pixels'));
        }
    }

    /**
     * Get product catalogs.
     */
    public function getCatalogs(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('settings.connection_not_found'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $catalogs = $this->tiktokAssetsService->getCatalogs(
                $connectionId,
                $data['access_token'],
                $data['advertiser_ids'],
                $forceRefresh
            );

            return $this->success($catalogs, __('settings.tiktok_catalogs_loaded'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch TikTok catalogs', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_load_tiktok_catalogs'));
        }
    }

    /**
     * Refresh all assets (clear cache and reload).
     */
    public function refreshAll(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('settings.connection_not_found'));
        }

        try {
            $this->tiktokAssetsService->clearCache($connectionId, $org);

            return $this->success(null, __('settings.tiktok_cache_cleared'));
        } catch (\Exception $e) {
            Log::error('Failed to refresh TikTok assets', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_refresh_tiktok_assets'));
        }
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(Request $request, string $org, string $connectionId): JsonResponse
    {
        try {
            $status = $this->tiktokAssetsService->getCacheStatus($connectionId, $org);

            return $this->success($status, __('settings.cache_status_retrieved'));
        } catch (\Exception $e) {
            Log::error('Failed to get TikTok cache status', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('settings.failed_to_get_cache_status'));
        }
    }
}
