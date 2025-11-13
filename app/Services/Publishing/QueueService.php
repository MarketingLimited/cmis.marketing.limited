<?php

namespace App\Services\Publishing;

use App\Repositories\Contracts\PublishingQueueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QueueService
{
    protected PublishingQueueRepositoryInterface $queueRepo;

    public function __construct(PublishingQueueRepositoryInterface $queueRepo)
    {
        $this->queueRepo = $queueRepo;
    }

    /**
     * Get all queues for an organization
     */
    public function getOrgQueues(string $orgId, bool $activeOnly = false): Collection
    {
        return $this->queueRepo->getForOrg($orgId, $activeOnly);
    }

    /**
     * Get queue for a social account
     */
    public function getAccountQueue(string $socialAccountId): ?array
    {
        $queue = $this->queueRepo->getForAccount($socialAccountId);

        if (!$queue) {
            return null;
        }

        return $this->formatQueueData($queue);
    }

    /**
     * Create a new publishing queue
     */
    public function createQueue(string $orgId, array $data): array
    {
        try {
            // Add org_id to data
            $data['org_id'] = $orgId;

            // Validate time slots format
            if (isset($data['time_slots'])) {
                $data['time_slots'] = $this->validateTimeSlots($data['time_slots']);
            }

            $queue = $this->queueRepo->create($data);

            Log::info('Publishing queue created', [
                'queue_id' => $queue->queue_id,
                'org_id' => $orgId,
                'social_account_id' => $data['social_account_id']
            ]);

            return $this->formatQueueData($queue);

        } catch (\Exception $e) {
            Log::error('Failed to create publishing queue', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing queue
     */
    public function updateQueue(string $queueId, array $data): array
    {
        try {
            // Validate time slots format if provided
            if (isset($data['time_slots'])) {
                $data['time_slots'] = $this->validateTimeSlots($data['time_slots']);
            }

            $updated = $this->queueRepo->update($queueId, $data);

            if (!$updated) {
                throw new \Exception('Queue not found or update failed');
            }

            $queue = $this->queueRepo->findById($queueId);

            Log::info('Publishing queue updated', [
                'queue_id' => $queueId
            ]);

            return $this->formatQueueData($queue);

        } catch (\Exception $e) {
            Log::error('Failed to update publishing queue', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a queue
     */
    public function deleteQueue(string $queueId): bool
    {
        try {
            $deleted = $this->queueRepo->delete($queueId);

            if ($deleted) {
                Log::info('Publishing queue deleted', [
                    'queue_id' => $queueId
                ]);
            }

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to delete publishing queue', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Toggle queue active status
     */
    public function toggleQueue(string $queueId): array
    {
        try {
            $toggled = $this->queueRepo->toggleActive($queueId);

            if (!$toggled) {
                throw new \Exception('Queue not found or toggle failed');
            }

            $queue = $this->queueRepo->findById($queueId);

            Log::info('Publishing queue toggled', [
                'queue_id' => $queueId,
                'is_active' => $queue->is_active
            ]);

            return $this->formatQueueData($queue);

        } catch (\Exception $e) {
            Log::error('Failed to toggle publishing queue', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate time slots array format
     */
    protected function validateTimeSlots(array $timeSlots): array
    {
        $validated = [];

        foreach ($timeSlots as $slot) {
            if (!isset($slot['time'])) {
                continue;
            }

            // Validate time format (HH:MM)
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $slot['time'])) {
                continue;
            }

            $validated[] = [
                'time' => $slot['time'],
                'enabled' => isset($slot['enabled']) ? (bool)$slot['enabled'] : true,
            ];
        }

        return $validated;
    }

    /**
     * Format queue data for response
     */
    protected function formatQueueData($queue): array
    {
        return [
            'queue_id' => $queue->queue_id,
            'org_id' => $queue->org_id,
            'social_account_id' => $queue->social_account_id,
            'weekdays_enabled' => $queue->weekdays_enabled,
            'time_slots' => $queue->time_slots ?? [],
            'timezone' => $queue->timezone,
            'is_active' => $queue->is_active,
            'created_at' => $queue->created_at?->toIso8601String(),
            'updated_at' => $queue->updated_at?->toIso8601String(),
        ];
    }
}
