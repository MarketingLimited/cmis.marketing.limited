<?php

namespace App\Http\Resources\Queue;

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
            'queue_id' => $this->queue_id,
            'org_id' => $this->org_id,
            'social_account_id' => $this->social_account_id,
            'configuration' => [
                'weekdays_enabled' => $this->weekdays_enabled,
                'weekdays_readable' => $this->getReadableWeekdays(),
                'time_slots' => $this->getTimeSlots(),
                'timezone' => $this->timezone,
                'posts_per_week' => $this->calculatePostsPerWeek()
            ],
            'status' => [
                'is_active' => $this->is_active,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString()
            ]
        ];
    }

    /**
     * Get human-readable weekdays
     */
    protected function getReadableWeekdays(): array
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $enabled = str_split($this->weekdays_enabled ?? '1111100');
        $readable = [];

        foreach ($enabled as $index => $isEnabled) {
            if ($isEnabled === '1') {
                $readable[] = $days[$index];
            }
        }

        return $readable;
    }

    /**
     * Get time slots as array
     */
    protected function getTimeSlots(): array
    {
        if (is_string($this->time_slots)) {
            return json_decode($this->time_slots, true) ?? [];
        }

        return $this->time_slots ?? [];
    }

    /**
     * Calculate total posts per week
     */
    protected function calculatePostsPerWeek(): int
    {
        $activeDays = substr_count($this->weekdays_enabled ?? '1111100', '1');
        $slotsPerDay = count($this->getTimeSlots());

        return $activeDays * $slotsPerDay;
    }
}
