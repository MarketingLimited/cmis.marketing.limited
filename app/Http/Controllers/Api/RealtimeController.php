<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

/**
 * RealtimeController
 *
 * Provides Server-Sent Events (SSE) endpoint for real-time updates.
 * Alternative to WebSockets for simpler real-time functionality.
 *
 * Issue #70 - Implement real-time updates across interfaces
 */
class RealtimeController extends Controller
{
    use ApiResponse;

    /**
     * Stream real-time updates for current organization.
     *
     * GET /api/realtime/stream
     *
     * Usage:
     * ```javascript
     * const eventSource = new EventSource('/api/realtime/stream', {
     *     headers: {
     *         'Authorization': 'Bearer ' + token
     *     }
     * });
     *
     * eventSource.addEventListener('resource.updated', (event) => {
     *     const data = JSON.parse(event.data);
     *     console.log('Resource updated:', data);
     * });
     * ```
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();

        if (!$user || !$user->organization) {
            abort(401, 'Unauthorized');
        }

        $orgId = $user->organization->id;

        return response()->stream(function () use ($orgId) {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable nginx buffering

            // Send initial connection message
            echo "event: connected\n";
            echo "data: " . json_encode([
                'message' => 'Connected to real-time updates',
                'org_id' => $orgId,
                'timestamp' => now()->toIso8601String(),
            ]) . "\n\n";
            ob_flush();
            flush();

            $lastEventId = 0;

            // Keep connection alive and send updates
            while (true) {
                // Check for new updates from cache/Redis
                $updates = $this->getRecentUpdates($orgId, $lastEventId);

                foreach ($updates as $update) {
                    $lastEventId = $update['id'];

                    echo "event: resource.updated\n";
                    echo "id: {$lastEventId}\n";
                    echo "data: " . json_encode($update['data']) . "\n\n";
                    ob_flush();
                    flush();
                }

                // Send heartbeat every 15 seconds
                if (time() % 15 === 0) {
                    echo ": heartbeat\n\n";
                    ob_flush();
                    flush();
                }

                // Check if connection is still alive
                if (connection_aborted()) {
                    break;
                }

                // Sleep for 1 second before checking again
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get recent updates for an organization.
     */
    protected function getRecentUpdates(string $orgId, int $afterId = 0): array
    {
        $cacheKey = "realtime:org:{$orgId}:updates";

        // Get updates from cache
        $updates = Cache::get($cacheKey, []);

        // Filter updates after the given ID
        return array_filter($updates, function ($update) use ($afterId) {
            return $update['id'] > $afterId;
        });
    }

    /**
     * Publish an update to the real-time stream.
     *
     * This is called internally when ResourceUpdated event is fired.
     */
    public static function publishUpdate(string $orgId, array $data): void
    {
        $cacheKey = "realtime:org:{$orgId}:updates";

        // Get current updates
        $updates = Cache::get($cacheKey, []);

        // Add new update
        $updates[] = [
            'id' => time() * 1000 + count($updates), // Millisecond timestamp + sequence
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep only last 100 updates
        $updates = array_slice($updates, -100);

        // Store updates for 5 minutes
        Cache::put($cacheKey, $updates, now()->addMinutes(5));
    }

    /**
     * Get cache invalidation suggestions.
     *
     * GET /api/realtime/invalidate-cache
     *
     * Returns information about what caches should be invalidated
     * based on recent updates.
     */
    public function getCacheInvalidationSuggestions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->organization) {
            abort(401);
        }

        $orgId = $user->organization->id;

        // Get recent updates
        $updates = $this->getRecentUpdates($orgId, 0);

        // Group by resource type
        $suggestions = [];
        foreach ($updates as $update) {
            $resourceType = $update['data']['resource_type'] ?? 'unknown';

            if (!isset($suggestions[$resourceType])) {
                $suggestions[$resourceType] = [
                    'resource_type' => $resourceType,
                    'affected_resources' => [],
                    'suggested_actions' => [],
                ];
            }

            $suggestions[$resourceType]['affected_resources'][] = $update['data']['resource_id'];

            // Add action-specific suggestions
            $action = $update['data']['action'] ?? 'updated';
            if ($action === 'deleted') {
                $suggestions[$resourceType]['suggested_actions'][] = 'Remove from local cache';
            } elseif ($action === 'updated') {
                $suggestions[$resourceType]['suggested_actions'][] = 'Refresh data';
            } elseif ($action === 'created') {
                $suggestions[$resourceType]['suggested_actions'][] = 'Fetch and add to list';
            }
        }

        return response()->json([
            'success' => true,
            'suggestions' => array_values($suggestions),
            'last_check' => now()->toIso8601String(),
        ]);
    }
}
