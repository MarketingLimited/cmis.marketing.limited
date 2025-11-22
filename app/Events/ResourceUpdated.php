<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ResourceUpdated Event
 *
 * Broadcasts real-time updates when resources change.
 * Enables cross-interface synchronization (web, API, GPT).
 *
 * Issue #70 - Implement real-time updates across interfaces
 */
class ResourceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The resource type (e.g., 'campaign', 'content_plan').
     */
    public string $resourceType;

    /**
     * The resource ID.
     */
    public string $resourceId;

    /**
     * The action performed (created, updated, deleted).
     */
    public string $action;

    /**
     * The organization ID.
     */
    public string $orgId;

    /**
     * The updated data.
     */
    public ?array $data;

    /**
     * The user who made the change.
     */
    public ?string $userId;

    /**
     * Timestamp of the change.
     */
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $resourceType,
        string $resourceId,
        string $action,
        string $orgId,
        ?array $data = null,
        ?string $userId = null
    ) {
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->action = $action;
        $this->orgId = $orgId;
        $this->data = $data;
        $this->userId = $userId;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to organization-specific private channel.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organization.{$this->orgId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'resource.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'action' => $this->action,
            'org_id' => $this->orgId,
            'data' => $this->data,
            'user_id' => $this->userId,
            'timestamp' => $this->timestamp,
        ];
    }
}
