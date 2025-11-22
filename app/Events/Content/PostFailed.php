<?php

namespace App\Events\Content;

use App\Models\Social\SocialPost;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when post publishing fails
 */
class PostFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SocialPost $post,
        public string $error
    ) {}
}
