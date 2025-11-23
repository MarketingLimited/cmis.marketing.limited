<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Services\PublishingQueueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * PublishingQueueController
 *
 * Manages Buffer-style publishing queues for social media accounts
 * Implements Sprint 2.1: Queue per-Channel
 *
 * Features:
 * - Configure posting schedules per social account
 * - Define custom time slots for each weekday
 * - Automatic post scheduling to next available slot
 * - Queue visualization and statistics
 */
class PublishingQueueController extends Controller
{
    use ApiResponse;

    protected PublishingQueueService $queueService;

    public function __construct(PublishingQueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Get queue configuration for social account
     *
     * GET /api/queues/{socialAccountId}
     *
     * @param string $socialAccountId
     * @return JsonResponse
     */
    public function show(string $socialAccountId): JsonResponse
    {
        $queue = $this->queueService->getQueue($socialAccountId);

        if (!$queue) {
            return $this->error('Queue not configured for this account', 404);
        }

        return $this->success($queue, 'Queue retrieved successfully');
    }

    /**
     * Create or update queue configuration
     *
     * POST /api/queues
     *
     * Request body:
     * {
     *   "org_id": "uuid",
     *   "social_account_id": "uuid",
     *   "weekdays_enabled": "1111100", // Mon-Fri
     *   "time_slots": ["09:00", "13:00", "17:00"],
     *   "timezone": "Asia/Riyadh"
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'org_id' => 'required|uuid',
            'social_account_id' => 'required|uuid',
            'weekdays_enabled' => 'nullable|regex:/^[01]{7}$/',
            'time_slots' => 'nullable|array',
            'time_slots.*' => 'string|regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'timezone' => 'nullable|timezone',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $queue = $this->queueService->upsertQueue(
                $validated['org_id'],
                $validated['social_account_id'],
                [
                    'weekdays_enabled' => $validated['weekdays_enabled'] ?? '1111100',
                    'time_slots' => $validated['time_slots'] ?? [],
                    'timezone' => $validated['timezone'] ?? 'UTC',
                    'is_active' => $validated['is_active'] ?? true
                ]
            );

            return $this->created($queue, 'Publishing queue configured successfully');
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'error' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return $this->serverError('Failed to configure queue: ' . $e->getMessage());
        }
    }

    /**
     * Update existing queue configuration
     *
     * PUT /api/queues/{socialAccountId}
     *
     * @param Request $request
     * @param string $socialAccountId
     * @return JsonResponse
     */
    public function update(Request $request, string $socialAccountId): JsonResponse
    {
        $validated = $request->validate([
            'org_id' => 'required|uuid',
            'weekdays_enabled' => 'nullable|regex:/^[01]{7}$/',
            'time_slots' => 'nullable|array',
            'time_slots.*' => 'string|regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'timezone' => 'nullable|timezone',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $queue = $this->queueService->upsertQueue(
                $validated['org_id'],
                $socialAccountId,
                array_filter([
                    'weekdays_enabled' => $validated['weekdays_enabled'] ?? null,
                    'time_slots' => $validated['time_slots'] ?? null,
                    'timezone' => $validated['timezone'] ?? null,
                    'is_active' => $validated['is_active'] ?? null
                ])
            );

            return $this->success($queue, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update queue: ' . $e->getMessage());
        }
    }

    /**
     * Get next available time slot
     *
     * GET /api/queues/{socialAccountId}/next-slot
     *
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function nextSlot(string $socialAccountId, Request $request): JsonResponse
    {
        $afterTime = null;

        if ($request->has('after')) {
            try {
                $afterTime = new \DateTime($request->input('after'));
            } catch (\Exception $e) {
                return $this->error('Invalid date format for after parameter', 422);
            }
        }

        $nextSlot = $this->queueService->getNextAvailableSlot($socialAccountId, $afterTime);

        if (!$nextSlot) {
            return $this->error('No available slots found. Please configure queue first.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'next_slot' => $nextSlot->format('Y-m-d H:i:s'),
                'timezone' => $nextSlot->getTimezone()->getName()
            ]
        ]);
    }

    /**
     * Get queued posts
     *
     * GET /api/queues/{socialAccountId}/posts
     *
     * @param string $socialAccountId
     * @return JsonResponse
     */
    public function queuedPosts(string $socialAccountId): JsonResponse
    {
        $posts = $this->queueService->getQueuedPosts($socialAccountId);

        return $this->success($posts, 'Operation completed successfully');
    }

    /**
     * Schedule post to queue
     *
     * POST /api/queues/{socialAccountId}/schedule
     *
     * Request body:
     * {
     *   "post_id": "uuid",
     *   "scheduled_for": "2025-11-15 14:30:00" // optional
     * }
     *
     * @param Request $request
     * @param string $socialAccountId
     * @return JsonResponse
     */
    public function schedulePost(Request $request, string $socialAccountId): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|uuid',
            'scheduled_for' => 'nullable|date'
        ]);

        $scheduledFor = null;
        if (isset($validated['scheduled_for'])) {
            try {
                $scheduledFor = new \DateTime($validated['scheduled_for']);
            } catch (\Exception $e) {
                return $this->error('Invalid date format', 422);
            }
        }

        $success = $this->queueService->schedulePost(
            $validated['post_id'],
            $socialAccountId,
            $scheduledFor
        );

        if (!$success) {
            return $this->error('Failed to schedule post. Check if queue is configured and has available slots.', 400);
        }

        // Get the actual scheduled time
        $nextSlot = $scheduledFor ?? $this->queueService->getNextAvailableSlot($socialAccountId);

        return response()->json([
            'success' => true,
            'message' => 'Post scheduled successfully',
            'data' => [
                'post_id' => $validated['post_id'],
                'social_account_id' => $socialAccountId,
                'scheduled_for' => $nextSlot?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Remove post from queue
     *
     * DELETE /api/queues/posts/{postId}
     *
     * @param string $postId
     * @return JsonResponse
     */
    public function removePost(string $postId): JsonResponse
    {
        $success = $this->queueService->removeFromQueue($postId);

        if (!$success) {
            return $this->error('Failed to remove post from queue', 400);
        }

        return $this->success(null, 'Post removed from queue successfully');
    }

    /**
     * Get queue statistics
     *
     * GET /api/queues/{socialAccountId}/statistics
     *
     * @param string $socialAccountId
     * @return JsonResponse
     */
    public function statistics(string $socialAccountId): JsonResponse
    {
        $stats = $this->queueService->getQueueStatistics($socialAccountId);

        return $this->success($stats, 'Statistics retrieved successfully');
    }
}
