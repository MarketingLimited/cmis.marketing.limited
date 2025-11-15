<?php

namespace App\Events\Content;

use App\Models\SocialPost;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a social post is scheduled
 */
class PostScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SocialPost $post
    ) {}
}
