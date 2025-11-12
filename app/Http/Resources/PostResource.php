<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->post_id,
            'text' => $this->post_text,
            'type' => $this->post_type,
            'status' => $this->status,
            'media_urls' => $this->media_urls,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'external_post_id' => $this->external_post_id,
            'external_url' => $this->external_url,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'channel' => new ChannelResource($this->whenLoaded('channel')),
            'content_item' => new ContentItemResource($this->whenLoaded('contentItem')),

            // Metrics
            'metrics' => $this->whenLoaded('metrics', function() {
                return [
                    'impressions' => $this->metrics->sum('impressions'),
                    'clicks' => $this->metrics->sum('clicks'),
                    'likes' => $this->metrics->sum('likes'),
                    'shares' => $this->metrics->sum('shares'),
                    'comments' => $this->metrics->sum('comments'),
                ];
            }),

            // Computed fields
            'is_published' => $this->status === 'published',
            'is_scheduled' => $this->status === 'scheduled',
        ];
    }
}
