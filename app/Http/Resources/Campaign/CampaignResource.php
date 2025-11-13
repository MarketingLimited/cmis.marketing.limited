<?php

namespace App\Http\Resources\Campaign;

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
            'campaign_id' => $this->campaign_id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'objective' => $this->objective,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'budget' => $this->budget,
            'currency' => $this->currency,
            'context_id' => $this->context_id,
            'creative_id' => $this->creative_id,
            'value_id' => $this->value_id,
            'provider' => $this->provider,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'user_id' => $this->creator->user_id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'org' => $this->whenLoaded('org', function () {
                return [
                    'org_id' => $this->org->org_id,
                    'org_name' => $this->org->org_name,
                ];
            }),
        ];
    }
}
