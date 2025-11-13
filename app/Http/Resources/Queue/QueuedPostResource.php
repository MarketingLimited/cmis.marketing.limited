<?php

namespace App\Http\Resources\Queue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueuedPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'post_id' => $this->post_id,
            'social_account_id' => $this->social_account_id,
            'content' => $this->content ?? null,
            'scheduling' => [
                'scheduled_for' => $this->scheduled_for,
                'status' => $this->status ?? 'queued',
                'time_until_post' => $this->getTimeUntilPost()
            ],
            'metadata' => [
                'platform' => $this->platform ?? null,
                'post_type' => $this->post_type ?? null,
                'has_media' => isset($this->media_urls) && !empty($this->media_urls)
            ],
            'created_at' => $this->created_at ?? null
        ];
    }

    /**
     * Get human-readable time until post
     */
    protected function getTimeUntilPost(): ?string
    {
        if (!isset($this->scheduled_for)) {
            return null;
        }

        try {
            $scheduledTime = new \DateTime($this->scheduled_for);
            $now = new \DateTime();
            $interval = $now->diff($scheduledTime);

            if ($scheduledTime < $now) {
                return 'Overdue';
            }

            if ($interval->days > 0) {
                return $interval->days . ' day' . ($interval->days > 1 ? 's' : '');
            }

            if ($interval->h > 0) {
                return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
            }

            if ($interval->i > 0) {
                return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
            }

            return 'Less than a minute';
        } catch (\Exception $e) {
            return null;
        }
    }
}
