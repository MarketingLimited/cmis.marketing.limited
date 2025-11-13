<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * This is a lightweight version for list views and dropdowns.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'campaign_id' => $this->campaign_id,
            'name' => $this->name,
            'status' => $this->status,
            'budget' => $this->budget,
            'currency' => $this->currency,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d'),

            // Status helpers
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),

            // Minimal creator info if loaded
            'created_by_name' => $this->whenLoaded('creator', function () {
                return $this->creator->name;
            }),
        ];
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'active' => 'نشطة',
            'paused' => 'متوقفة مؤقتاً',
            'completed' => 'مكتملة',
            'archived' => 'مؤرشفة',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    protected function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'blue',
            'archived' => 'slate',
            default => 'gray',
        };
    }
}
