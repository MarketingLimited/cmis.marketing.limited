<?php

namespace App\Repositories\Contracts;

use App\Models\Publishing\PublishingQueue;
use Illuminate\Support\Collection;

interface PublishingQueueRepositoryInterface
{
    /**
     * Get all publishing queues for an organization
     */
    public function getForOrg(string $orgId, bool $activeOnly = false): Collection;

    /**
     * Get queue by ID
     */
    public function findById(string $queueId): ?PublishingQueue;

    /**
     * Get queue for a specific social account
     */
    public function getForAccount(string $socialAccountId): ?PublishingQueue;

    /**
     * Create a new publishing queue
     */
    public function create(array $data): PublishingQueue;

    /**
     * Update an existing publishing queue
     */
    public function update(string $queueId, array $data): bool;

    /**
     * Delete a publishing queue
     */
    public function delete(string $queueId): bool;

    /**
     * Toggle queue active status
     */
    public function toggleActive(string $queueId): bool;

    /**
     * Get all active queues for scheduling
     */
    public function getActiveQueues(): Collection;
}
