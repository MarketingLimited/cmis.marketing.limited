<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublishingQueueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->queue_id,
            'org_id' => $this->org_id,
            'social_account_id' => $this->social_account_id,
            'weekdays_enabled' => $this->weekdays_enabled,
            'weekdays' => $this->getWeekdaysArray(),
            'time_slots' => $this->time_slots ?? [],
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'social_account' => $this->whenLoaded('socialAccount', function() {
                return [
                    'id' => $this->socialAccount->account_id ?? null,
                    'platform' => $this->socialAccount->platform ?? null,
                    'account_name' => $this->socialAccount->account_name ?? null,
                ];
            }),

            // Computed fields
            'enabled_days_count' => $this->getEnabledDaysCount(),
            'enabled_slots_count' => count($this->getAllEnabledTimeSlots()),
        ];
    }

    /**
     * Get weekdays as array for easier frontend processing
     */
    protected function getWeekdaysArray(): array
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $enabled = str_split($this->weekdays_enabled);

        $result = [];
        foreach ($days as $index => $day) {
            $result[] = [
                'day' => $day,
                'enabled' => isset($enabled[$index]) && $enabled[$index] === '1',
            ];
        }

        return $result;
    }

    /**
     * Count enabled days
     */
    protected function getEnabledDaysCount(): int
    {
        return substr_count($this->weekdays_enabled, '1');
    }
}
