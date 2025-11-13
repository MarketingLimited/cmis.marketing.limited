<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->content_id,
            'type' => $this->item_type,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'channel' => new ChannelResource($this->whenLoaded('channel')),
            'plan' => $this->whenLoaded('plan'),
            'context' => $this->whenLoaded('context'),
            'creator' => new UserResource($this->whenLoaded('creator')),

            // Computed fields
            'is_scheduled' => $this->status === 'scheduled',
            'is_published' => $this->status === 'published',
            'word_count' => str_word_count(strip_tags($this->body)),
        ];
    }
}
