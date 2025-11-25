<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\PublishScheduledPost;

/**
 * Controller for publishing and scheduling content across platforms
 */
class ContentPublishingController extends Controller
{
    use ApiResponse;

    /**
     * Publish content immediately to one or more platforms
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publishNow(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string',
                'title' => 'nullable|string',
                'integration_ids' => 'required|array',
                'integration_ids.*' => 'required|string|exists:cmis.integrations,integration_id',
                'media_urls' => 'nullable|array',
            ]);

            $orgId = $request->user()->org_id;
            $results = [];

            // Create content item
            $contentItem = ContentItem::create([
                'org_id' => $orgId,
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'],
                'content_type' => 'post',
                'status' => 'published',
                'created_by' => $request->user()->user_id,
            ]);

            foreach ($validated['integration_ids'] as $integrationId) {
                $integration = Integration::where('integration_id', $integrationId)
                    ->where('org_id', $orgId)
                    ->where('is_active', true)
                    ->first();

                if (!$integration) {
                    $results[] = [
                        'integration_id' => $integrationId,
                        'success' => false,
                        'error' => 'Integration not found or inactive',
                    ];
                    continue;
                }

                try {
                    $connector = ConnectorFactory::make($integration->platform);
                    $platformPostId = $connector->publishPost($integration, $contentItem);

                    // Store in social_posts table
                    DB::table('cmis_social.social_posts')->insert([
                        'post_id' => \Illuminate\Support\Str::uuid(),
                        'org_id' => $orgId,
                        'integration_id' => $integrationId,
                        'platform' => $integration->platform,
                        'platform_post_id' => $platformPostId,
                        'content' => $validated['content'],
                        'published_at' => now(),
                        'status' => 'published',
                        'created_at' => now(),
                    ]);

                    $results[] = [
                        'integration_id' => $integrationId,
                        'platform' => $integration->platform,
                        'success' => true,
                        'platform_post_id' => $platformPostId,
                    ];
                } catch (\Exception $e) {
                    Log::error("Failed to publish to {$integration->platform}: {$e->getMessage()}");
                    $results[] = [
                        'integration_id' => $integrationId,
                        'platform' => $integration->platform,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'content_item_id' => $contentItem->content_id,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish content: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule content for later publishing
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function schedulePost(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string',
                'title' => 'nullable|string',
                'integration_ids' => 'required|array',
                'integration_ids.*' => 'required|string|exists:cmis.integrations,integration_id',
                'scheduled_at' => 'required|date|after:now',
                'media_urls' => 'nullable|array',
            ]);

            $orgId = $request->user()->org_id;
            $scheduledAt = Carbon::parse($validated['scheduled_at']);

            // Create content item
            $contentItem = ContentItem::create([
                'org_id' => $orgId,
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'],
                'content_type' => 'post',
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
                'created_by' => $request->user()->user_id,
            ]);

            // Create scheduled posts for each integration
            $scheduledPosts = [];
            foreach ($validated['integration_ids'] as $integrationId) {
                $integration = Integration::where('integration_id', $integrationId)
                    ->where('org_id', $orgId)
                    ->where('is_active', true)
                    ->first();

                if (!$integration) continue;

                $scheduleId = \Illuminate\Support\Str::uuid();
                DB::table('cmis_creative.scheduled_posts')->insert([
                    'schedule_id' => $scheduleId,
                    'org_id' => $orgId,
                    'content_id' => $contentItem->content_id,
                    'integration_id' => $integrationId,
                    'platform' => $integration->platform,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                    'created_by' => $request->user()->user_id,
                    'created_at' => now(),
                ]);

                // Dispatch job to publish at scheduled time
                PublishScheduledPost::dispatch($scheduleId, $integrationId, $contentItem)
                    ->delay($scheduledAt);

                $scheduledPosts[] = [
                    'schedule_id' => $scheduleId,
                    'integration_id' => $integrationId,
                    'platform' => $integration->platform,
                    'scheduled_at' => $scheduledAt,
                ];
            }

            return response()->json([
                'success' => true,
                'content_item_id' => $contentItem->content_id,
                'scheduled_posts' => $scheduledPosts,
                'message' => 'Content scheduled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to schedule content: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get scheduled posts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getScheduledPosts(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $status = $request->input('status', 'pending');

            $scheduledPosts = DB::table('cmis_creative.scheduled_posts as sp')
                ->join('cmis_creative.content_items as ci', 'sp.content_id', '=', 'ci.content_id')
                ->join('cmis.integrations as int', 'sp.integration_id', '=', 'int.integration_id')
                ->where('sp.org_id', $orgId)
                ->where('sp.status', $status)
                ->select(
                    'sp.*',
                    'ci.content',
                    'ci.title',
                    'int.platform',
                    'int.external_account_name'
                )
                ->orderBy('sp.scheduled_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'scheduled_posts' => $scheduledPosts,
                'total' => $scheduledPosts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get scheduled posts: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update scheduled post
     *
     * @param string $scheduleId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateScheduledPost(string $scheduleId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'scheduled_at' => 'nullable|date|after:now',
                'content' => 'nullable|string',
                'title' => 'nullable|string',
            ]);

            $orgId = $request->user()->org_id;

            $scheduled = DB::table('cmis_creative.scheduled_posts')
                ->where('schedule_id', $scheduleId)
                ->where('org_id', $orgId)
                ->where('status', 'pending')
                ->first();

            if (!$scheduled) {
                return response()->json([
                    'success' => false,
                    'error' => 'Scheduled post not found or already published',
                ], 404);
            }

            // Update scheduled_at if provided
            if (isset($validated['scheduled_at'])) {
                DB::table('cmis_creative.scheduled_posts')
                    ->where('schedule_id', $scheduleId)
                    ->update([
                        'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                        'updated_at' => now(),
                    ]);
            }

            // Update content if provided
            if (isset($validated['content']) || isset($validated['title'])) {
                $updates = ['updated_at' => now()];
                if (isset($validated['content'])) $updates['content'] = $validated['content'];
                if (isset($validated['title'])) $updates['title'] = $validated['title'];

                DB::table('cmis_creative.content_items')
                    ->where('content_id', $scheduled->content_id)
                    ->update($updates);
            }

            return response()->json([
                'success' => true,
                'message' => 'Scheduled post updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update scheduled post: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel scheduled post
     *
     * @param string $scheduleId
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelScheduledPost(string $scheduleId, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $updated = DB::table('cmis_creative.scheduled_posts')
                ->where('schedule_id', $scheduleId)
                ->where('org_id', $orgId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Scheduled post not found or already processed',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Scheduled post cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to cancel scheduled post: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get publishing history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPublishingHistory(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $limit = $request->input('limit', 50);

            $history = DB::table('cmis_social.social_posts as sp')
                ->join('cmis.integrations as int', 'sp.integration_id', '=', 'int.integration_id')
                ->where('sp.org_id', $orgId)
                ->select(
                    'sp.*',
                    'int.platform',
                    'int.external_account_name'
                )
                ->orderBy('sp.published_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'history' => $history,
                'total' => $history->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get publishing history: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
