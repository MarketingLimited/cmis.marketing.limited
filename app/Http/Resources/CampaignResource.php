<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->campaign_id,
            'name' => $this->campaign_name,
            'type' => $this->campaign_type,
            'status' => $this->status,
            'budget' => $this->budget,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'target_audience' => $this->target_audience,
            'objectives' => $this->objectives,
            'kpis' => $this->kpis,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'organization' => new OrgResource($this->whenLoaded('org')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'channels' => ChannelResource::collection($this->whenLoaded('channels')),
            'assets' => CreativeAssetResource::collection($this->whenLoaded('assets')),
            'posts' => PostResource::collection($this->whenLoaded('posts')),

            // Computed fields
            'is_active' => $this->isActive(),
            'days_remaining' => $this->when($this->end_date, fn() => $this->end_date->diffInDays(now())),
        ];
    }
}
