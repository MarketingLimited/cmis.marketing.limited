<?php

namespace App\Repositories\Social;

use App\Models\Social\SocialPost;
use App\Models\Social\PostHistory;

class SocialPostsRepository
{
    public function create(array $data): SocialPost
    {
        $post = SocialPost::create($data);
        $this->logHistory($post, 'created', $data['status'] ?? 'draft');
        return $post;
    }

    public function schedule(SocialPost $post, $scheduledAt): bool
    {
        $oldStatus = $post->status;
        $post->update([
            'status' => SocialPost::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);
        $this->logHistory($post, 'scheduled', $post->status, $oldStatus);
        return true;
    }

    public function publish(SocialPost $post): bool
    {
        $oldStatus = $post->status;
        $post->update([
            'status' => SocialPost::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->logHistory($post, 'published', $post->status, $oldStatus);
        return true;
    }

    protected function logHistory($post, $action, $newStatus = null, $oldStatus = null, $changes = null): void
    {
        PostHistory::create([
            'post_id' => $post->id,
            'org_id' => $post->org_id,
            'action' => $action,
            'user_id' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changes' => $changes,
        ]);
    }
}
