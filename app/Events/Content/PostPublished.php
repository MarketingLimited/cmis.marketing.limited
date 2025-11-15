<?php

namespace App\Events\Content;

use App\Models\Social\SocialPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SocialPost $post
    ) {}
}
