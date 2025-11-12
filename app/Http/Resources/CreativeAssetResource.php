<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreativeAssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->asset_id,
            'name' => $this->asset_name,
            'type' => $this->asset_type,
            'file_url' => $this->file_url,
            'thumbnail_url' => $this->thumbnail_url,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'dimensions' => $this->dimensions,
            'duration' => $this->duration,
            'status' => $this->status,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'uploader' => new UserResource($this->whenLoaded('uploader')),

            // Computed fields
            'file_size_human' => $this->when($this->file_size, fn() => $this->getFileSizeHuman()),
            'is_image' => $this->asset_type === 'image',
            'is_video' => $this->asset_type === 'video',
        ];
    }

    protected function getFileSizeHuman(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
