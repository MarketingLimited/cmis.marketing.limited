<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignDetailResource extends JsonResource
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

            // Relationships
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'user_id' => $this->creator->user_id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'avatar' => $this->creator->avatar ?? null,
                ];
            }),

            'org' => $this->whenLoaded('org', function () {
                return [
                    'org_id' => $this->org->org_id,
                    'org_name' => $this->org->org_name,
                    'org_type' => $this->org->org_type ?? null,
                ];
            }),

            'offerings' => $this->whenLoaded('offerings', function () {
                return $this->offerings->map(function ($offering) {
                    return [
                        'offering_id' => $offering->offering_id,
                        'offering_name' => $offering->offering_name,
                        'category' => $offering->category ?? null,
                    ];
                });
            }),

            'performance_metrics' => $this->whenLoaded('performanceMetrics', function () {
                return $this->performanceMetrics->map(function ($metric) {
                    return [
                        'metric_id' => $metric->metric_id,
                        'metric_type' => $metric->metric_type,
                        'value' => $metric->value,
                        'confidence_level' => $metric->confidence_level,
                        'collected_at' => $metric->collected_at?->toISOString(),
                    ];
                });
            }),

            'ad_campaigns' => $this->whenLoaded('adCampaigns', function () {
                return $this->adCampaigns->map(function ($adCampaign) {
                    return [
                        'ad_campaign_id' => $adCampaign->ad_campaign_id,
                        'platform' => $adCampaign->platform,
                        'platform_campaign_id' => $adCampaign->platform_campaign_id,
                        'status' => $adCampaign->status,
                        'budget' => $adCampaign->budget,
                    ];
                });
            }),

            // Computed fields
            'days_remaining' => $this->when($this->end_date && $this->status === 'active', function () {
                return max(0, now()->diffInDays($this->end_date, false));
            }),

            'is_active' => $this->status === 'active',
            'is_completed' => $this->status === 'completed',
            'is_archived' => $this->status === 'archived',
        ];
    }
}
