<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Platform\Integration;

class IntegrationQueueSettings extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.integration_queue_settings';

    protected $fillable = [
        'org_id',
        'integration_id',
        'queue_enabled',
        'posting_times',
        'days_enabled',
        'schedule',
        'posts_per_day',
    ];

    protected $casts = [
        'queue_enabled' => 'boolean',
        'posting_times' => 'array',
        'days_enabled' => 'array',
        'schedule' => 'array',
        'posts_per_day' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the integration that owns the queue settings.
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get the next available posting time for this integration.
     */
    public function getNextAvailableTime(): ?string
    {
        if (!$this->queue_enabled || empty($this->posting_times)) {
            return null;
        }

        $now = now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');
        $currentDayOfWeek = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday

        // Check if today is enabled
        $daysEnabled = $this->days_enabled ?? [1, 2, 3, 4, 5]; // Default to weekdays

        // Try to find a time slot today
        foreach ($this->posting_times as $time) {
            if ($time > $currentTime && in_array($currentDayOfWeek, $daysEnabled)) {
                return "{$today} {$time}";
            }
        }

        // If no time slot today, find next available day
        for ($i = 1; $i <= 7; $i++) {
            $nextDay = $now->copy()->addDays($i);
            $nextDayOfWeek = $nextDay->dayOfWeek;

            if (in_array($nextDayOfWeek, $daysEnabled)) {
                $nextDate = $nextDay->format('Y-m-d');
                $firstTime = $this->posting_times[0];
                return "{$nextDate} {$firstTime}";
            }
        }

        return null;
    }

    /**
     * Get default queue settings for a platform.
     */
    public static function getDefaultSettings(string $platform): array
    {
        $defaults = [
            'facebook' => [
                'posting_times' => ['09:00', '13:00', '18:00'],
                'posts_per_day' => 3,
            ],
            'instagram' => [
                'posting_times' => ['10:00', '14:00', '19:00'],
                'posts_per_day' => 3,
            ],
            'twitter' => [
                'posting_times' => ['08:00', '12:00', '16:00', '20:00'],
                'posts_per_day' => 4,
            ],
            'linkedin' => [
                'posting_times' => ['09:00', '12:00', '17:00'],
                'posts_per_day' => 3,
            ],
        ];

        return $defaults[$platform] ?? [
            'posting_times' => ['09:00', '13:00', '18:00'],
            'posts_per_day' => 3,
        ];
    }
}
