<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncPlatformPosts;
use App\Jobs\SyncPlatformComments;
use App\Jobs\SyncPlatformMessages;
use App\Jobs\SyncPlatformCampaigns;

/**
 * Controller for syncing data from connected platforms
 */
class SyncController extends Controller
{
    use ApiResponse;

    /**
     * Trigger manual sync for an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function syncIntegration(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->where('is_active', true)
                ->firstOrFail();

            $syncTypes = $request->input('sync_types', ['posts', 'comments', 'messages', 'campaigns']);

            $jobs = [];
            foreach ($syncTypes as $type) {
                switch ($type) {
                    case 'posts':
                        SyncPlatformPosts::dispatch($integration);
                        $jobs[] = 'posts';
                        break;
                    case 'comments':
                        SyncPlatformComments::dispatch($integration);
                        $jobs[] = 'comments';
                        break;
                    case 'messages':
                        SyncPlatformMessages::dispatch($integration);
                        $jobs[] = 'messages';
                        break;
                    case 'campaigns':
                        SyncPlatformCampaigns::dispatch($integration);
                        $jobs[] = 'campaigns';
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync jobs dispatched',
                'platform' => $integration->platform,
                'jobs' => $jobs,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to trigger sync for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync posts from an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function syncPosts(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->where('is_active', true)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $posts = $connector->syncPosts($integration, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Posts synced successfully',
                'platform' => $integration->platform,
                'posts_count' => $posts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync posts for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync comments from an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function syncComments(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->where('is_active', true)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $comments = $connector->syncComments($integration, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Comments synced successfully',
                'platform' => $integration->platform,
                'comments_count' => $comments->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync comments for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync messages from an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function syncMessages(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->where('is_active', true)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $messages = $connector->syncMessages($integration, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Messages synced successfully',
                'platform' => $integration->platform,
                'messages_count' => $messages->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync messages for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync ad campaigns from an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function syncCampaigns(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->where('is_active', true)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $campaigns = $connector->syncCampaigns($integration, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Campaigns synced successfully',
                'platform' => $integration->platform,
                'campaigns_count' => $campaigns->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync campaigns for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all platforms for an organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncAll(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $integrations = Integration::where('org_id', $orgId)
                ->where('is_active', true)
                ->get();

            $dispatched = 0;
            foreach ($integrations as $integration) {
                SyncPlatformPosts::dispatch($integration);
                SyncPlatformComments::dispatch($integration);
                SyncPlatformMessages::dispatch($integration);
                SyncPlatformCampaigns::dispatch($integration);
                $dispatched += 4;
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync jobs dispatched for all platforms',
                'integrations_count' => $integrations->count(),
                'jobs_dispatched' => $dispatched,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync all platforms: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status for an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function getSyncStatus(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            // Get recent sync logs
            $syncLogs = \DB::table('cmis.api_logs')
                ->where('integration_id', $integrationId)
                ->where('operation', 'like', 'sync_%')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'integration' => [
                    'integration_id' => $integration->integration_id,
                    'platform' => $integration->platform,
                    'is_active' => $integration->is_active,
                    'last_sync_at' => $integration->last_sync_at,
                ],
                'recent_syncs' => $syncLogs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Integration not found',
            ], 404);
        }
    }

    /**
     * Get sync history for organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSyncHistory(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $limit = $request->input('limit', 50);

            $syncLogs = \DB::table('cmis.api_logs as logs')
                ->join('cmis.integrations as int', 'logs.integration_id', '=', 'int.integration_id')
                ->where('int.org_id', $orgId)
                ->where('logs.operation', 'like', 'sync_%')
                ->select('logs.*', 'int.platform')
                ->orderBy('logs.created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'sync_history' => $syncLogs,
                'total' => $syncLogs->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get sync history: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
