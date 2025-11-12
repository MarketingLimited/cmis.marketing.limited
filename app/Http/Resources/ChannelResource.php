<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->channel_id,
            'name' => $this->channel_name,
            'platform' => $this->platform,
            'type' => $this->channel_type,
            'external_id' => $this->external_channel_id,
            'status' => $this->status,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'integration' => new IntegrationResource($this->whenLoaded('integration')),

            // Computed fields
            'is_active' => $this->status === 'active',
            'platform_icon' => $this->getPlatformIcon(),
        ];
    }

    protected function getPlatformIcon(): string
    {
        $icons = [
            'facebook' => 'fab fa-facebook',
            'instagram' => 'fab fa-instagram',
            'twitter' => 'fab fa-twitter',
            'linkedin' => 'fab fa-linkedin',
            'youtube' => 'fab fa-youtube',
        ];

        return $icons[$this->platform] ?? 'fas fa-globe';
    }
}
