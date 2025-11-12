<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrgResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->org_id,
            'name' => $this->org_name,
            'slug' => $this->org_slug,
            'logo_url' => $this->logo_url,
            'website' => $this->website,
            'industry' => $this->industry,
            'size' => $this->org_size,
            'timezone' => $this->timezone,
            'settings' => $this->settings,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'users' => UserResource::collection($this->whenLoaded('users')),
            'campaigns' => CampaignResource::collection($this->whenLoaded('campaigns')),

            // Computed fields
            'member_count' => $this->whenLoaded('users', fn() => $this->users->count()),
        ];
    }
}
