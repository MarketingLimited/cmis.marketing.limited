<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface AuditRepositoryInterface
{
    /**
     * Log audit event
     */
    public function logEvent(
        string $userId,
        string $action,
        string $entityType,
        string $entityId,
        ?array $metadata = null
    ): bool;

    /**
     * Get audit trail for entity
     */
    public function getAuditTrail(
        string $entityType,
        string $entityId,
        int $limit = 50
    ): Collection;

    /**
     * Get user activity
     */
    public function getUserActivity(
        string $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection;
}
