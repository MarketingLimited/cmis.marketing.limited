<?php

namespace App\Http\Resources\Creative;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreativeAssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'asset_id' => $this->asset_id,
            'org_id' => $this->org_id,
            'asset_name' => $this->asset_name,
            'asset_type' => $this->asset_type,
            'file_path' => $this->file_path,
            'file_url' => $this->when($this->file_path, function () {
                return asset('storage/' . $this->file_path);
            }),
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Status helpers
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'is_approved' => $this->status === 'approved',
            'is_rejected' => $this->status === 'rejected',
            'is_pending' => $this->status === 'pending_review',
        ];
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending_review' => 'قيد المراجعة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'archived' => 'مؤرشف',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    protected function getStatusColor(): string
    {
        return match ($this->status) {
            'pending_review' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'archived' => 'gray',
            default => 'gray',
        };
    }
}
