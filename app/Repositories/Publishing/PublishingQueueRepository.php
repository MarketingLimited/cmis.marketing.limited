<?php

namespace App\Repositories\Publishing;

use App\Models\Publishing\PublishingQueue;
use App\Repositories\Contracts\PublishingQueueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PublishingQueueRepository implements PublishingQueueRepositoryInterface
{
    /**
     * Get all publishing queues (automatically filtered by RLS)
     */
    public function getAll(bool $activeOnly = false): Collection
    {
        $query = PublishingQueue::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->with('socialAccount')->get();
    }

    /**
     * Get queue by ID
     */
    public function findById(string $queueId): ?PublishingQueue
    {
        return PublishingQueue::find($queueId);
    }

    /**
     * Get queue for a specific social account
     */
    public function getForAccount(string $socialAccountId): ?PublishingQueue
    {
        return PublishingQueue::where('social_account_id', $socialAccountId)->first();
    }

    /**
     * Create a new publishing queue
     */
    public function create(array $data): PublishingQueue
    {
        try {
            // Ensure UUID is set
            if (!isset($data['queue_id'])) {
                $data['queue_id'] = (string) \Illuminate\Support\Str::uuid();
            }

            // Set defaults
            $data['weekdays_enabled'] = $data['weekdays_enabled'] ?? '1111111';
            $data['time_slots'] = $data['time_slots'] ?? [];
            $data['timezone'] = $data['timezone'] ?? 'UTC';
            $data['is_active'] = $data['is_active'] ?? true;

            return PublishingQueue::create($data);

        } catch (\Exception $e) {
            Log::error('Failed to create publishing queue', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing publishing queue
     */
    public function update(string $queueId, array $data): bool
    {
        try {
            $queue = $this->findById($queueId);

            if (!$queue) {
                return false;
            }

            return $queue->update($data);

        } catch (\Exception $e) {
            Log::error('Failed to update publishing queue', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a publishing queue
     */
    public function delete(string $queueId): bool
    {
        try {
            $queue = $this->findById($queueId);

            if (!$queue) {
                return false;
            }

            return $queue->delete();

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
    public function toggleActive(string $queueId): bool
    {
        try {
            $queue = $this->findById($queueId);

            if (!$queue) {
                return false;
            }

            $queue->is_active = !$queue->is_active;
            return $queue->save();

        } catch (\Exception $e) {
            Log::error('Failed to toggle publishing queue', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all active queues for scheduling
     */
    public function getActiveQueues(): Collection
    {
        return PublishingQueue::where('is_active', true)
            ->with('socialAccount')
            ->get();
    }
}
