<?php

namespace App\Models\Concerns;

use App\Events\ResourceUpdated;
use Illuminate\Support\Facades\Auth;

/**
 * BroadcastsUpdates Trait
 *
 * Automatically broadcasts real-time updates when model changes.
 * Enables cross-interface synchronization.
 *
 * Usage:
 * ```php
 * class Campaign extends BaseModel
 * {
 *     use BroadcastsUpdates;
 *
 *     protected $broadcastAs = 'campaign'; // Resource type
 * }
 * ```
 *
 * Issue #70 - Implement real-time updates across interfaces
 */
trait BroadcastsUpdates
{
    /**
     * Boot the broadcasting trait.
     */
    protected static function bootBroadcastsUpdates(): void
    {
        // Broadcast on create
        static::created(function ($model) {
            $model->broadcastUpdate('created');
        });

        // Broadcast on update
        static::updated(function ($model) {
            $model->broadcastUpdate('updated');
        });

        // Broadcast on delete
        static::deleted(function ($model) {
            $model->broadcastUpdate('deleted');
        });
    }

    /**
     * Broadcast a resource update event.
     */
    protected function broadcastUpdate(string $action): void
    {
        // Skip if broadcasting is disabled
        if (!config('broadcasting.enabled', true)) {
            return;
        }

        // Get resource type (either from $broadcastAs property or class name)
        $resourceType = $this->broadcastAs ?? strtolower(class_basename(static::class));

        // Get organization ID
        $orgId = $this->org_id ?? $this->organization_id ?? null;

        if (!$orgId) {
            // Cannot broadcast without org context
            return;
        }

        // Get data to broadcast (only include safe fields)
        $data = $this->getBroadcastData();

        // Get current user ID
        $userId = Auth::id();

        // Dispatch broadcast event
        event(new ResourceUpdated(
            resourceType: $resourceType,
            resourceId: $this->id,
            action: $action,
            orgId: $orgId,
            data: $data,
            userId: $userId
        ));
    }

    /**
     * Get data to include in broadcast.
     *
     * Override this method to customize what data is broadcast.
     */
    protected function getBroadcastData(): array
    {
        // Default: return basic fields without sensitive data
        $data = [
            'id' => $this->id,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Add name if exists
        if (isset($this->name)) {
            $data['name'] = $this->name;
        }

        // Add status if exists
        if (isset($this->status)) {
            $data['status'] = $this->status;
        }

        return $data;
    }

    /**
     * Manually trigger a broadcast for this model.
     *
     * Useful for triggering updates without actual model changes.
     */
    public function broadcast(string $action = 'updated', ?array $customData = null): void
    {
        $resourceType = $this->broadcastAs ?? strtolower(class_basename(static::class));
        $orgId = $this->org_id ?? $this->organization_id ?? null;

        if (!$orgId) {
            return;
        }

        $data = $customData ?? $this->getBroadcastData();

        event(new ResourceUpdated(
            resourceType: $resourceType,
            resourceId: $this->id,
            action: $action,
            orgId: $orgId,
            data: $data,
            userId: Auth::id()
        ));
    }
}
