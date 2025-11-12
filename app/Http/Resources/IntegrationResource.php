<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->integration_id,
            'platform' => $this->platform,
            'auth_type' => $this->auth_type,
            'status' => $this->status,
            'scopes' => $this->scopes,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_sync' => $this->last_sync?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'organization' => new OrgResource($this->whenLoaded('org')),
            'connector' => new UserResource($this->whenLoaded('connector')),

            // Computed fields - DO NOT expose credentials
            'is_active' => $this->status === 'active',
            'is_expired' => $this->expires_at && $this->expires_at->isPast(),
            'needs_refresh' => $this->when($this->expires_at, fn() => $this->expires_at->diffInDays(now()) < 7),
        ];
    }
}
