<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\PlatformConnection;
use App\Services\Platform\MetaAssetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * API controller for fetching Meta Business Manager assets via AJAX.
 * Supports progressive loading for better UX on pages with hundreds of assets.
 */
class MetaAssetsApiController extends Controller
{
    use ApiResponse;

    public function __construct(private MetaAssetsService $metaAssetsService)
    {
    }

    /**
     * Get the platform connection and access token.
     * Handles both encrypted and plain text tokens for backward compatibility.
     */
    private function getConnectionWithToken(string $org, string $connectionId): ?array
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->first();

        if (!$connection) {
            return null;
        }

        if (empty($connection->access_token)) {
            Log::error('Access token is empty', ['connection_id' => $connectionId]);
            return null;
        }

        // Try to decrypt the token first (new encrypted format)
        try {
            $accessToken = Crypt::decryptString($connection->access_token);
        } catch (\Exception $e) {
            // If decryption fails, assume it's a plain text token (legacy format)
            // Meta tokens start with 'EAA' - verify it looks like a valid token
            if (str_starts_with($connection->access_token, 'EAA')) {
                $accessToken = $connection->access_token;
                Log::debug('Using plain text access token', ['connection_id' => $connectionId]);
            } else {
                Log::error('Failed to decrypt access token and token format is invalid', [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        }

        return [
            'connection' => $connection,
            'access_token' => $accessToken,
        ];
    }

    /**
     * Get Facebook Pages.
     */
    public function getPages(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $pages = $this->metaAssetsService->getPages(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($pages, __('Facebook Pages loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook Pages', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Facebook Pages'));
        }
    }

    /**
     * Get Instagram Business accounts.
     */
    public function getInstagramAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $accounts = $this->metaAssetsService->getInstagramAccounts(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($accounts, __('Instagram accounts loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Instagram accounts', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Instagram accounts'));
        }
    }

    /**
     * Get Threads accounts.
     */
    public function getThreadsAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $accounts = $this->metaAssetsService->getThreadsAccounts(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($accounts, __('Threads accounts loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Threads accounts', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Threads accounts'));
        }
    }

    /**
     * Get Meta Ad Accounts.
     */
    public function getAdAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $accounts = $this->metaAssetsService->getAdAccounts(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($accounts, __('Ad Accounts loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Ad Accounts', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Ad Accounts'));
        }
    }

    /**
     * Get Meta Pixels.
     */
    public function getPixels(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $pixels = $this->metaAssetsService->getPixels(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($pixels, __('Meta Pixels loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta Pixels', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Meta Pixels'));
        }
    }

    /**
     * Get Product Catalogs.
     */
    public function getCatalogs(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $catalogs = $this->metaAssetsService->getCatalogs(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($catalogs, __('Product Catalogs loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Product Catalogs', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Product Catalogs'));
        }
    }

    /**
     * Get WhatsApp Business Accounts.
     */
    public function getWhatsappAccounts(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $accounts = $this->metaAssetsService->getWhatsappAccounts(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($accounts, __('WhatsApp accounts loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch WhatsApp accounts', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load WhatsApp accounts'));
        }
    }

    /**
     * Get Business Managers.
     */
    public function getBusinesses(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $businesses = $this->metaAssetsService->getBusinesses(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($businesses, __('Business Managers loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Business Managers', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Business Managers'));
        }
    }

    /**
     * Get Custom Conversions.
     */
    public function getCustomConversions(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $conversions = $this->metaAssetsService->getCustomConversions(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($conversions, __('Custom Conversions loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Custom Conversions', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Custom Conversions'));
        }
    }

    /**
     * Get Offline Event Sets.
     */
    public function getOfflineEventSets(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $forceRefresh = $request->boolean('refresh', false);
            $eventSets = $this->metaAssetsService->getOfflineEventSets(
                $connectionId,
                $data['access_token'],
                $forceRefresh
            );

            return $this->success($eventSets, __('Offline Event Sets loaded successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Offline Event Sets', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to load Offline Event Sets'));
        }
    }

    /**
     * Refresh all assets (clear cache and reload).
     */
    public function refreshAll(Request $request, string $org, string $connectionId): JsonResponse
    {
        $data = $this->getConnectionWithToken($org, $connectionId);

        if (!$data) {
            return $this->notFound(__('Connection not found or access token invalid'));
        }

        try {
            $this->metaAssetsService->refreshAll($connectionId);

            return $this->success([
                'message' => __('Cache cleared successfully'),
                'refreshed_at' => now()->toIso8601String(),
            ], __('All asset caches have been cleared'));
        } catch (\Exception $e) {
            Log::error('Failed to refresh asset cache', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('Failed to clear asset cache'));
        }
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(Request $request, string $org, string $connectionId): JsonResponse
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->first();

        if (!$connection) {
            return $this->notFound(__('Connection not found'));
        }

        try {
            $status = $this->metaAssetsService->getCacheStatus($connectionId);

            return $this->success($status, __('Cache status retrieved'));
        } catch (\Exception $e) {
            return $this->serverError(__('Failed to get cache status'));
        }
    }
}
