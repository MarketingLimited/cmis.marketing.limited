<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TriggerRepositoryInterface
{
    /**
     * Get active triggers for org
     */
    public function getActiveTriggers(string $orgId): Collection;

    /**
     * Execute trigger
     */
    public function executeTrigger(
        string $triggerId,
        ?array $context = null
    ): bool;

    /**
     * Get trigger execution history
     */
    public function getTriggerHistory(
        string $triggerId,
        int $limit = 50
    ): Collection;
}
